<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Campus Registration</h2>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="font-semibold mb-4">Document Status</h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Complete</span>
                    <span class="font-bold text-green-600 text-lg"><?php echo e($stats['complete_docs'] ?? 0); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Incomplete</span>
                    <span class="font-bold text-yellow-600 text-lg"><?php echo e($stats['incomplete_docs'] ?? 0); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="font-semibold mb-4">Overview</h4>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total Pending</span>
                    <span class="font-bold text-blue-600 text-lg"><?php echo e($stats['total_pending'] ?? 0); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Registrations Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Pending Registrations</h3>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                <?php echo e($stats['total_pending'] ?? 0); ?> Pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <?php if($pendingRegistrations->count() > 0): ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">CNIC</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Campus</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Documents</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Undertakings</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $pendingRegistrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $candidate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4"><?php echo e($candidate->name); ?></td>
                                <td class="px-6 py-4 font-mono"><?php echo e($candidate->cnic ?? '-'); ?></td>
                                <td class="px-6 py-4"><?php echo e($candidate->campus->name ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <?php if($candidate->documents_count > 0): ?>
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            Complete (<?php echo e($candidate->documents_count); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">
                                            Missing
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($candidate->undertakings_count > 0): ?>
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            Signed
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="<?php echo e(route('candidates.show', $candidate->id)); ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">View</a>
                                        <a href="<?php echo e(route('registration.edit', $candidate->id)); ?>" 
                                           class="text-green-600 hover:text-green-900 text-sm font-medium">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                
                <div class="mt-4">
                    <?php echo e($pendingRegistrations->links()); ?>

                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">No pending registrations</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/iihsedup/oep.jaamiah.com/resources/views/dashboard/tabs/registration.blade.php ENDPATH**/ ?>