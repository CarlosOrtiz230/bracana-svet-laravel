<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scan Results - BRANACA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem;
        }
        .severity-badge {
            font-size: 0.9rem;
            padding: 0.35em 0.75em;
            border-radius: 0.5rem;
        }
        .severity-low { background-color: #d4edda; color: #155724; }
        .severity-medium { background-color: #fff3cd; color: #856404; }
        .severity-high { background-color: #f8d7da; color: #721c24; }
        .severity-unknown { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center mb-4">
        <h1 class="display-5">🔍 Scan Results</h1>
        <?php if(!empty($tool)): ?>
            <h5 class="text-muted">Tool Used: <strong><?php echo e(strtoupper($tool)); ?></strong></h5>
        <?php endif; ?>
        <?php if(isset($total_score)): ?>
            <h4 class="text-success">💡 Risk Score: <strong><?php echo e($total_score); ?></strong></h4>
        <?php endif; ?>
         


    </div>

    <?php if(is_array($results) && count($results)): ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
            
                    // Grab severity from riskdesc (e.g., "Medium (High)") and fallback if missing
                     $raw = $item['riskdesc'] ?? $item['risk'] ?? $item['severity'] ?? 'unknown';

                   // Extract first word, lowercase it
                    $severity = strtolower(trim(strtok($raw, ' ')));
                    $badgeClass = match($severity) {
                        'low' => 'severity-low',
                        'medium' => 'severity-medium',
                        'high', 'very high' => 'severity-high',
                        default => 'severity-unknown'
                    };
                ?>
                <div class="col">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo e($item['alert'] ?? $item['message'] ?? 'No alert title'); ?>

                            </h5>
                            <span class="severity-badge <?php echo e($badgeClass); ?>">
                                <?php echo e(ucfirst($severity)); ?>

                            </span>
                            <?php if(!empty($item['description'])): ?>
                                <p class="mt-3"><?php echo e($item['description']); ?></p>
                            <?php endif; ?>
                            <?php if(!empty($item['uri'])): ?>
                                <p class="text-muted mb-0"><strong>URI:</strong> <?php echo e($item['uri']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No issues found or the report format is invalid.
        </div>
    <?php endif; ?>

    <a href="<?php echo e(route('educate.fromStorage', ['tool' => $tool, 'id' => $scan_id])); ?>" class="btn btn-outline-info mt-3">
        📘 View Educational Summary
    </a>
    

    <a href="<?php echo e(route('scan.raw.json', ['tool' => $tool, 'id' => $scan_id])); ?>" target="_blank" class="btn btn-outline-secondary mt-2">
        🧾 View Raw JSON
    </a>
</div>
</body>
</html>
<?php /**PATH /home/ssd2mtc/bracana-svet-laravel/backend/resources/views/results.blade.php ENDPATH**/ ?>