<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Educational Guidance - BRANACA</title>
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
        .severity-informational { background-color: #d1ecf1; color: #0c5460; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
    <div class="text-center mb-5">
        <h1 class="display-5">📘 Educational Guidance</h1> 
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php $__currentLoopData = $guidance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
            $severity = strtolower($item['severity']);
            $badgeClass = match($severity) {
                'low' => 'severity-low',
                'medium' => 'severity-medium',
                'high' => 'severity-high',
                default => 'severity-informational'
            };
            ?>
            <div class="col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                <h5 class="card-title"><?php echo $item['title']; ?></h5>
                <span class="severity-badge <?php echo e($badgeClass); ?>">
                    <?php echo ucfirst($severity); ?>

                </span>
                <p class="mt-3"><strong>Explanation:</strong><br><?php echo $item['explanation']; ?></p>
                <p><strong>Recommendation:</strong><br><?php echo $item['recommendation']; ?></p>

                <?php if(!empty($item['owasp_reference'])): ?>
                    <p><strong> OWASP Reference:</strong><br>
                    <a href="<?php echo e($item['owasp_reference']); ?>" target="_blank" class="text-decoration-underline">
                        Learn more about this issue
                    </a>
                    </p>
                <?php endif; ?>

                <?php if(!empty($item['custom_explanation'])): ?>
                    <p><strong>🛠️ Nikto Explanation:</strong><br><?php echo $item['custom_explanation']; ?></p>
                <?php endif; ?>

                <form class="ai-comment-form mt-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="title" value="<?php echo e($item['title']); ?>">
                    <input type="hidden" name="severity" value="<?php echo e($item['severity']); ?>">
                    <input type="hidden" name="explanation" value="<?php echo e($item['explanation']); ?>">
                    <input type="hidden" name="recommendation" value="<?php echo e($item['recommendation']); ?>">
                    <button type="button" class="btn btn-sm btn-outline-secondary ai-comment-button">💡 AI Comment</button>
                </form>

                <div class="ai-comment-response alert alert-info mt-2 d-none">
                    <strong>AI says:</strong> <span class="ai-comment-text"></span>
                </div>
                </div>
            </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="text-center mt-5">
        <a href="<?php echo url('/'); ?>" class="btn btn-outline-primary">🔁 Start New Scan</a>
    </div>
</div>

<script>
    $(document).on('click', '.ai-comment-button', function () {
        const form = $(this).closest('.ai-comment-form');
        const responseContainer = form.siblings('.ai-comment-response');
        const aiCommentText = responseContainer.find('.ai-comment-text');

        $.ajax({
            url: "<?php echo e(route('educational.aiComment')); ?>",
            method: "POST",
            data: form.serialize(),
            success: function (response) {
                aiCommentText.text(response.message || 'No response from AI.');
                responseContainer.removeClass('d-none');
            },
            error: function () {
                aiCommentText.text('An error occurred while processing your request.');
                responseContainer.removeClass('d-none');
            }
        });
    });
</script>
</body>
</html>
<?php /**PATH /home/ssd2mtc/bracana-svet-laravel/backend/resources/views/educational.blade.php ENDPATH**/ ?>