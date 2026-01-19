<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;

class ProcessVideoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The model instance.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The model type.
     *
     * @var string
     */
    protected $modelType;

    /**
     * The video file path.
     *
     * @var string
     */
    protected $videoPath;

    /**
     * The maximum number of attempts.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param mixed $model The model instance (SuccessStory, Departure, etc.)
     * @param string $modelType The model type identifier
     * @param string $videoPath The path to the video file
     */
    public function __construct($model, string $modelType, string $videoPath)
    {
        $this->model = $model;
        $this->modelType = $modelType;
        $this->videoPath = $videoPath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Starting video processing', [
                'model_type' => $this->modelType,
                'model_id' => $this->model->id,
                'video_path' => $this->videoPath,
            ]);

            // Check if FFMpeg is available
            if (!class_exists('\FFMpeg\FFMpeg')) {
                Log::warning('FFMpeg not available, skipping video processing');
                return;
            }

            $disk = Storage::disk('private');
            $fullPath = $disk->path($this->videoPath);

            if (!file_exists($fullPath)) {
                Log::error('Video file not found', ['path' => $fullPath]);
                return;
            }

            // Initialize FFMpeg
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => config('services.ffmpeg.binaries', '/usr/bin/ffmpeg'),
                'ffprobe.binaries' => config('services.ffmpeg.ffprobe_binaries', '/usr/bin/ffprobe'),
                'timeout'          => 300,
                'ffmpeg.threads'   => 4,
            ]);

            $video = $ffmpeg->open($fullPath);

            // Generate thumbnail
            $this->generateThumbnail($video, $fullPath);

            // Get video metadata
            $this->extractMetadata($video, $fullPath);

            // Optionally compress/optimize video if it's too large
            if (filesize($fullPath) > 50 * 1024 * 1024) { // 50MB
                $this->compressVideo($video, $fullPath);
            }

            Log::info('Video processing completed successfully', [
                'model_type' => $this->modelType,
                'model_id' => $this->model->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'model_type' => $this->modelType,
                'model_id' => $this->model->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Generate a thumbnail from the video.
     *
     * @param mixed $video
     * @param string $fullPath
     * @return void
     */
    protected function generateThumbnail($video, string $fullPath): void
    {
        try {
            $thumbnailPath = $this->getThumbnailPath();
            $disk = Storage::disk('private');
            $fullThumbnailPath = $disk->path($thumbnailPath);

            // Ensure directory exists
            $directory = dirname($fullThumbnailPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Generate thumbnail at 5 seconds
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(5));
            $frame->save($fullThumbnailPath);

            // Update model with thumbnail path
            $this->model->update(['thumbnail_path' => $thumbnailPath]);

            Log::info('Thumbnail generated', [
                'thumbnail_path' => $thumbnailPath,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to generate thumbnail', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract video metadata and duration.
     *
     * @param mixed $video
     * @param string $fullPath
     * @return void
     */
    protected function extractMetadata($video, string $fullPath): void
    {
        try {
            $ffprobe = \FFMpeg\FFProbe::create([
                'ffprobe.binaries' => config('services.ffmpeg.ffprobe_binaries', '/usr/bin/ffprobe'),
            ]);

            $duration = $ffprobe->format($fullPath)->get('duration');
            $filesize = filesize($fullPath);

            // Update model with metadata
            $this->model->update([
                'video_duration' => (int) $duration,
                'video_filesize' => $filesize,
            ]);

            Log::info('Video metadata extracted', [
                'duration' => $duration,
                'filesize' => $filesize,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to extract metadata', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Compress video if it's too large.
     *
     * @param mixed $video
     * @param string $fullPath
     * @return void
     */
    protected function compressVideo($video, string $fullPath): void
    {
        try {
            $compressedPath = $this->getCompressedPath();
            $disk = Storage::disk('private');
            $fullCompressedPath = $disk->path($compressedPath);

            // Ensure directory exists
            $directory = dirname($fullCompressedPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Compress to 720p with reasonable bitrate
            $format = new X264();
            $format->setKiloBitrate(1000)
                   ->setAudioKiloBitrate(128);

            $video->filters()
                  ->resize(new Dimension(1280, 720))
                  ->synchronize();

            $video->save($format, $fullCompressedPath);

            // Replace original with compressed version if compression was successful
            if (file_exists($fullCompressedPath) && filesize($fullCompressedPath) < filesize($fullPath)) {
                unlink($fullPath);
                rename($fullCompressedPath, $fullPath);

                Log::info('Video compressed successfully', [
                    'original_size' => filesize($fullPath),
                    'compressed_size' => filesize($fullCompressedPath),
                ]);
            } else {
                // Clean up compressed file if it's larger
                if (file_exists($fullCompressedPath)) {
                    unlink($fullCompressedPath);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to compress video', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get thumbnail path for the video.
     *
     * @return string
     */
    protected function getThumbnailPath(): string
    {
        $pathInfo = pathinfo($this->videoPath);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '.jpg';
    }

    /**
     * Get compressed video path.
     *
     * @return string
     */
    protected function getCompressedPath(): string
    {
        $pathInfo = pathinfo($this->videoPath);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_compressed.' . $pathInfo['extension'];
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Video processing job failed permanently', [
            'model_type' => $this->modelType,
            'model_id' => $this->model->id,
            'video_path' => $this->videoPath,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admin or mark video as failed
        // You could update the model with a processing_failed flag
        try {
            $this->model->update(['video_processing_failed' => true]);
        } catch (\Exception $e) {
            // Silent fail - model might not have this column
        }
    }
}
