<?php $__env->startSection('title', 'Trade Management'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Trade Management</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo e(route('trades.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Trade
            </a>
        </div>
    </div>

    <?php if($trades->count()): ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Trade Name</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Duration (weeks)</th>
                            <th>Candidates</th>
                            <th>Batches</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $trades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($trade->name); ?></td>
                                <td><span class="badge badge-info"><?php echo e($trade->code); ?></span></td>
                                <td><?php echo e($trade->category); ?></td>
                                <td><?php echo e($trade->duration_weeks); ?></td>
                                <td><span class="badge badge-primary"><?php echo e($trade->candidates_count); ?></span></td>
                                <td><span class="badge badge-success"><?php echo e($trade->batches_count); ?></span></td>
                                <td>
                                    <a href="<?php echo e(route('trades.show', $trade->id)); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo e(route('trades.edit', $trade->id)); ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?php echo e(route('trades.destroy', $trade->id)); ?>" method="POST" class="d-inline">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete trade?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            <?php echo e($trades->links()); ?>

        </div>
    <?php else: ?>
        <div class="alert alert-info">No trades found.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/iihsedup/oep.jaamiah.com/resources/views/admin/trades/index.blade.php ENDPATH**/ ?>