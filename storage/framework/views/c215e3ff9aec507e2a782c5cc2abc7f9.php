<?php $__env->startSection('title', 'OEPs Management'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>OEPs Management</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo e(route('oeps.create')); ?>" class="btn btn-primary">+ Add New OEP</a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Country</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $oeps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $oep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($oep->id); ?></td>
                            <td><?php echo e($oep->name); ?></td>
                            <td><span class="badge badge-info"><?php echo e($oep->code ?? 'N/A'); ?></span></td>
                            <td><?php echo e($oep->country ?? 'N/A'); ?></td>
                            <td><?php echo e($oep->contact_person ?? 'N/A'); ?></td>
                            <td>
                                <?php if($oep->is_active): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo e(route('oeps.show', $oep->id)); ?>" class="btn btn-sm btn-info">View</a>
                                <a href="<?php echo e(route('oeps.edit', $oep->id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="<?php echo e(route('oeps.destroy', $oep->id)); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No OEPs found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        <?php echo e($oeps->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/iihsedup/oep.jaamiah.com/resources/views/admin/oeps/index.blade.php ENDPATH**/ ?>