<?php
$row = $row ?? [];
$dateValue = static function (mixed $value): string {
    if (!$value) {
        return date('Y-m-d\TH:i');
    }

    $time = strtotime((string) $value);

    return $time ? date('Y-m-d\TH:i', $time) : date('Y-m-d\TH:i');
};
$checked = static fn (mixed $value): string => in_array(strtolower((string) $value), ['1', 'true', 't', 'yes'], true) ? 'checked' : '';
$categoryOptions = [
    'tin-tuc' => 'Tin tức',
    'thong-bao' => 'Thông báo',
    'su-kien' => 'Sự kiện',
    'tinh-nang' => 'Tính năng',
    'huong-dan' => 'Hướng dẫn',
];
$statusOptions = [
    'published' => 'Đã đăng',
    'draft' => 'Bản nháp',
    'hidden' => 'Ẩn',
];
ob_start();
?>
<form class="panel form admin-form-screen admin-post-form" method="post" action="<?= e($actionUrl ?? '/admin/posts/save') ?>" enctype="multipart/form-data">
    <div class="admin-list-head">
        <div>
            <h2><?= e($heading ?? 'Tin tức') ?></h2>
            <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
        </div>
        <a class="btn secondary" href="<?= e($backUrl ?? '/admin/posts') ?>">Quay lại</a>
    </div>

    <input type="hidden" name="id" value="<?= e((string) ($row['id'] ?? '')) ?>">

    <label>
        Tiêu đề
        <input name="title" value="<?= e((string) ($row['title'] ?? '')) ?>" required>
    </label>

    <label>
        Slug
        <input name="slug" value="<?= e((string) ($row['slug'] ?? '')) ?>" placeholder="Bỏ trống để tự tạo">
    </label>

    <div class="admin-form-grid">
        <label>
            Danh mục
            <select name="category">
                <?php foreach ($categoryOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= (string) ($row['category'] ?? 'tin-tuc') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Trạng thái
            <select name="status">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= (string) ($row['status'] ?? 'published') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Ngày đăng
            <input name="published_at" type="datetime-local" value="<?= e($dateValue($row['published_at'] ?? null)) ?>">
        </label>

        <label>
            Index
            <input name="sort_order" type="number" min="0" step="1" value="<?= e((string) ($row['sort_order'] ?? 0)) ?>">
        </label>
    </div>

    <label>
        Tóm tắt
        <textarea name="summary" rows="3"><?= e((string) ($row['summary'] ?? '')) ?></textarea>
    </label>

    <label>
        Ảnh đại diện
        <input name="image_url" value="<?= e((string) ($row['image_url'] ?? '')) ?>" placeholder="/uploads/news/example.webp">
    </label>

    <label>
        Upload anh local
        <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
        <small class="admin-field-hint">Neu chon anh local, he thong se tu dong dung anh nay lam anh dai dien.</small>
    </label>

    <?php if (!empty($row['image_url'])): ?>
        <img class="admin-preview" src="<?= e((string) $row['image_url']) ?>" alt="Ảnh đại diện">
    <?php endif; ?>

    <label>
        Nội dung
        <textarea id="post-content-editor" name="content" rows="18"><?= e((string) ($row['content'] ?? '')) ?></textarea>
    </label>

    <label class="admin-inline">
        <input type="checkbox" name="is_featured" <?= $checked($row['is_featured'] ?? false) ?>>
        Nổi bật
    </label>

    <button class="btn primary" type="submit">Lưu tin tức</button>
</form>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.9/build/jodit.min.css">
<script src="https://cdn.jsdelivr.net/npm/jodit@3.24.9/build/jodit.min.js"></script>
<script>
    const contentEditor = document.querySelector('#post-content-editor');
    let htmlEditor = null;

    if (contentEditor && window.Jodit) {
        htmlEditor = Jodit.make(contentEditor, {
            height: 420,
            language: 'vi',
            enter: 'p',
            defaultMode: Jodit.MODE_WYSIWYG,
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            saveModeInCookie: false,
            beautifyHTML: true,
            removeButtons: ['about'],
            buttons: [
                'source', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'superscript', 'subscript', 'eraser', '|',
                'ul', 'ol', 'outdent', 'indent', '|',
                'paragraph', 'font', 'fontsize', 'brush', '|',
                'left', 'center', 'right', 'justify', '|',
                'link', 'image', 'video', 'table', '|',
                'hr', 'symbol', 'fullsize', 'preview', '|',
                'undo', 'redo'
            ],
            cleanHTML: {
                removeEmptyElements: false,
                fillEmptyParagraph: false,
                replaceNBSP: false
            },
            events: {
                change: () => {
                    contentEditor.value = htmlEditor.value;
                }
            }
        });
    }

    document.querySelector('.admin-post-form')?.addEventListener('submit', () => {
        if (htmlEditor) {
            contentEditor.value = htmlEditor.value;
        }
    });
</script>
