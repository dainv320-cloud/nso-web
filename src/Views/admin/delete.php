<?php ob_start(); ?>
<section class="panel admin-delete">
    <h2><?= e($heading ?? 'Xác nhận xóa') ?></h2>
    <p><?= e($message ?? 'Bạn có chắc chắn muốn xóa bản ghi này?') ?></p>

    <?php if (!empty($row)): ?>
        <dl class="payment-qr-info inline">
            <?php foreach (($summary ?? []) as $label => $key): ?>
                <div>
                    <dt><?= e($label) ?></dt>
                    <dd><?= e((string) ($row[$key] ?? '')) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>

    <form class="admin-delete-actions" method="post" action="<?= e($actionUrl ?? '') ?>">
        <input type="hidden" name="id" value="<?= e((string) ($row['id'] ?? '')) ?>">
        <a class="btn secondary" href="<?= e($backUrl ?? '/admin') ?>">Hủy</a>
        <button class="btn primary" type="submit">Xóa</button>
    </form>
</section>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>
