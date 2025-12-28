<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\FileStorageService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FileStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileStorageService();
        Storage::fake('private');
        Storage::fake('public');
        Storage::fake('photos');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    // =========================================================================
    // FILE STORAGE
    // =========================================================================

    /** @test */
    public function it_can_store_a_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $result = $this->service->store($file, 'cnic', 123);

        $this->assertArrayHasKey('disk', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('private', $result['disk']);
        Storage::disk('private')->assertExists($result['path']);
    }

    /** @test */
    public function it_stores_files_in_correct_category_paths()
    {
        $file = UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf');

        $result = $this->service->store($file, 'passport', 456);

        $this->assertStringContains('candidates/456/passport', $result['path']);
    }

    /** @test */
    public function it_throws_exception_for_unknown_category()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown document category');

        $this->service->store($file, 'unknown_category', 123);
    }

    // =========================================================================
    // MAGIC BYTES VALIDATION
    // =========================================================================

    /** @test */
    public function it_validates_pdf_magic_bytes()
    {
        // Create a fake PDF with proper magic bytes
        $pdfContent = "%PDF-1.4\n%Test PDF content";
        $file = UploadedFile::fake()->createWithContent('document.pdf', $pdfContent);

        $result = $this->service->validate($file);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_rejects_pdf_with_wrong_magic_bytes()
    {
        // Create a file with .pdf extension but wrong content
        $fakeContent = "This is not a PDF file";
        $file = UploadedFile::fake()->createWithContent('document.pdf', $fakeContent);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('content does not match', $result['error']);
    }

    /** @test */
    public function it_validates_jpg_magic_bytes()
    {
        // Create a fake JPG with proper magic bytes (JFIF)
        $jpgContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00";
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $jpgContent);

        $result = $this->service->validate($file);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_rejects_jpg_with_wrong_magic_bytes()
    {
        // Create a file with .jpg extension but wrong content
        $fakeContent = "Not a JPEG file";
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $fakeContent);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_validates_png_magic_bytes()
    {
        // PNG magic bytes
        $pngContent = "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 100);
        $file = UploadedFile::fake()->createWithContent('image.png', $pngContent);

        $result = $this->service->validate($file);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_validates_zip_magic_bytes_for_docx()
    {
        // DOCX is a ZIP file with PK header
        $docxContent = "PK\x03\x04" . str_repeat("\x00", 100);
        $file = UploadedFile::fake()->createWithContent('document.docx', $docxContent);

        $result = $this->service->validate($file);

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // DANGEROUS FILE DETECTION
    // =========================================================================

    /** @test */
    public function it_rejects_php_files()
    {
        $file = UploadedFile::fake()->create('script.php', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('security reasons', $result['error']);
    }

    /** @test */
    public function it_rejects_phtml_files()
    {
        $file = UploadedFile::fake()->create('script.phtml', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_rejects_exe_files()
    {
        $file = UploadedFile::fake()->create('program.exe', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_rejects_bat_files()
    {
        $file = UploadedFile::fake()->create('script.bat', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_rejects_js_files()
    {
        $file = UploadedFile::fake()->create('script.js', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_rejects_html_files()
    {
        $file = UploadedFile::fake()->create('page.html', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_rejects_svg_files()
    {
        $file = UploadedFile::fake()->create('image.svg', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // DOUBLE EXTENSION ATTACKS
    // =========================================================================

    /** @test */
    public function it_rejects_double_extension_php_jpg()
    {
        $file = UploadedFile::fake()->create('image.php.jpg', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('suspicious patterns', $result['error']);
    }

    /** @test */
    public function it_rejects_double_extension_exe_pdf()
    {
        $file = UploadedFile::fake()->create('document.exe.pdf', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_rejects_double_extension_html_png()
    {
        $file = UploadedFile::fake()->create('file.html.png', 100);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // PHP CODE DETECTION
    // =========================================================================

    /** @test */
    public function it_detects_php_code_in_file_content()
    {
        $maliciousContent = "GIF89a\n<?php echo 'hacked'; ?>";
        $file = UploadedFile::fake()->createWithContent('image.gif', $maliciousContent);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('malicious content', $result['error']);
    }

    /** @test */
    public function it_detects_short_php_tags()
    {
        $maliciousContent = "GIF89a\n<?= system('ls'); ?>";
        $file = UploadedFile::fake()->createWithContent('image.gif', $maliciousContent);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_detects_script_language_php()
    {
        $maliciousContent = "GIF89a\n<script language=\"php\">echo 'test';</script>";
        $file = UploadedFile::fake()->createWithContent('image.gif', $maliciousContent);

        $result = $this->service->validate($file);

        $this->assertFalse($result['valid']);
    }

    // =========================================================================
    // FILE SIZE VALIDATION
    // =========================================================================

    /** @test */
    public function it_rejects_files_exceeding_size_limit()
    {
        // Create file larger than 5MB (5120KB default)
        $file = UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf');

        $result = $this->service->validate($file, [], 5120);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('size exceeds', $result['error']);
    }

    /** @test */
    public function it_accepts_files_within_size_limit()
    {
        $pdfContent = "%PDF-1.4\n%Test content";
        $file = UploadedFile::fake()->createWithContent('small.pdf', $pdfContent);

        $result = $this->service->validate($file, [], 5120);

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // MIME TYPE VALIDATION
    // =========================================================================

    /** @test */
    public function it_validates_allowed_mime_types()
    {
        $pdfContent = "%PDF-1.4\n%Test content";
        $file = UploadedFile::fake()->createWithContent('document.pdf', $pdfContent);

        $result = $this->service->validate($file, ['application/pdf']);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_rejects_disallowed_mime_types()
    {
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $result = $this->service->validate($file, ['application/pdf', 'image/jpeg']);

        $this->assertFalse($result['valid']);
        $this->assertStringContains('type not allowed', $result['error']);
    }

    // =========================================================================
    // URL GENERATION
    // =========================================================================

    /** @test */
    public function it_generates_secure_url_for_private_files()
    {
        $url = $this->service->generateUrl('private', 'candidates/123/cnic/document.pdf');

        $this->assertStringContains('secure-file', $url);
    }

    /** @test */
    public function it_generates_view_url_for_private_files()
    {
        $url = $this->service->generateViewUrl('private', 'candidates/123/cnic/document.pdf');

        $this->assertStringContains('secure-file', $url);
    }

    // =========================================================================
    // FILE OPERATIONS
    // =========================================================================

    /** @test */
    public function it_can_check_if_file_exists()
    {
        Storage::disk('private')->put('test/file.txt', 'content');

        $this->assertTrue($this->service->exists('private', 'test/file.txt'));
        $this->assertFalse($this->service->exists('private', 'nonexistent.txt'));
    }

    /** @test */
    public function it_can_delete_a_file()
    {
        Storage::disk('private')->put('test/delete-me.txt', 'content');

        $result = $this->service->delete('private', 'test/delete-me.txt');

        $this->assertTrue($result);
        Storage::disk('private')->assertMissing('test/delete-me.txt');
    }

    /** @test */
    public function it_can_get_file_contents()
    {
        Storage::disk('private')->put('test/content.txt', 'Hello World');

        $content = $this->service->get('private', 'test/content.txt');

        $this->assertEquals('Hello World', $content);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_file()
    {
        $content = $this->service->get('private', 'nonexistent.txt');

        $this->assertNull($content);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
