<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Training Management</h2>
        <a href="<?php echo e(route('training.create')); ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>New Training
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Active Batches</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo e($stats['active_batches'] ?? 0); ?></p>
                </div>
                <i class="fas fa-layer-group text-blue-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">In Progress</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo e($stats['in_progress'] ?? 0); ?></p>
                </div>
                <i class="fas fa-graduation-cap text-yellow-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Completed</p>
                    <p class="text-3xl font-bold text-green-600 mt-2"><?php echo e($stats['completed'] ?? 0); ?></p>
                </div>
                <i class="fas fa-check-circle text-green-400 text-3xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Certificates</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo e($stats['completed_count'] ?? 0); ?></p>
                </div>
                <i class="fas fa-certificate text-purple-400 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Active Batches Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Active Training Batches</h3>
        
        <div class="overflow-x-auto">
            <?php if($activeBatches->count() > 0): ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Batch Number</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Candidates</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $activeBatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold"><?php echo e($batch->batch_number); ?></td>
                                <td class="px-6 py-4"><?php echo e($batch->campus->name ?? 'N/A'); ?></td>
                                <td class="px-6 py-4"><?php echo e($batch->trade->name ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="font-bold"><?php echo e($batch->candidates_count); ?></span> candidates
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                        <?php echo e(ucfirst($batch->status)); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo e(route('training.show', $batch->id)); ?>" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                
                <div class="mt-4">
                    <?php echo e($activeBatches->links()); ?>

                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">No active training batches</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/iihsedup/oep.jaamiah.com/resources/views/dashboard/tabs/training.blade.php ENDPATH**/ ?>