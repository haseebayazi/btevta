<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Candidates Listing</h2>
        <div class="flex space-x-3">
            <a href="<?php echo e(route('import.candidates.form')); ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-file-import mr-2"></i>Import from Excel
            </a>
            <a href="<?php echo e(route('candidates.create')); ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Add Candidate
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="text" name="search" placeholder="Search by Name, CNIC, BTEVTA ID" 
                   value="<?php echo e(request('search')); ?>" class="px-4 py-2 border rounded-lg">
            
            <select name="status" class="px-4 py-2 border rounded-lg">
                <option value="">All Status</option>
                <option value="listed" <?php echo e(request('status') === 'listed' ? 'selected' : ''); ?>>Listed</option>
                <option value="screening" <?php echo e(request('status') === 'screening' ? 'selected' : ''); ?>>Screening</option>
                <option value="registered" <?php echo e(request('status') === 'registered' ? 'selected' : ''); ?>>Registered</option>
                <option value="training" <?php echo e(request('status') === 'training' ? 'selected' : ''); ?>>Training</option>
                <option value="visa_processing" <?php echo e(request('status') === 'visa_processing' ? 'selected' : ''); ?>>Visa</option>
                <option value="departed" <?php echo e(request('status') === 'departed' ? 'selected' : ''); ?>>Departed</option>
            </select>
            
            <select name="trade_id" class="px-4 py-2 border rounded-lg">
                <option value="">All Trades</option>
                <?php $__currentLoopData = $trades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $trade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($id); ?>" <?php echo e(request('trade_id') == $id ? 'selected' : ''); ?>><?php echo e($trade); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            
            <select name="batch_id" class="px-4 py-2 border rounded-lg">
                <option value="">All Batches</option>
                <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($id); ?>" <?php echo e(request('batch_id') == $id ? 'selected' : ''); ?>><?php echo e($batch); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </form>
    </div>

    <!-- Candidates Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="overflow-x-auto">
            <?php if($candidates->count() > 0): ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">BTEVTA ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">CNIC</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Trade</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $candidates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $candidate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4"><?php echo e($candidate->name); ?></td>
                                <td class="px-6 py-4 font-mono"><?php echo e($candidate->btevta_id); ?></td>
                                <td class="px-6 py-4 font-mono"><?php echo e($candidate->cnic ?? '-'); ?></td>
                                <td class="px-6 py-4"><?php echo e($candidate->campus->name ?? 'N/A'); ?></td>
                                <td class="px-6 py-4"><?php echo e($candidate->trade->name ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                        <?php if($candidate->status === 'listed'): ?> bg-blue-100 text-blue-800
                                        <?php elseif($candidate->status === 'screening'): ?> bg-yellow-100 text-yellow-800
                                        <?php elseif($candidate->status === 'registered'): ?> bg-green-100 text-green-800
                                        <?php elseif($candidate->status === 'training'): ?> bg-purple-100 text-purple-800
                                        <?php elseif($candidate->status === 'visa_processing'): ?> bg-indigo-100 text-indigo-800
                                        <?php elseif($candidate->status === 'departed'): ?> bg-green-100 text-green-800
                                        <?php else: ?> bg-red-100 text-red-800
                                        <?php endif; ?>
                                    ">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $candidate->status))); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="<?php echo e(route('candidates.show', $candidate->id)); ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">View</a>
                                        <a href="<?php echo e(route('candidates.edit', $candidate->id)); ?>" 
                                           class="text-green-600 hover:text-green-900 text-sm font-medium">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="mt-4">
                    <?php echo e($candidates->links()); ?>

                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">No candidates found</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/iihsedup/oep.jaamiah.com/resources/views/dashboard/tabs/candidates-listing.blade.php ENDPATH**/ ?>