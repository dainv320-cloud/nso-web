<?php
$rows = $rows ?? [];
$columns = $columns ?? [];
$actions = $actions ?? [];
$paymentToggleUrl = $paymentToggleUrl ?? null;
$paymentEnabled = (bool) ($paymentEnabled ?? false);
ob_start();
?>
<section class="panel">
    <div class="admin-list-head">
        <div>
            <h2><?= e($heading ?? 'Danh sách') ?></h2>
            <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
        </div>
        <div class="admin-actions">
            <?php if (!empty($paymentToggleUrl)): ?>
                <form method="post" action="<?= e($paymentToggleUrl) ?>">
                    <input type="hidden" name="enabled" value="<?= $paymentEnabled ? '0' : '1' ?>">
                    <button class="btn <?= $paymentEnabled ? 'secondary' : 'primary' ?>" type="submit">
                        <?= $paymentEnabled ? 'Tắt nạp tiền' : 'Bật nạp tiền' ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!empty($createUrl)): ?>
                <a class="btn primary" href="<?= e($createUrl) ?>">Thêm mới</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table>
            <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th><?= e($column['label']) ?></th>
                <?php endforeach; ?>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <?php $value = $row[$column['key']] ?? ''; ?>
                        <td>
                            <?php if (($column['format'] ?? '') === 'money'): ?>
                                <?= number_format((float) $value) ?>
                            <?php elseif (($column['format'] ?? '') === 'bool'): ?>
                                <?= in_array(strtolower((string) $value), ['1', 'true', 't', 'yes'], true) ? 'Có' : 'Không' ?>
                            <?php elseif (($column['format'] ?? '') === 'datetime'): ?>
                                <?php
                                $time = strtotime((string) $value);
                                echo $time ? e(date('d/m/Y H:i', $time)) : '-';
                                ?>
                            <?php elseif (($column['format'] ?? '') === 'admin_role'): ?>
                                <?= match ((int) $value) {
                                    0 => 'User',
                                    2 => 'Cộng tác viên',
                                    default => 'Admin',
                                } ?>
                            <?php else: ?>
                                <?= e((string) $value) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="admin-actions">
                        <?php if (!empty($actions['edit'])): ?>
                            <a class="btn secondary" href="<?= e(sprintf($actions['edit'], $row['id'])) ?>">Sửa</a>
                        <?php endif; ?>
                        <?php if (!empty($actions['delete'])): ?>
                            <a class="btn secondary" href="<?= e(sprintf($actions['delete'], $row['id'])) ?>">Xóa</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>
