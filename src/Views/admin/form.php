<?php
$row = $row ?? [];
$fields = $fields ?? [];
$hasCkeditor = false;
$checked = static fn (mixed $value): string => in_array(strtolower((string) $value), ['1', 'true', 't', 'yes'], true) ? 'checked' : '';
$dateValue = static function (mixed $value): string {
    if (!$value) {
        return date('Y-m-d\TH:i');
    }

    $time = strtotime((string) $value);

    return $time ? date('Y-m-d\TH:i', $time) : date('Y-m-d\TH:i');
};
ob_start();
?>
<form class="panel form admin-form-screen" method="post" action="<?= e($actionUrl ?? '') ?>">
    <div class="admin-list-head">
        <div>
            <h2><?= e($heading ?? 'Sửa') ?></h2>
            <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
        </div>
        <a class="btn secondary" href="<?= e($backUrl ?? '/admin') ?>">Quay lại</a>
    </div>

    <input type="hidden" name="id" value="<?= e((string) ($row['id'] ?? '')) ?>">

    <?php foreach ($fields as $field): ?>
        <?php
        $name = $field['name'];
        $type = $field['type'] ?? 'text';
        $value = $row[$name] ?? ($field['default'] ?? '');
        ?>

        <?php if ($type === 'checkbox'): ?>
            <label class="admin-inline">
                <input type="checkbox" name="<?= e($name) ?>" <?= $checked($value) ?: (!empty($field['checked']) ? 'checked' : '') ?>>
                <?= e($field['label']) ?>
            </label>
        <?php elseif ($type === 'textarea'): ?>
            <?php $hasCkeditor = $hasCkeditor || (($field['editor'] ?? '') === 'ckeditor'); ?>
            <label><?= e($field['label']) ?>
                <textarea
                    name="<?= e($name) ?>"
                    rows="<?= e((string) ($field['rows'] ?? 4)) ?>"
                    <?= (($field['editor'] ?? '') === 'ckeditor') ? 'data-ckeditor="classic"' : '' ?>
                ><?= e((string) $value) ?></textarea>
            </label>
        <?php elseif ($type === 'select'): ?>
            <label><?= e($field['label']) ?>
                <select name="<?= e($name) ?>">
                    <?php foreach (($field['options'] ?? []) as $option): ?>
                        <?php
                        $optionValue = is_array($option) ? (string) ($option['value'] ?? '') : (string) $option;
                        $optionLabel = is_array($option) ? (string) ($option['label'] ?? $optionValue) : (string) $option;
                        ?>
                        <option value="<?= e($optionValue) ?>" <?= (string) $value === $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php elseif ($type === 'datetime-local'): ?>
            <label><?= e($field['label']) ?>
                <input name="<?= e($name) ?>" type="datetime-local" value="<?= e($dateValue($value)) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
            </label>
        <?php else: ?>
            <label><?= e($field['label']) ?>
                <input
                    name="<?= e($name) ?>"
                    type="<?= e($type) ?>"
                    value="<?= $type === 'password' ? '' : e((string) $value) ?>"
                    <?= !empty($field['required']) ? 'required' : '' ?>
                    <?= isset($field['min']) ? 'min="' . e((string) $field['min']) . '"' : '' ?>
                    <?= isset($field['step']) ? 'step="' . e((string) $field['step']) . '"' : '' ?>
                >
            </label>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!empty($row['image_url'])): ?>
        <img class="admin-preview" src="<?= e($row['image_url']) ?>" alt="Ảnh xem trước">
    <?php endif; ?>

    <button class="btn primary" type="submit">Lưu</button>
</form>
<?php
$adminContent = ob_get_clean();
require __DIR__ . '/partials/shell.php';
?>

<?php if ($hasCkeditor): ?>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        class AdminUploadAdapter {
            constructor(loader) {
                this.loader = loader;
                this.xhr = null;
            }

            upload() {
                return this.loader.file.then((file) => new Promise((resolve, reject) => {
                    this.xhr = new XMLHttpRequest();
                    this.xhr.open('POST', '/admin/uploads/image', true);
                    this.xhr.responseType = 'json';

                    this.xhr.addEventListener('error', () => reject('Không thể upload ảnh.'));
                    this.xhr.addEventListener('abort', () => reject('Upload ảnh đã bị hủy.'));
                    this.xhr.addEventListener('load', () => {
                        const response = this.xhr.response;

                        if (!response || response.error) {
                            reject(response?.error?.message || 'Upload ảnh thất bại.');
                            return;
                        }

                        resolve({ default: response.url });
                    });

                    if (this.xhr.upload) {
                        this.xhr.upload.addEventListener('progress', (event) => {
                            if (event.lengthComputable) {
                                this.loader.uploadTotal = event.total;
                                this.loader.uploaded = event.loaded;
                            }
                        });
                    }

                    const data = new FormData();
                    data.append('upload', file);
                    this.xhr.send(data);
                }));
            }

            abort() {
                if (this.xhr) {
                    this.xhr.abort();
                }
            }
        }

        const adminUploadAdapterPlugin = (editor) => {
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new AdminUploadAdapter(loader);
        };

        document.querySelectorAll('[data-ckeditor="classic"]').forEach((textarea) => {
            ClassicEditor
                .create(textarea, {
                    extraPlugins: [adminUploadAdapterPlugin],
                    toolbar: {
                        items: [
                            'heading', '|',
                            'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|',
                            'blockQuote', 'insertTable', 'imageUpload', 'mediaEmbed', '|',
                            'undo', 'redo'
                        ]
                    },
                    image: {
                        toolbar: [
                            'imageTextAlternative',
                            'toggleImageCaption',
                            'imageStyle:inline',
                            'imageStyle:block',
                            'imageStyle:side'
                        ]
                    }
                })
                .catch((error) => console.error(error));
        });
    </script>
<?php endif; ?>
