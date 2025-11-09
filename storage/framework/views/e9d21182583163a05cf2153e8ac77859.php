<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Complaints Redressal Mechanism</h2>
        <a href="<?php echo e(route('complaints.create')); ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Register Complaint
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Total Complaints</p>
            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo e($complaintStats['total'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Pending</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo e($complaintStats['pending'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm">Resolved</p>
            <p class="text-3xl font-bold text-green-600 mt-2"><?php echo e($complaintStats['resolved'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <p class="text-gray-600 text-sm font-bold text-red-600">OVERDUE SLA</p>
            <p class="text-3xl font-bold text-red-600 mt-2"><?php echo e($complaintStats['overdue'] ?? 0); ?></p>
        </div>
    </div>

    <!-- Complaints Table -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Complaint List</h3>
        
        <div class="overflow-x-auto">
            <?php if($complaintsList->count() > 0): ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">ID</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Complainant</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Category</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">SLA Days</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $complaintsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $complaint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $daysRemaining = $complaint->registered_at->addDays($complaint->sla_days)->diffInDays(now());
                                $isOverdue = $daysRemaining < 0;
                            ?>
                            <tr class="border-b hover:bg-gray-50 <?php echo e($isOverdue ? 'bg-red-50' : ''); ?>">
                                <td class="px-6 py-4 font-mono">#<?php echo e($complaint->id); ?></td>
                                <td class="px-6 py-4"><?php echo e($complaint->candidate->name); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $complaint->category))); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                        <?php if($complaint->status === 'resolved'): ?> bg-green-100 text-green-800
                                        <?php elseif($complaint->status === 'in_progress'): ?> bg-blue-100 text-blue-800
                                        <?php else: ?> bg-yellow-100 text-yellow-800
                                        <?php endif; ?>
                                    ">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $complaint->status))); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold <?php echo e($isOverdue ? 'text-red-600 bg-red-100 px-2 py-1 rounded' : 'text-gray-700'); ?>">
                                        <?php echo e(abs($daysRemaining)); ?> <?php echo e($isOverdue ? 'OVERDUE' : 'days'); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo e(route('complaints.show', $complaint->id)); ?>" 
                                       class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                
                <div class="mt-4">
                    <?php echo e($complaintsList->links()); ?>

                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">No complaints found</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/iihsedup/oep.jaamiah.com/resources/views/dashboard/tabs/complaints.blade.php ENDPATH**/ ?>