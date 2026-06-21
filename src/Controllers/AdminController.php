<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Response;
use App\View;
use PDO;
use Throwable;

final class AdminController
{
    private const ROLE_ADMIN = 99;
    private const ROLE_COLLABORATOR = 1;
    private const ROLE_USER = 0;
    private const REGISTER_BONUS_AMOUNT = 5000000;
    private ?array $userTableSchema = null;

    public function login(): never
    {
        if ($this->currentAdmin()) {
            header('Location: /admin');
            exit;
        }

        View::render('admin-login', [
            'title' => 'Admin Login',
            'error' => null,
        ]);
    }

    public function submitLogin(): never
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            View::render('admin-login', [
                'title' => 'Admin Login',
                'error' => "Vui l\u{00F2}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n v\u{00E0} m\u{1EAD}t kh\u{1EA9}u admin.",
            ], 422);
        }

        try {
            $statement = Database::connection()->prepare($this->adminLoginQuery());
            $statement->execute(['username' => $username]);
            $account = $statement->fetch();

            if (
                !$account
                || !password_verify($password, (string) $account['password'])
                || !$this->isUserAccessible($account)
                || !$this->isAdminRole($account)
            ) {
                View::render('admin-login', [
                    'title' => 'Admin Login',
                    'error' => "T\u{00E0}i kho\u{1EA3}n admin ho\u{1EB7}c m\u{1EAD}t kh\u{1EA9}u kh\u{00F4}ng \u{0111}\u{00FA}ng.",
                ], 401);
            }
        } catch (Throwable) {
            View::render('admin-login', [
                'title' => 'Admin Login',
                'error' => "Kh\u{00F4}ng th\u{1EC3} \u{0111}\u{0103}ng nh\u{1EAD}p admin l\u{00FA}c n\u{00E0}y.",
            ], 500);
        }

        $_SESSION['user'] = [
            'id' => $account['id'],
            'username' => $account['username'],
            'display_name' => $account['username'],
            'login_at' => date('Y-m-d H:i:s'),
        ];
        $_SESSION['admin_login'] = true;
        $_SESSION['toast_success'] = "\u{0110}\u{0103}ng nh\u{1EAD}p admin th\u{00E0}nh c\u{00F4}ng!";

        header('Location: /admin');
        exit;
    }

    public function logout(): never
    {
        unset($_SESSION['admin_login']);
        header('Location: /admin/login');
        exit;
    }

    public function dashboard(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/dashboard', [
            'title' => 'Admin',
            'admin' => $admin,
            'section' => 'dashboard',
            'stats' => $this->dashboardStats(),
            'flash' => $this->pullFlash(),
        ]);
    }

    public function users(): never
    {
        $admin = $this->requireAdmin();
        $registerBonusEnabled = $this->registerBonusEnabled();
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $search !== ''
            ? $this->searchUsers($search)
            : $this->fetchAll('select id, username, name, email, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew from users order by id desc limit 200');

        View::render('admin/list', [
            'title' => 'Admin - Users',
            'admin' => $admin,
            'section' => 'users',
            'heading' => 'Danh sach user',
            'description' => 'Quan ly cac truong hien tai cua bang users. Thuong dang ky hien tai: ' . number_format(self::REGISTER_BONUS_AMOUNT, 0, ',', '.') . ' coin.',
            'createUrl' => '/admin/users/create',
            'searchUrl' => '/admin/users',
            'searchValue' => $search,
            'searchPlaceholder' => 'Tim theo username, ten hoac email',
            'featureToggles' => [[
                'url' => '/admin/users/register-bonus-toggle',
                'enabled' => $registerBonusEnabled,
                'enableLabel' => 'Bat thuong dang ky',
                'disableLabel' => 'Tat thuong dang ky',
            ]],
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Hiển thị'],
                ['key' => 'username', 'label' => 'User'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'is_active', 'label' => 'Kích hoạt', 'format' => 'bool'],
                ['key' => 'ban', 'label' => 'Ban', 'format' => 'bool'],
                ['key' => 'money', 'label' => 'Coin', 'format' => 'money'],
                ['key' => 'totalmoney', 'label' => 'Tổng nạp', 'format' => 'money'],
                ['key' => 'tongnapthang', 'label' => 'Nạp tháng', 'format' => 'money'],
                ['key' => 'money', 'label' => 'Coin', 'format' => 'money'],
                ['key' => 'type_admin', 'label' => 'Quyen', 'format' => 'admin_role'],
                ['key' => 'created_at', 'label' => 'Tạo lúc', 'format' => 'datetime'],
                ['key' => 'updated_at', 'label' => 'Cập nhật', 'format' => 'datetime'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'username', 'label' => 'Username'],
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'user_status'],
                ['key' => 'activated', 'label' => 'Activated', 'format' => 'bool'],
                ['key' => 'active', 'label' => 'Active', 'format' => 'bool'],
                ['key' => 'role', 'label' => 'Role', 'format' => 'admin_role'],
                ['key' => 'balance', 'label' => 'Balance', 'format' => 'money'],
                ['key' => 'tongnap', 'label' => 'Tong nap', 'format' => 'money'],
                ['key' => 'tongNapThang', 'label' => 'Tong nap thang', 'format' => 'money'],
                ['key' => 'tongNapTuan', 'label' => 'Tong nap tuan', 'format' => 'money'],
                ['key' => 'quanew', 'label' => 'Qua new', 'format' => 'money'],
            ],
            'actions' => ['edit' => '/admin/users/%s/edit', 'delete' => '/admin/users/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function toggleRegisterBonus(): never
    {
        $this->requireAdmin();
        $enabled = ($_POST['enabled'] ?? '') === '1';

        if (!$this->writeEnvValue('REGISTER_BONUS_ENABLED', $enabled ? 'true' : 'false')) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng th\u{1EC3} c\u{1EAD}p nh\u{1EAD}t file .env.", 'error');
        }

        $message = $enabled
            ? "\u{0110}\u{00E3} b\u{1EAD}t th\u{01B0}\u{1EDF}ng \u{0111}\u{0103}ng k\u{00FD} " . number_format(self::REGISTER_BONUS_AMOUNT, 0, ',', '.') . ' coin.'
            : "\u{0110}\u{00E3} t\u{1EAF}t th\u{01B0}\u{1EDF}ng \u{0111}\u{0103}ng k\u{00FD}.";

        $this->redirectWithFlash('/admin/users', $message);
    }

    public function userForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('users', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y user.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a user" : "Admin - Th\u{00EA}m user",
            'admin' => $admin,
            'section' => 'users',
            'heading' => $id ? "S\u{1EED}a user" : "Th\u{00EA}m user",
            'backUrl' => '/admin/users',
            'actionUrl' => '/admin/users/save',
            'row' => $row ?? ['status' => 1, 'activated' => 1, 'active' => 1, 'role' => self::ROLE_USER, 'balance' => 0, 'tongnap' => 0, 'tongNapThang' => 0, 'tongNapTuan' => 0, 'quanew' => 0],
            'fields' => [
                ['name' => 'username', 'label' => 'Username', 'required' => true],
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'password', 'label' => $id ? 'Password moi, bo trong neu khong doi' : 'Password', 'type' => 'password', 'required' => !$id],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->userStatusOptions()],
                ['name' => 'activated', 'label' => 'Activated', 'type' => 'checkbox', 'checked' => !$id],
                ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
                ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => $this->userRoleOptions()],
                ['name' => 'balance', 'label' => 'Balance', 'type' => 'money'],
                ['name' => 'tongnap', 'label' => 'Tong nap', 'type' => 'money'],
                ['name' => 'tongNapThang', 'label' => 'Tong nap thang', 'type' => 'money'],
                ['name' => 'tongNapTuan', 'label' => 'Tong nap tuan', 'type' => 'money'],
                ['name' => 'quanew', 'label' => 'Qua new', 'type' => 'money'],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmUserDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('users', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y user.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a user",
            'admin' => $admin,
            'section' => 'users',
            'heading' => "X\u{00F3}a user",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a user n\u{00E0}y?",
            'row' => $row,
            'summary' => ['ID' => 'id', 'Hiển thị' => 'name', 'User' => 'username', 'Email' => 'email'],
            'summary' => ['ID' => 'id', 'Username' => 'username', 'Name' => 'name', 'Email' => 'email'],
            'actionUrl' => '/admin/users/delete',
            'backUrl' => '/admin/users',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveUser(): never
    {
        $admin = $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $this->redirectWithFlash($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create', "T\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}.", 'error');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithFlash($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create', "Email kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}.", 'error');
        }

        if ($id === 0 && strlen($password) < 6) {
            $this->redirectWithFlash('/admin/users/create', "M\u{1EAD}t kh\u{1EA9}u user m\u{1EDB}i t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1}.", 'error');
        }

        $data = [
            'name' => trim((string) ($_POST['name'] ?? $username)) ?: $username,
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'status' => $this->userStatusFromRequest(),
            'activated' => isset($_POST['activated']) ? 1 : 0,
            'active' => isset($_POST['active']) ? 1 : 0,
            'role' => $this->userRoleFromRequest($id),
            'balance' => $this->moneyIntFromRequest('balance'),
            'tongnap' => $this->moneyIntFromRequest('tongnap'),
            'tongNapThang' => $this->moneyIntFromRequest('tongNapThang'),
            'tongNapTuan' => $this->moneyIntFromRequest('tongNapTuan'),
            'quanew' => $this->moneyIntFromRequest('quanew'),
        ];

        try {
            if ($id > 0) {
                $sql = 'update users set name = :name, username = :username, email = :email, status = :status, activated = :activated, active = :active, role = :role, balance = :balance, tongnap = :tongnap, tongNapThang = :tongNapThang, tongNapTuan = :tongNapTuan, quanew = :quanew';

                if ($password !== '') {
                    if (strlen($password) < 6) {
                        $this->redirectWithFlash('/admin/users/' . $id . '/edit', "M\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1}.", 'error');
                    }

                    $sql .= ', password = :password';
                    $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                }

                $sql .= ', updated_at = now() where id = :id';
                $data['id'] = $id;
                Database::connection()->prepare($sql)->execute($data);
            } else {
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                Database::connection()->prepare(
                    'insert into users (name, username, email, password, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, created_at, updated_at)
                     values (:name, :username, :email, :password, :status, :activated, :active, :role, :balance, :tongnap, :tongNapThang, :tongNapTuan, :quanew, now(), now())'
                )->execute($data);
            }
        } catch (Throwable) {
            $this->redirectWithFlash($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create', "Kh\u{00F4}ng th\u{1EC3} l\u{01B0}u user. T\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p/email c\u{00F3} th\u{1EC3} \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.", 'error');
        }

        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "\u{0110}\u{00E3} l\u{01B0}u user."];
        header('Location: /admin/users');
        exit;
    }

    public function deleteUser(): never
    {
        $admin = $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $id === (int) $admin['id']) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng th\u{1EC3} x\u{00F3}a t\u{00E0}i kho\u{1EA3}n n\u{00E0}y.", 'error');
        }

        Database::connection()->prepare('delete from users where id = :id')->execute(['id' => $id]);
        $this->redirectWithFlash('/admin/users', "\u{0110}\u{00E3} x\u{00F3}a user.");
    }

    public function posts(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => "Admin - Tin t\u{1EE9}c",
            'admin' => $admin,
            'section' => 'posts',
            'heading' => "Danh s\u{00E1}ch tin t\u{1EE9}c",
            'createUrl' => '/admin/posts/create',
            'rows' => $this->fetchAll('select * from posts order by published_at desc, id desc limit 200'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'title', 'label' => "Ti\u{00EA}u \u{0111}\u{1EC1}"],
                ['key' => 'category', 'label' => "Danh m\u{1EE5}c"],
                ['key' => 'status', 'label' => "Tr\u{1EA1}ng th\u{00E1}i"],
                ['key' => 'published_at', 'label' => "Ng\u{00E0}y \u{0111}\u{0103}ng"],
            ],
            'actions' => $this->isCollaborator($admin)
                ? ['edit' => '/admin/posts/%s/edit']
                : ['edit' => '/admin/posts/%s/edit', 'delete' => '/admin/posts/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function postForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('posts', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/posts', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y tin t\u{1EE9}c.", 'error');
        }

        View::render('admin/post-form', [
            'title' => $id ? "Admin - S\u{1EED}a tin t\u{1EE9}c" : "Admin - Th\u{00EA}m tin t\u{1EE9}c",
            'admin' => $admin,
            'section' => 'posts',
            'heading' => $id ? "S\u{1EED}a tin t\u{1EE9}c" : "Th\u{00EA}m tin t\u{1EE9}c",
            'description' => "So\u{1EA1}n n\u{1ED9}i dung b\u{00E0}i vi\u{1EBF}t v\u{00E0} ch\u{00E8}n \u{1EA3}nh tr\u{1EF1}c ti\u{1EBF}p trong b\u{00E0}i vi\u{1EBF}t.",
            'backUrl' => '/admin/posts',
            'actionUrl' => '/admin/posts/save',
            'row' => $row ?? ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmPostDelete(int $id): never
    {
        $admin = $this->requireAdmin();

        if ($this->isCollaborator($admin)) {
            $this->redirectWithFlash('/admin/posts', 'Cong tac vien khong duoc xoa tin tuc.', 'error');
        }

        $row = $this->findRow('posts', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/posts', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y tin t\u{1EE9}c.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a tin t\u{1EE9}c",
            'admin' => $admin,
            'section' => 'posts',
            'heading' => "X\u{00F3}a tin t\u{1EE9}c",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a tin t\u{1EE9}c n\u{00E0}y?",
            'row' => $row,
            'summary' => ['ID' => 'id', "Ti\u{00EA}u \u{0111}\u{1EC1}" => 'title', 'Slug' => 'slug'],
            'actionUrl' => '/admin/posts/delete',
            'backUrl' => '/admin/posts',
            'flash' => $this->pullFlash(),
        ]);
    }
    public function savePost(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? '')) ?: $this->slugify($title);

        if ($title === '' || $slug === '') {
            $this->redirectWithFlash($id ? '/admin/posts/' . $id . '/edit' : '/admin/posts/create', "Ti\u{00EA}u \u{0111}\u{1EC1} v\u{00E0} slug b\u{1EAF}t bu\u{1ED9}c.", 'error');
        }

        $imageUrl = trim((string) ($_POST['image_url'] ?? '')) ?: null;

        try {
            $uploadedImageUrl = $this->storeNewsImageUpload($_FILES['image_file'] ?? null);

            if ($uploadedImageUrl !== null) {
                $imageUrl = $uploadedImageUrl;
            }
        } catch (Throwable $exception) {
            $this->redirectWithFlash($id ? '/admin/posts/' . $id . '/edit' : '/admin/posts/create', $exception->getMessage(), 'error');
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'category' => trim((string) ($_POST['category'] ?? 'tin-tuc')) ?: 'tin-tuc',
            'summary' => trim((string) ($_POST['summary'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
            'image_url' => $imageUrl,
            'status' => trim((string) ($_POST['status'] ?? 'published')) ?: 'published',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'published_at' => trim((string) ($_POST['published_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];

        try {
            if ($id > 0) {
                $data['id'] = $id;
                Database::connection()->prepare(
                    'update posts set title = :title, slug = :slug, category = :category, summary = :summary, content = :content, image_url = :image_url, status = :status, is_featured = :is_featured, published_at = :published_at, updated_at = now() where id = :id'
                )->execute($data);
            } else {
                Database::connection()->prepare(
                    'insert into posts (title, slug, category, summary, content, image_url, status, is_featured, published_at)
                     values (:title, :slug, :category, :summary, :content, :image_url, :status, :is_featured, :published_at)'
                )->execute($data);
            }
        } catch (Throwable) {
            $this->redirectWithFlash($id ? '/admin/posts/' . $id . '/edit' : '/admin/posts/create', "Kh\u{00F4}ng th\u{1EC3} l\u{01B0}u tin t\u{1EE9}c. Slug c\u{00F3} th\u{1EC3} \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.", 'error');
        }

        $this->redirectWithFlash('/admin/posts', "\u{0110}\u{00E3} l\u{01B0}u tin t\u{1EE9}c.");
    }

    public function deletePost(): never
    {
        $admin = $this->requireAdmin();

        if ($this->isCollaborator($admin)) {
            $this->redirectWithFlash('/admin/posts', 'Cong tac vien khong duoc xoa tin tuc.', 'error');
        }

        Database::connection()->prepare('delete from posts where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/posts', "\u{0110}\u{00E3} x\u{00F3}a tin t\u{1EE9}c.");
    }

    public function uploadImage(): never
    {
        $this->requireAdmin();
        try {
            $url = $this->storeNewsImageUpload($_FILES['upload'] ?? null, true);
        } catch (Throwable $exception) {
            Response::json(['error' => ['message' => $exception->getMessage()]], 422);
        }

        Response::json(['url' => $url]);
    }

    public function payments(): never
    {
        $admin = $this->requireAdmin();
        $paymentEnabled = $this->paymentEnabled();

        View::render('admin/list', [
            'title' => 'Admin - Payments',
            'admin' => $admin,
            'section' => 'payments',
            'heading' => "Danh s\u{00E1}ch payment",
            'description' => $paymentEnabled
                ? "Ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n \u{0111}ang b\u{1EAD}t."
                : "Ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n \u{0111}ang t\u{1EAF}t.",
            'paymentEnabled' => $paymentEnabled,
            'paymentToggleUrl' => '/admin/payments/toggle',
            'rows' => $this->fetchAll(
                'select p.*, u.username
                 from payments p
                 left join users u on u.id = p.user_id
                 order by p.created_at desc, p.id desc
                 limit 200'
            ),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'user_id', 'label' => 'User ID'],
                ['key' => 'username', 'label' => 'User'],
                ['key' => 'currency_id', 'label' => 'Currency'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'amount', 'label' => 'Amount', 'format' => 'money'],
                ['key' => 'balance', 'label' => 'Balance', 'format' => 'money'],
                ['key' => 'created_at', 'label' => 'Created', 'format' => 'datetime'],
                ['key' => 'updated_at', 'label' => 'Updated', 'format' => 'datetime'],
            ],
            'actions' => ['edit' => '/admin/payments/%s/edit'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function togglePayment(): never
    {
        $this->requireAdmin();
        $enabled = ($_POST['enabled'] ?? '') === '1';

        if (!$this->writeEnvValue('PAYMENT_ENABLED', $enabled ? 'true' : 'false')) {
            $this->redirectWithFlash('/admin/payments', "Kh\u{00F4}ng th\u{1EC3} c\u{1EAD}p nh\u{1EAD}t file .env.", 'error');
        }

        $message = $enabled
            ? "\u{0110}\u{00E3} b\u{1EAD}t ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n."
            : "\u{0110}\u{00E3} t\u{1EAF}t ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n.";
        $this->redirectWithFlash('/admin/payments', $message);
    }

    public function paymentForm(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('payments', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/payments', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y payment.", 'error');
        }

        View::render('admin/form', [
            'title' => 'Admin - Chi tiet payment',
            'admin' => $admin,
            'section' => 'payments',
            'heading' => 'Chi tiet payment',
            'description' => 'Chi xem thong tin payment, khong cho phep chinh sua.',
            'backUrl' => '/admin/payments',
            'actionUrl' => '',
            'viewOnly' => true,
            'row' => $row,
            'fields' => [
                ['name' => 'id', 'label' => 'ID', 'readonly' => true],
                ['name' => 'user_id', 'label' => 'User ID', 'readonly' => true],
                ['name' => 'currency_id', 'label' => 'Currency', 'readonly' => true],
                ['name' => 'type', 'label' => 'Type', 'readonly' => true],
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'money', 'readonly' => true],
                ['name' => 'balance', 'label' => 'Balance', 'type' => 'money', 'readonly' => true],
                ['name' => 'trans_id', 'label' => 'Trans ID', 'readonly' => true],
                ['name' => 'player_name', 'label' => 'Player', 'readonly' => true],
                ['name' => 'received', 'label' => 'Received', 'readonly' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4, 'readonly' => true],
                ['name' => 'extra', 'label' => 'Extra', 'type' => 'textarea', 'rows' => 8, 'readonly' => true],
                ['name' => 'created_at', 'label' => 'Created at', 'readonly' => true],
                ['name' => 'updated_at', 'label' => 'Updated at', 'readonly' => true],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function savePayment(): never
    {
        $this->requireAdmin();
        $this->redirectWithFlash('/admin/payments', 'Trang payment chi cho xem, khong ho tro luu.', 'error');
    }

    public function feedbacks(): never
    {
        $admin = $this->requireAdmin();
        $search = trim((string) ($_GET['q'] ?? ''));
        $rows = $search !== ''
            ? $this->searchFeedbacks($search)
            : $this->fetchAll(
                'select f.id, f.user_id, u.username, f.type, f.subject, f.status, f.created_at, f.updated_at
                 from user_feedback f
                 left join users u on u.id = f.user_id
                 order by f.created_at desc, f.id desc
                 limit 200'
            );

        View::render('admin/list', [
            'title' => 'Admin - Phản hồi',
            'admin' => $admin,
            'section' => 'feedbacks',
            'heading' => 'Danh sách phản hồi',
            'description' => 'Tổng hợp báo lỗi và đề xuất tính năng từ người dùng.',
            'searchUrl' => '/admin/feedbacks',
            'searchValue' => $search,
            'searchPlaceholder' => 'Tìm theo username, tiêu đề hoặc nội dung',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'username', 'label' => 'User'],
                ['key' => 'type', 'label' => 'Loại', 'format' => 'feedback_type'],
                ['key' => 'subject', 'label' => 'Tiêu đề'],
                ['key' => 'status', 'label' => 'Trạng thái', 'format' => 'feedback_status'],
                ['key' => 'created_at', 'label' => 'Ngày gửi', 'format' => 'datetime'],
            ],
            'actions' => ['edit' => '/admin/feedbacks/%s/edit'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function feedbackForm(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findFeedback($id);

        if (!$row) {
            $this->redirectWithFlash('/admin/feedbacks', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y feedback.", 'error');
        }

        View::render('admin/form', [
            'title' => 'Admin - Chi tiết phản hồi',
            'admin' => $admin,
            'section' => 'feedbacks',
            'heading' => 'Chi tiết phản hồi',
            'description' => 'Xem thông tin phản hồi do người dùng gửi lên.',
            'backUrl' => '/admin/feedbacks',
            'actionUrl' => '',
            'viewOnly' => true,
            'row' => $row,
            'fields' => [
                ['name' => 'id', 'label' => 'ID', 'readonly' => true],
                ['name' => 'user_id', 'label' => 'User ID', 'readonly' => true],
                ['name' => 'username', 'label' => 'Username', 'readonly' => true],
                ['name' => 'type', 'label' => 'Loại', 'readonly' => true],
                ['name' => 'status', 'label' => 'Trạng thái', 'readonly' => true],
                ['name' => 'subject', 'label' => 'Tiêu đề', 'readonly' => true],
                ['name' => 'content', 'label' => 'Nội dung', 'type' => 'textarea', 'rows' => 8, 'readonly' => true],
                ['name' => 'created_at', 'label' => 'Ngày tạo', 'readonly' => true],
                ['name' => 'updated_at', 'label' => 'Cập nhật', 'readonly' => true],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function banks(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => 'Admin - Bank Accounts',
            'admin' => $admin,
            'section' => 'banks',
            'heading' => "Danh s\u{00E1}ch t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'createUrl' => '/admin/banks/create',
            'rows' => $this->fetchAll('select * from bank_accounts order by sort_order asc, id desc'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'bank_name', 'label' => 'Bank'],
                ['key' => 'acc_num', 'label' => 'Account'],
                ['key' => 'code', 'label' => 'Prefix'],
                ['key' => 'bank_rate', 'label' => 'Rate'],
                ['key' => 'is_active', 'label' => 'Active', 'format' => 'bool'],
            ],
            'actions' => ['edit' => '/admin/banks/%s/edit', 'delete' => '/admin/banks/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function bankForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('bank_accounts', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/banks', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng" : "Admin - Th\u{00EA}m t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'admin' => $admin,
            'section' => 'banks',
            'heading' => $id ? "S\u{1EED}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng" : "Th\u{00EA}m t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'backUrl' => '/admin/banks',
            'actionUrl' => '/admin/banks/save',
            'row' => $row ?? ['bank_rate' => 1, 'is_active' => true],
            'fields' => [
                ['name' => 'bank_name', 'label' => 'Bank name', 'required' => true],
                ['name' => 'bank_code', 'label' => 'Bank code VietQR', 'required' => true],
                ['name' => 'acc_num', 'label' => 'Account number', 'required' => true],
                ['name' => 'acc_name', 'label' => 'Account name', 'required' => true],
                ['name' => 'code', 'label' => 'Prefix content', 'required' => true],
                ['name' => 'bank_rate', 'label' => 'Rate', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
                ['name' => 'sort_order', 'label' => 'Sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmBankDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('bank_accounts', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/banks', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'admin' => $admin,
            'section' => 'banks',
            'heading' => "X\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng n\u{00E0}y?",
            'row' => $row,
            'summary' => ['Bank' => 'bank_name', 'Account' => 'acc_num', 'Prefix' => 'code'],
            'actionUrl' => '/admin/banks/delete',
            'backUrl' => '/admin/banks',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveBank(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'bank_name' => trim((string) ($_POST['bank_name'] ?? '')),
            'bank_code' => strtoupper(trim((string) ($_POST['bank_code'] ?? ''))),
            'acc_num' => trim((string) ($_POST['acc_num'] ?? '')),
            'acc_name' => trim((string) ($_POST['acc_name'] ?? '')),
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'bank_rate' => max(0, (float) ($_POST['bank_rate'] ?? 1)),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];

        if ($data['bank_name'] === '' || $data['bank_code'] === '' || $data['acc_num'] === '' || $data['acc_name'] === '' || $data['code'] === '') {
            $this->redirectWithFlash($id ? '/admin/banks/' . $id . '/edit' : '/admin/banks/create', "Th\u{00F4}ng tin ng\u{00E2}n h\u{00E0}ng ch\u{01B0}a \u{0111}\u{1EA7}y \u{0111}\u{1EE7}.", 'error');
        }

        if ($id > 0) {
            $data['id'] = $id;
            Database::connection()->prepare(
                'update bank_accounts set bank_name = :bank_name, bank_code = :bank_code, acc_num = :acc_num, acc_name = :acc_name, code = :code, bank_rate = :bank_rate, is_active = :is_active, sort_order = :sort_order, updated_at = now() where id = :id'
            )->execute($data);
        } else {
            Database::connection()->prepare(
                'insert into bank_accounts (bank_name, bank_code, acc_num, acc_name, code, bank_rate, is_active, sort_order)
                 values (:bank_name, :bank_code, :acc_num, :acc_name, :code, :bank_rate, :is_active, :sort_order)'
            )->execute($data);
        }

        $this->redirectWithFlash('/admin/banks', "\u{0110}\u{00E3} l\u{01B0}u t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.");
    }

    public function deleteBank(): never
    {
        $this->requireAdmin();
        Database::connection()->prepare('delete from bank_accounts where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/banks', "\u{0110}\u{00E3} x\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.");
    }

    public function downloads(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => 'Admin - Downloads',
            'admin' => $admin,
            'section' => 'downloads',
            'heading' => "Danh s\u{00E1}ch download",
            'createUrl' => '/admin/downloads/create',
            'rows' => $this->fetchAll('select * from downloads order by sort_order asc, id desc'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'platform', 'label' => 'Platform'],
                ['key' => 'version', 'label' => 'Version'],
                ['key' => 'file_size', 'label' => 'Size'],
                ['key' => 'is_active', 'label' => 'Active', 'format' => 'bool'],
            ],
            'actions' => ['edit' => '/admin/downloads/%s/edit', 'delete' => '/admin/downloads/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function downloadForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('downloads', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/downloads', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y download.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a download" : "Admin - Th\u{00EA}m download",
            'admin' => $admin,
            'section' => 'downloads',
            'heading' => $id ? "S\u{1EED}a download" : "Th\u{00EA}m download",
            'backUrl' => '/admin/downloads',
            'actionUrl' => '/admin/downloads/save',
            'row' => $row ?? ['is_active' => true],
            'fields' => [
                ['name' => 'platform', 'label' => 'Platform', 'required' => true],
                ['name' => 'version', 'label' => 'Version', 'required' => true],
                ['name' => 'file_size', 'label' => 'File size', 'required' => true],
                ['name' => 'download_url', 'label' => 'Download URL', 'required' => true],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'sort_order', 'label' => 'Sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmDownloadDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('downloads', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/downloads', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y download.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a download",
            'admin' => $admin,
            'section' => 'downloads',
            'heading' => "X\u{00F3}a download",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a download n\u{00E0}y?",
            'row' => $row,
            'summary' => ['Platform' => 'platform', 'Version' => 'version', 'URL' => 'download_url'],
            'actionUrl' => '/admin/downloads/delete',
            'backUrl' => '/admin/downloads',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveDownload(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'platform' => trim((string) ($_POST['platform'] ?? '')),
            'version' => trim((string) ($_POST['version'] ?? '')),
            'file_size' => trim((string) ($_POST['file_size'] ?? '')),
            'download_url' => trim((string) ($_POST['download_url'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];

        if ($data['platform'] === '' || $data['version'] === '' || $data['file_size'] === '' || $data['download_url'] === '') {
            $this->redirectWithFlash($id ? '/admin/downloads/' . $id . '/edit' : '/admin/downloads/create', "Th\u{00F4}ng tin download ch\u{01B0}a \u{0111}\u{1EA7}y \u{0111}\u{1EE7}.", 'error');
        }

        try {
            if ($id > 0) {
                $data['id'] = $id;
                Database::connection()->prepare(
                    'update downloads set platform = :platform, version = :version, file_size = :file_size, download_url = :download_url, notes = :notes, is_active = :is_active, sort_order = :sort_order, updated_at = now() where id = :id'
                )->execute($data);
            } else {
                Database::connection()->prepare(
                    'insert into downloads (platform, version, file_size, download_url, notes, is_active, sort_order)
                     values (:platform, :version, :file_size, :download_url, :notes, :is_active, :sort_order)'
                )->execute($data);
            }
        } catch (Throwable) {
            $this->redirectWithFlash($id ? '/admin/downloads/' . $id . '/edit' : '/admin/downloads/create', "Kh\u{00F4}ng th\u{1EC3} l\u{01B0}u download. Platform c\u{00F3} th\u{1EC3} \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.", 'error');
        }

        $this->redirectWithFlash('/admin/downloads', "\u{0110}\u{00E3} l\u{01B0}u download.");
    }

    public function deleteDownload(): never
    {
        $this->requireAdmin();
        Database::connection()->prepare('delete from downloads where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/downloads', "\u{0110}\u{00E3} x\u{00F3}a download.");
    }

    public function rates(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => 'Admin - Rate',
            'admin' => $admin,
            'section' => 'rates',
            'heading' => "Chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i",
            'description' => $this->activeCampaign()
                ? "\u{0110}ang c\u{00F3} chi\u{1EBF}n d\u{1ECB}ch active. Base rate ng\u{00E2}n h\u{00E0}ng s\u{1EED}a trong Bank account."
                : "Ch\u{01B0}a c\u{00F3} chi\u{1EBF}n d\u{1ECB}ch active. Base rate ng\u{00E2}n h\u{00E0}ng s\u{1EED}a trong Bank account.",
            'createUrl' => '/admin/rates/create',
            'banks' => $this->fetchAll('select id, bank_name, bank_rate, is_active from bank_accounts order by sort_order asc, id desc'),
            'activeCampaign' => $this->activeCampaign(),
            'rows' => $this->fetchAll('select * from promotion_campaigns order by starts_at desc, id desc'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Campaign'],
                ['key' => 'bonus_percent', 'label' => 'Bonus %'],
                ['key' => 'starts_at', 'label' => "B\u{1EAF}t \u{0111}\u{1EA7}u"],
                ['key' => 'ends_at', 'label' => "K\u{1EBF}t th\u{00FA}c"],
                ['key' => 'is_active', 'label' => 'Active', 'format' => 'bool'],
            ],
            'actions' => ['edit' => '/admin/rates/%s/edit', 'delete' => '/admin/rates/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function campaignForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('promotion_campaigns', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/rates', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y chi\u{1EBF}n d\u{1ECB}ch.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a KM" : "Admin - Th\u{00EA}m KM",
            'admin' => $admin,
            'section' => 'rates',
            'heading' => $id ? "S\u{1EED}a chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i" : "Th\u{00EA}m chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i",
            'description' => "V\u{00ED} d\u{1EE5}: ch\u{1EA1}y 2 ng\u{00E0}y v\u{1EDB}i bonus 30% coin n\u{1EA1}p.",
            'backUrl' => '/admin/rates',
            'actionUrl' => '/admin/rates/save',
            'row' => $row ?? ['bonus_percent' => 30, 'starts_at' => date('Y-m-d H:i:s'), 'ends_at' => date('Y-m-d H:i:s', strtotime('+2 days')), 'is_active' => true],
            'fields' => [
                ['name' => 'name', 'label' => "T\u{00EA}n chi\u{1EBF}n d\u{1ECB}ch", 'required' => true],
                ['name' => 'bonus_percent', 'label' => 'Bonus %', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
                ['name' => 'starts_at', 'label' => "B\u{1EAF}t \u{0111}\u{1EA7}u", 'type' => 'datetime-local', 'required' => true],
                ['name' => 'ends_at', 'label' => "K\u{1EBF}t th\u{00FA}c", 'type' => 'datetime-local', 'required' => true],
                ['name' => 'note', 'label' => "Ghi ch\u{00FA}", 'type' => 'textarea', 'rows' => 4],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmCampaignDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('promotion_campaigns', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/rates', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y chi\u{1EBF}n d\u{1ECB}ch.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a KM",
            'admin' => $admin,
            'section' => 'rates',
            'heading' => "X\u{00F3}a chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a chi\u{1EBF}n d\u{1ECB}ch n\u{00E0}y?",
            'row' => $row,
            'summary' => ['Name' => 'name', 'Bonus' => 'bonus_percent', "B\u{1EAF}t \u{0111}\u{1EA7}u" => 'starts_at'],
            'actionUrl' => '/admin/rates/delete',
            'backUrl' => '/admin/rates',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveCampaign(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'bonus_percent' => max(0, (float) ($_POST['bonus_percent'] ?? 0)),
            'starts_at' => trim((string) ($_POST['starts_at'] ?? '')),
            'ends_at' => trim((string) ($_POST['ends_at'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        if ($data['name'] === '' || $data['starts_at'] === '' || $data['ends_at'] === '') {
            $this->redirectWithFlash($id ? '/admin/rates/' . $id . '/edit' : '/admin/rates/create', "T\u{00EA}n chi\u{1EBF}n d\u{1ECB}ch v\u{00E0} th\u{1EDD}i gian l\u{00E0} b\u{1EAF}t bu\u{1ED9}c.", 'error');
        }

        if ($id > 0) {
            $data['id'] = $id;
            Database::connection()->prepare(
                'update promotion_campaigns set name = :name, bonus_percent = :bonus_percent, starts_at = :starts_at, ends_at = :ends_at, is_active = :is_active, note = :note, updated_at = now() where id = :id'
            )->execute($data);
        } else {
            Database::connection()->prepare(
                'insert into promotion_campaigns (name, bonus_percent, starts_at, ends_at, is_active, note)
                 values (:name, :bonus_percent, :starts_at, :ends_at, :is_active, :note)'
            )->execute($data);
        }

        $this->redirectWithFlash('/admin/rates', "\u{0110}\u{00E3} l\u{01B0}u chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i.");
    }

    public function deleteCampaign(): never
    {
        $this->requireAdmin();
        Database::connection()->prepare('delete from promotion_campaigns where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/rates', "\u{0110}\u{00E3} x\u{00F3}a chi\u{1EBF}n d\u{1ECB}ch.");
    }

    private function requireAdmin(): array
    {
        $admin = $this->currentAdmin();

        if (!$admin) {
            header('Location: /admin/login');
            exit;
        }

        if ($this->isCollaborator($admin) && !$this->collaboratorCanAccessCurrentPath()) {
            if ($this->currentAdminPath() === '/admin') {
                header('Location: /admin/posts');
                exit;
            }

            $this->redirectWithFlash('/admin/posts', "C\u{1ED9}ng t\u{00E1}c vi\u{00EA}n ch\u{1EC9} \u{0111}\u{01B0}\u{1EE3}c qu\u{1EA3}n l\u{00FD} tin t\u{1EE9}c.", 'error');
        }

        return $admin;
    }

    private function currentAdmin(): ?array
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user || empty($user['username'])) {
            return null;
        }

        try {
            $statement = Database::connection()->prepare($this->currentAdminQuery());
            $statement->execute(['username' => $user['username']]);
            $admin = $statement->fetch();

            if (
                $admin
                && $this->isAdminRole($admin)
                && $this->isUserAccessible($admin)
            ) {
                return $admin;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function isCollaborator(array $admin): bool
    {
        return $this->accountRole($admin) === self::ROLE_COLLABORATOR;
    }

    private function isAdminRole(array $account): bool
    {
        $role = $this->accountRole($account);

        return in_array($role, [self::ROLE_ADMIN, self::ROLE_COLLABORATOR], true);
    }

    private function currentAdminPath(): string
    {
        return (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    }

    private function collaboratorCanAccessCurrentPath(): bool
    {
        $path = $this->currentAdminPath();

        return $path === '/admin'
            || str_starts_with($path, '/admin/posts')
            || $path === '/admin/uploads/image'
            || $path === '/admin/logout';
    }

    private function userRoleOptions(): array
    {
        return [
            ['value' => (string) self::ROLE_USER, 'label' => 'User'],
            ['value' => (string) self::ROLE_COLLABORATOR, 'label' => 'CTV'],
            ['value' => (string) self::ROLE_ADMIN, 'label' => 'Admin'],
        ];
    }

    private function userRoleFromRequest(int $id): int
    {
        $requestedRole = (int) ($_POST['role'] ?? self::ROLE_USER);

        if (in_array($requestedRole, [self::ROLE_USER, self::ROLE_COLLABORATOR, self::ROLE_ADMIN], true)) {
            return $requestedRole;
        }

        if ($id > 0) {
            $row = $this->findRow('users', $id);

            if ($row) {
                return (int) ($row['role'] ?? self::ROLE_USER);
            }
        }

        return self::ROLE_USER;
    }

    private function userStatusOptions(): array
    {
        return [
            ['value' => '0', 'label' => 'Deactivate'],
            ['value' => '1', 'label' => 'Active'],
            ['value' => '2', 'label' => 'Block'],
        ];
    }

    private function userStatusFromRequest(): int
    {
        $status = (int) ($_POST['status'] ?? 1);

        return in_array($status, [0, 1, 2], true) ? $status : 1;
    }

    private function isUserAccessible(array $account): bool
    {
        if ($this->usesModernUserSchema()) {
            return (int) ($account['status'] ?? 0) === 1
                && $this->databaseBool($account['activated'] ?? 1)
                && $this->databaseBool($account['active'] ?? 1);
        }

        return !$this->databaseBool($account['ban'] ?? 0)
            && $this->databaseBool($account['is_active'] ?? 1);
    }

    private function dashboardStats(): array
    {
        return [
            'users' => $this->scalar('select count(*) from users'),
            'posts' => $this->scalar('select count(*) from posts'),
            'payments' => $this->scalar('select count(*) from payments'),
            'banks' => $this->scalar('select count(*) from bank_accounts'),
            'downloads' => $this->scalar('select count(*) from downloads'),
            'campaigns' => $this->scalar('select count(*) from promotion_campaigns'),
        ];
    }

    private function fetchAll(string $sql): array
    {
        try {
            return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function searchUsers(string $search): array
    {
        try {
            $statement = Database::connection()->prepare(
                'select id, username, name, email, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew
                 from users
                 where lower(username) like :search
                    or lower(coalesce(name, \'\')) like :search
                    or lower(coalesce(email, \'\')) like :search
                 order by id desc
                 limit 200'
            );
            $statement->execute([
                'search' => '%' . strtolower($search) . '%',
            ]);

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function storeNewsImageUpload(mixed $file, bool $required = false): ?string
    {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new \RuntimeException("Kh\u{00F4}ng nh\u{1EAD}n \u{0111}\u{01B0}\u{1EE3}c file upload.");
            }

            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Kh\u{00F4}ng nh\u{1EAD}n \u{0111}\u{01B0}\u{1EE3}c file upload.");
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException("\u{1EA2}nh kh\u{00F4}ng \u{0111}\u{01B0}\u{1EE3}c v\u{01B0}\u{1EE3}t qu\u{00E1} 5MB.");
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mimeType = function_exists('mime_content_type') ? (string) mime_content_type($tmpName) : '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($extensions[$mimeType])) {
            throw new \RuntimeException("Ch\u{1EC9} h\u{1ED7} tr\u{1EE3} JPG, PNG, WEBP, GIF.");
        }

        $baseDir = dirname(__DIR__, 2);
        $uploadDir = $baseDir . '/public/uploads/news';
        $mirrorUploadDir = $baseDir . '/public_html/uploads/news';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException("Kh\u{00F4}ng t\u{1EA1}o \u{0111}\u{01B0}\u{1EE3}c th\u{01B0} m\u{1EE5}c upload.");
        }

        if (!is_dir($mirrorUploadDir) && !mkdir($mirrorUploadDir, 0775, true) && !is_dir($mirrorUploadDir)) {
            throw new \RuntimeException("Kh\u{00F4}ng t\u{1EA1}o \u{0111}\u{01B0}\u{1EE3}c th\u{01B0} m\u{1EE5}c public_html/uploads/news.");
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mimeType];
        $target = $uploadDir . '/' . $filename;
        $mirrorTarget = $mirrorUploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            throw new \RuntimeException("Kh\u{00F4}ng l\u{01B0}u \u{0111}\u{01B0}\u{1EE3}c file upload.");
        }

        if (!copy($target, $mirrorTarget)) {
            @unlink($target);
            throw new \RuntimeException("Kh\u{00F4}ng copy \u{1EA3}nh sang public_html/uploads/news.");
        }

        return '/uploads/news/' . $filename;
    }

    private function searchFeedbacks(string $search): array
    {
        try {
            $statement = Database::connection()->prepare(
                'select f.id, f.user_id, u.username, f.type, f.subject, f.status, f.created_at, f.updated_at
                 from user_feedback f
                 left join users u on u.id = f.user_id
                 where lower(coalesce(u.username, \'\')) like :search
                    or lower(coalesce(f.subject, \'\')) like :search
                    or lower(coalesce(f.content, \'\')) like :search
                 order by f.created_at desc, f.id desc
                 limit 200'
            );
            $statement->execute([
                'search' => '%' . strtolower($search) . '%',
            ]);

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function findRow(string $table, int $id): ?array
    {
        $allowed = ['users', 'posts', 'payments', 'bank_accounts', 'downloads', 'promotion_campaigns', 'user_feedback'];

        if ($id <= 0 || !in_array($table, $allowed, true)) {
            return null;
        }

        $statement = Database::connection()->prepare("select * from {$table} where id = :id limit 1");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function findFeedback(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $statement = Database::connection()->prepare(
            'select f.*, u.username
             from user_feedback f
             left join users u on u.id = f.user_id
             where f.id = :id
             limit 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function scalar(string $sql): int
    {
        try {
            return (int) Database::connection()->query($sql)->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function activeCampaign(): ?array
    {
        try {
            $statement = Database::connection()->query(
                'select * from promotion_campaigns
                 where is_active = true and now() between starts_at and ends_at
                 order by bonus_percent desc, ends_at asc
                 limit 1'
            );
            $campaign = $statement->fetch();

            return $campaign ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function redirectWithFlash(string $url, string $message, string $type = 'success'): never
    {
        $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
        header('Location: ' . $url);
        exit;
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        return is_array($flash) ? $flash : null;
    }

    private function databaseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 't', 'yes', 'y'], true);
    }

    private function moneyIntFromRequest(string $key): int
    {
        $raw = (string) ($_POST[$key] ?? '0');
        $normalized = preg_replace('/[^0-9]/', '', $raw) ?? '0';

        return max(0, (int) $normalized);
    }

    private function paymentEnabled(): bool
    {
        return $this->databaseBool(env('PAYMENT_ENABLED', 'true'));
    }

    private function registerBonusEnabled(): bool
    {
        return $this->databaseBool(env('REGISTER_BONUS_ENABLED', 'true'));
    }

    private function writeEnvValue(string $key, string $value): bool
    {
        $path = dirname(__DIR__, 2) . '/.env';

        if (!is_file($path) || !is_writable($path)) {
            return false;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return false;
        }

        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $content)) {
            $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $content) ?? $content;
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        return file_put_contents($path, $content) !== false;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';

        return trim($value, '-');
    }

    private function userTableSchema(): array
    {
        if ($this->userTableSchema !== null) {
            return $this->userTableSchema;
        }

        try {
            $rows = Database::connection()->query('show columns from users')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $schema = [];

            foreach ($rows as $row) {
                $field = strtolower((string) ($row['Field'] ?? ''));

                if ($field !== '') {
                    $schema[$field] = $row;
                }
            }

            $this->userTableSchema = $schema;
        } catch (Throwable) {
            $this->userTableSchema = [];
        }

        return $this->userTableSchema;
    }

    private function usesModernUserSchema(): bool
    {
        $schema = $this->userTableSchema();

        return isset($schema['role'], $schema['status'], $schema['active']);
    }

    private function adminLoginQuery(): string
    {
        if ($this->usesModernUserSchema()) {
            return 'select id, username, password, status, activated, active, role from users where lower(username) = lower(:username) limit 1';
        }

        return 'select id, username, password, ban, is_active, type_admin from users where lower(username) = lower(:username) limit 1';
    }

    private function currentAdminQuery(): string
    {
        if ($this->usesModernUserSchema()) {
            return 'select id, username, role, status, activated, active from users where lower(username) = lower(:username) limit 1';
        }

        return 'select id, username, type_admin, ban, is_active from users where lower(username) = lower(:username) limit 1';
    }

    private function accountRole(array $account): int
    {
        if ($this->usesModernUserSchema()) {
            return (int) ($account['role'] ?? self::ROLE_USER);
        }

        $legacyRole = (int) ($account['type_admin'] ?? self::ROLE_USER);

        if ($legacyRole <= 0) {
            return self::ROLE_USER;
        }

        return $legacyRole === self::ROLE_COLLABORATOR
            ? self::ROLE_COLLABORATOR
            : self::ROLE_ADMIN;
    }
}
