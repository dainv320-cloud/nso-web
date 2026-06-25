<?php
$stats = $stats ?? [];
$chart = $chart ?? ['labels' => [], 'users' => [], 'payments' => []];
$labels = is_array($chart['labels'] ?? null) ? $chart['labels'] : [];
$userValues = is_array($chart['users'] ?? null) ? $chart['users'] : [];
$paymentValues = is_array($chart['payments'] ?? null) ? $chart['payments'] : [];
$pointCount = max(count($labels), 1);
$maxValue = max(array_merge([1], $userValues, $paymentValues));
$chartWidth = 760.0;
$chartHeight = 280.0;
$paddingLeft = 36.0;
$paddingRight = 16.0;
$paddingTop = 20.0;
$paddingBottom = 36.0;
$plotWidth = $chartWidth - $paddingLeft - $paddingRight;
$plotHeight = $chartHeight - $paddingTop - $paddingBottom;
$xStep = $pointCount > 1 ? $plotWidth / ($pointCount - 1) : 0.0;
$yFor = static function (int $value) use ($paddingTop, $plotHeight, $maxValue): float {
    return $paddingTop + $plotHeight - (($value / $maxValue) * $plotHeight);
};
$buildPolyline = static function (array $values) use ($paddingLeft, $xStep, $yFor): string {
    $points = [];

    foreach ($values as $index => $value) {
        $points[] = round($paddingLeft + ($xStep * $index), 2) . ',' . round($yFor((int) $value), 2);
    }

    return implode(' ', $points);
};
$userPolyline = $buildPolyline($userValues);
$paymentPolyline = $buildPolyline($paymentValues);
ob_start();
?>
<section class="panel">
    <h2>Tong quan</h2>
    <div class="admin-stats">
        <article>
            <span>Total user</span>
            <strong><?= number_format((int) ($stats['users'] ?? 0)) ?></strong>
        </article>
        <article>
            <span>Total payment</span>
            <strong><?= number_format((int) ($stats['payments'] ?? 0)) ?></strong>
        </article>
        <article>
            <span>Total post</span>
            <strong><?= number_format((int) ($stats['posts'] ?? 0)) ?></strong>
        </article>
    </div>
</section>

<section class="panel">
    <div class="admin-list-head">
        <div>
            <h2>Thong ke theo ngay</h2>
            <p>User dang ky moi va payment moi trong 14 ngay gan nhat.</p>
        </div>
        <div class="admin-actions">
            <span class="btn secondary" style="cursor:default">User moi</span>
            <span class="btn primary" style="cursor:default">Payment moi</span>
        </div>
    </div>

    <svg viewBox="0 0 <?= e((string) $chartWidth) ?> <?= e((string) $chartHeight) ?>" width="100%" role="img" aria-label="Bieu do user moi va payment moi theo ngay">
        <line x1="<?= e((string) $paddingLeft) ?>" y1="<?= e((string) ($paddingTop + $plotHeight)) ?>" x2="<?= e((string) ($paddingLeft + $plotWidth)) ?>" y2="<?= e((string) ($paddingTop + $plotHeight)) ?>" stroke="#d1d5db" stroke-width="1" />
        <line x1="<?= e((string) $paddingLeft) ?>" y1="<?= e((string) $paddingTop) ?>" x2="<?= e((string) $paddingLeft) ?>" y2="<?= e((string) ($paddingTop + $plotHeight)) ?>" stroke="#d1d5db" stroke-width="1" />

        <?php foreach ([0, 0.25, 0.5, 0.75, 1] as $ratio): ?>
            <?php
            $gridY = $paddingTop + ($plotHeight * (1 - $ratio));
            $labelValue = (int) round($maxValue * $ratio);
            ?>
            <line x1="<?= e((string) $paddingLeft) ?>" y1="<?= e((string) $gridY) ?>" x2="<?= e((string) ($paddingLeft + $plotWidth)) ?>" y2="<?= e((string) $gridY) ?>" stroke="#eef2f7" stroke-width="1" />
            <text x="4" y="<?= e((string) ($gridY + 4)) ?>" font-size="12" fill="#6b7280"><?= e((string) $labelValue) ?></text>
        <?php endforeach; ?>

        <?php if ($userPolyline !== ''): ?>
            <polyline fill="none" stroke="#2563eb" stroke-width="3" points="<?= e($userPolyline) ?>" />
        <?php endif; ?>
        <?php if ($paymentPolyline !== ''): ?>
            <polyline fill="none" stroke="#16a34a" stroke-width="3" points="<?= e($paymentPolyline) ?>" />
        <?php endif; ?>

        <?php foreach ($labels as $index => $label): ?>
            <?php $x = $paddingLeft + ($xStep * $index); ?>
            <text x="<?= e((string) $x) ?>" y="<?= e((string) ($paddingTop + $plotHeight + 20)) ?>" text-anchor="middle" font-size="11" fill="#6b7280"><?= e((string) $label) ?></text>
        <?php endforeach; ?>

        <?php foreach ($userValues as $index => $value): ?>
            <?php
            $x = $paddingLeft + ($xStep * $index);
            $y = $yFor((int) $value);
            ?>
            <circle cx="<?= e((string) $x) ?>" cy="<?= e((string) $y) ?>" r="4" fill="#2563eb" />
        <?php endforeach; ?>

        <?php foreach ($paymentValues as $index => $value): ?>
            <?php
            $x = $paddingLeft + ($xStep * $index);
            $y = $yFor((int) $value);
            ?>
            <circle cx="<?= e((string) $x) ?>" cy="<?= e((string) $y) ?>" r="4" fill="#16a34a" />
        <?php endforeach; ?>
    </svg>
</section>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>
