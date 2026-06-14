<?php
$rows = $rows ?? [];
$columns = $columns ?? [];
$actions = $actions ?? [];
$hasActions = !empty($actions['edit']) || !empty($actions['delete']);
$paymentToggleUrl = $paymentToggleUrl ?? null;
$paymentEnabled = (bool) ($paymentEnabled ?? false);
$featureToggles = $featureToggles ?? [];
ob_start();
?>
<section class="panel">
    <div class="admin-list-head">
        <div>
            <h2><?= e($heading ?? 'Danh sach') ?></h2>
            <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
        </div>
        <div class="admin-actions">
            <?php foreach ($featureToggles as $toggle): ?>
                <form method="post" action="<?= e((string) ($toggle['url'] ?? '')) ?>">
                    <input type="hidden" name="enabled" value="<?= !empty($toggle['enabled']) ? '0' : '1' ?>">
                    <button class="btn <?= !empty($toggle['enabled']) ? 'secondary' : 'primary' ?>" type="submit">
                        <?= e((string) ($toggle['enabled'] ? ($toggle['disableLabel'] ?? 'Tat') : ($toggle['enableLabel'] ?? 'Bat'))) ?>
                    </button>
                </form>
            <?php endforeach; ?>
            <?php if (!empty($paymentToggleUrl)): ?>
                <form method="post" action="<?= e($paymentToggleUrl) ?>">
                    <input type="hidden" name="enabled" value="<?= $paymentEnabled ? '0' : '1' ?>">
                    <button class="btn <?= $paymentEnabled ? 'secondary' : 'primary' ?>" type="submit">
                        <?= $paymentEnabled ? 'Tat nap tien' : 'Bat nap tien' ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!empty($createUrl)): ?>
                <a class="btn primary" href="<?= e($createUrl) ?>">Them moi</a>
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
                <?php if ($hasActions): ?><th></th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <?php $value = $row[$column['key']] ?? ''; ?>
                        <td data-label="<?= e($column['label']) ?>">
                            <?php if (($column['format'] ?? '') === 'money'): ?>
                                <?= number_format((float) $value, 0, ',', '.') ?>
                            <?php elseif (($column['format'] ?? '') === 'bool'): ?>
                                <?= in_array(strtolower((string) $value), ['1', 'true', 't', 'yes'], true) ? 'Co' : 'Khong' ?>
                            <?php elseif (($column['format'] ?? '') === 'datetime'): ?>
                                <?php
                                $time = strtotime((string) $value);
                                echo $time ? e(date('d/m/Y H:i', $time)) : '-';
                                ?>
                            <?php elseif (($column['format'] ?? '') === 'user_status'): ?>
                                <?= match ((int) $value) {
                                    0 => 'Deactivate',
                                    2 => 'Block',
                                    default => 'Active',
                                } ?>
                            <?php elseif (($column['format'] ?? '') === 'admin_role'): ?>
                                <?= match ((int) $value) {
                                    0 => 'User',
                                    1 => 'CTV',
                                    99 => 'Admin',
                                    default => 'Admin',
                                } ?>
                            <?php else: ?>
                                <?= e((string) $value) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <?php if ($hasActions): ?>
                        <td class="admin-actions" data-label="Thao tac">
                            <?php if (!empty($actions['edit'])): ?>
                                <a class="btn secondary" href="<?= e(sprintf($actions['edit'], $row['id'])) ?>">Sua</a>
                            <?php endif; ?>
                            <?php if (!empty($actions['delete'])): ?>
                                <a class="btn secondary" href="<?= e(sprintf($actions['delete'], $row['id'])) ?>">Xoa</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
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
