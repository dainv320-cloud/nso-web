<?php
$rows = $rows ?? [];
$columns = $columns ?? [];
$actions = $actions ?? [];
$hasActions = !empty($actions['edit']) || !empty($actions['delete']);
$paymentToggleUrl = $paymentToggleUrl ?? null;
$paymentEnabled = (bool) ($paymentEnabled ?? false);
$featureToggles = $featureToggles ?? [];
$searchUrl = $searchUrl ?? null;
$searchValue = (string) ($searchValue ?? '');
$searchPlaceholder = (string) ($searchPlaceholder ?? 'Tìm kiếm');
$filters = $filters ?? [];
$pagination = $pagination ?? ['currentPage' => 1, 'totalPages' => 1, 'totalRows' => count($rows), 'pageSize' => count($rows), 'query' => []];
$paginationBaseUrl = (string) ($searchUrl ?? (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: ''));
$paginationQuery = is_array($pagination['query'] ?? null) ? $pagination['query'] : [];
$pageUrl = static function (int $page) use ($paginationBaseUrl, $paginationQuery): string {
    $query = $paginationQuery;
    $query['page'] = $page;

    return $paginationBaseUrl . '?' . http_build_query($query);
};
ob_start();
?>
<section class="panel">
    <div class="admin-list-head">
        <div>
            <h2><?= e($heading ?? 'Danh sách') ?></h2>
            <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
            <?php if (!empty($searchUrl)): ?>
                <form class="admin-search" method="get" action="<?= e($searchUrl) ?>">
                    <input type="text" name="q" value="<?= e($searchValue) ?>" placeholder="<?= e($searchPlaceholder) ?>">
                    <?php foreach ($filters as $filter): ?>
                        <select name="<?= e((string) ($filter['name'] ?? '')) ?>">
                            <option value=""><?= e((string) (($filter['label'] ?? 'Bộ lọc') . ': tất cả')) ?></option>
                            <?php foreach (($filter['options'] ?? []) as $option): ?>
                                <?php
                                $optionValue = (string) ($option['value'] ?? '');
                                $optionLabel = (string) ($option['label'] ?? $optionValue);
                                ?>
                                <option value="<?= e($optionValue) ?>" <?= (string) ($filter['value'] ?? '') === $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endforeach; ?>
                    <button class="btn secondary" type="submit">Tìm</button>
                    <?php
                    $hasActiveFilter = $searchValue !== '';
                    foreach ($filters as $filter) {
                        if ((string) ($filter['value'] ?? '') !== '') {
                            $hasActiveFilter = true;
                            break;
                        }
                    }
                    ?>
                    <?php if ($hasActiveFilter): ?>
                        <a class="btn secondary" href="<?= e($searchUrl) ?>">Bỏ lọc</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
        <div class="admin-actions">
            <?php foreach ($featureToggles as $toggle): ?>
                <form method="post" action="<?= e((string) ($toggle['url'] ?? '')) ?>">
                    <input type="hidden" name="enabled" value="<?= !empty($toggle['enabled']) ? '0' : '1' ?>">
                    <button class="btn <?= !empty($toggle['enabled']) ? 'secondary' : 'primary' ?>" type="submit">
                        <?= e((string) ($toggle['enabled'] ? ($toggle['disableLabel'] ?? 'Tắt') : ($toggle['enableLabel'] ?? 'Bật'))) ?>
                    </button>
                </form>
            <?php endforeach; ?>
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
                <?php if ($hasActions): ?><th></th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= count($columns) + ($hasActions ? 1 : 0) ?>">Không có dữ liệu.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <?php $value = $row[$column['key']] ?? ''; ?>
                            <td data-label="<?= e($column['label']) ?>">
                                <?php if (($column['format'] ?? '') === 'money'): ?>
                                    <?= number_format((float) $value, 0, ',', '.') ?>
                                <?php elseif (($column['format'] ?? '') === 'bool'): ?>
                                    <?= in_array(strtolower((string) $value), ['1', 'true', 't', 'yes'], true) ? 'Có' : 'Không' ?>
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
                                <?php elseif (($column['format'] ?? '') === 'feedback_type'): ?>
                                    <?= (string) $value === 'bug' ? 'Báo lỗi' : 'Đề xuất tính năng' ?>
                                <?php elseif (($column['format'] ?? '') === 'feedback_status'): ?>
                                    <?= match ((string) $value) {
                                        'done' => 'Đã xử lý',
                                        'reviewing' => 'Đang xem',
                                        default => 'Mới',
                                    } ?>
                                <?php else: ?>
                                    <?= e((string) $value) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php if ($hasActions): ?>
                            <td class="admin-actions" data-label="Thao tác">
                                <?php if (!empty($actions['edit'])): ?>
                                    <a class="btn secondary" href="<?= e(sprintf($actions['edit'], $row['id'])) ?>">Sửa</a>
                                <?php endif; ?>
                                <?php if (!empty($actions['delete'])): ?>
                                    <a class="btn secondary" href="<?= e(sprintf($actions['delete'], $row['id'])) ?>">Xóa</a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-list-head">
        <p>
            Trang <?= (int) ($pagination['currentPage'] ?? 1) ?>/<?= (int) ($pagination['totalPages'] ?? 1) ?>
            · <?= number_format((int) ($pagination['totalRows'] ?? 0)) ?> dòng
            · <?= number_format((int) ($pagination['pageSize'] ?? count($rows))) ?> dòng/trang
        </p>
        <?php if (($pagination['totalPages'] ?? 1) > 1): ?>
            <div class="admin-actions">
                <?php if (($pagination['currentPage'] ?? 1) > 1): ?>
                    <a class="btn secondary" href="<?= e($pageUrl((int) $pagination['currentPage'] - 1)) ?>">Prev</a>
                <?php endif; ?>
                <?php if (($pagination['currentPage'] ?? 1) < ($pagination['totalPages'] ?? 1)): ?>
                    <a class="btn secondary" href="<?= e($pageUrl((int) $pagination['currentPage'] + 1)) ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>
