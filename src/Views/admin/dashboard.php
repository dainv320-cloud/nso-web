<?php ob_start(); ?>
<section class="panel">
    <h2>Tổng quan</h2>
    <div class="admin-stats">
        <?php foreach (($stats ?? []) as $label => $value): ?>
            <article>
                <span><?= e((string) $label) ?></span>
                <strong><?= number_format((int) $value) ?></strong>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>
