<?php

declare(strict_types=1);

namespace App\Controllers;

use App\ContentRepository;
use App\Database;
use App\Mail\PasswordOtpMail;
use App\Response;
use App\View;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SiteController
{
    private const PAYMENT_REF_WIDTH = 3;
    private const ACTIVATION_DEPOSIT_AMOUNT = 20000;
    private const REGISTER_BONUS_AMOUNT = 5000000;
    private const MONTHLY_RESET_COLUMN = 'tongNapThangResetAt';
    private const WEEKLY_RESET_COLUMN = 'tongNapTuanResetAt';

    private ContentRepository $content;
    private ?array $userTableSchema = null;

    public function __construct(?ContentRepository $content = null)
    {
        $this->content = $content ?? new ContentRepository();
    }

    public function home(): never
    {
        View::render('home', $this->homePayload());
    }

    public function news(string $path): never
    {
        $category = trim($path, '/');
        $category = $category === 'news' || $category === 'tin-tuc' ? null : $category;

        View::render('news', [
            'title' => "Tin t\u{1EE9}c",
            'posts' => $this->content->posts($category),
            'activeCategory' => $category,
        ]);
    }

    public function post(string $slug): never
    {
        $post = $this->content->post($slug);

        if (!$post) {
            $this->notFound();
        }

        View::render('post', [
            'title' => $post['title'],
            'post' => $post,
            'relatedPosts' => $this->content->relatedPosts((string) ($post['category'] ?? ''), (string) ($post['slug'] ?? ''), 6),
        ]);
    }

    public function download(): never
    {
        View::render('download', [
            'title' => "T\u{1EA3}i game",
            'downloads' => $this->content->downloads(),
        ]);
    }

    public function payment(): never
    {
        if (!$this->paymentEnabled()) {
            $this->notFound();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $account = null;

        try {
            $account = $this->accountForUsername((string) ($user['username'] ?? ''));
        } catch (Throwable) {
            // Render the page with an error below.
        }

        $bankAccount = $this->activeBankAccount();

        View::render('payment', [
            'title' => "N\u{1EA1}p ti\u{1EC1}n",
            'submitted' => false,
            'account' => $account,
            'bankAccount' => $bankAccount,
            'amounts' => $this->depositAmounts(),
            'paymentCode' => ($bankAccount && $account) ? $this->buildTransferContent($bankAccount, $account) : '',
            'effectiveRate' => $bankAccount ? $this->effectiveBankRate($bankAccount) : null,
            'activeCampaign' => $this->activePromotionCampaign(),
        ]);
    }

    public function submitPayment(): never
    {
        if (!$this->paymentEnabled()) {
            $this->notFound();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $amount = (int) $this->moneyValue($_POST['amount'] ?? null);
        $bankId = (int) ($_POST['bank_account_id'] ?? 0);
        $amounts = $this->depositAmounts();
        $bankAccount = $this->activeBankAccount($bankId > 0 ? $bankId : null);

        if (!$bankAccount || $amount <= 0 || $amount % 1000 !== 0) {
            View::render('payment', [
                'title' => "N\u{1EA1}p ti\u{1EC1}n",
                'submitted' => false,
                'account' => null,
                'bankAccount' => $bankAccount,
                'amounts' => $amounts,
                'amount' => $amount > 0 ? $amount : null,
                'error' => "Vui l\u{00F2}ng nh\u{1EAD}p s\u{1ED1} ti\u{1EC1}n h\u{1EE3}p l\u{1EC7} v\u{00E0} l\u{00E0} b\u{1ED9}i s\u{1ED1} c\u{1EE7}a 1.000 VND.",
            ], 422);
        }

        try {
            $account = $this->accountForUsername((string) ($user['username'] ?? ''));

            if (!$account) {
            View::render('payment', [
                    'title' => "N\u{1EA1}p ti\u{1EC1}n",
                    'submitted' => false,
                    'account' => null,
                    'bankAccount' => $bankAccount,
                    'amounts' => $amounts,
                    'error' => "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n \u{0111}\u{1EC3} t\u{1EA1}o giao d\u{1ECB}ch n\u{1EA1}p.",
                ], 404);
            }

            $transferContent = $this->buildTransferContent($bankAccount, $account);
            $effectiveRate = $this->effectiveBankRate($bankAccount);
            $activeCampaign = $this->activePromotionCampaign();
            $coinAmount = $this->coinAmount($amount, $effectiveRate);
            $qrImageUrl = $this->vietQrUrl($bankAccount, $amount, $transferContent);
        } catch (Throwable) {
            View::render('payment', [
                'title' => "N\u{1EA1}p ti\u{1EC1}n",
                'submitted' => false,
                'account' => null,
                'bankAccount' => $bankAccount,
                'amounts' => $amounts,
                'error' => "Kh\u{00F4}ng th\u{1EC3} t\u{1EA1}o giao d\u{1ECB}ch n\u{1EA1}p l\u{00FA}c n\u{00E0}y.",
            ], 500);
        }

        View::render('payment', [
            'title' => "N\u{1EA1}p ti\u{1EC1}n",
            'submitted' => true,
            'account' => $account,
            'bankAccount' => $bankAccount,
            'amounts' => $amounts,
            'amount' => $amount,
            'paymentCode' => $transferContent,
            'coinAmount' => $coinAmount,
            'qrImageUrl' => $qrImageUrl,
            'effectiveRate' => $effectiveRate,
            'activeCampaign' => $activeCampaign,
        ]);
    }

    public function bankWebhook(): never
    {
        if (!$this->validBankWebhookToken()) {
            Response::json([
                'status' => false,
                'msg' => 'Unauthorized',
            ], 401);
        }

        $payload = $this->webhookPayload();
        $items = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : [];

        try {
            foreach ($items as $item) {
                if (!is_array($item) || strtoupper(trim((string) ($item['type'] ?? ''))) !== 'IN') {
                    continue;
                }

                $this->processBankWebhookItem($item, $payload);
            }

            Response::json([
                'status' => true,
                'msg' => 'OK',
            ]);
        } catch (Throwable) {
            Response::json([
                'status' => false,
                'msg' => 'Cannot process webhook right now',
            ], 500);
        }
    }

    public function paymentStatus(): never
    {
        if (!$this->paymentEnabled()) {
            $this->notFound();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user || empty($user['username'])) {
            Response::json([
                'status' => false,
                'paid' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $paymentCode = strtoupper(trim((string) ($_GET['code'] ?? '')));

        if ($paymentCode === '') {
            Response::json([
                'status' => false,
                'paid' => false,
                'message' => 'Missing payment code',
            ], 422);
        }

        try {
            $account = $this->accountForUsername((string) $user['username']);

            if (!$account) {
                Response::json([
                    'status' => false,
                    'paid' => false,
                    'message' => 'Account not found',
                ], 404);
            }

            $statement = Database::connection()->prepare(
                'select id, amount, balance, trans_id, created_at
                 from payments
                 where user_id = :user_id
                   and trans_id = :trans_id
                 order by id desc
                 limit 1'
            );
            $statement->execute([
                'user_id' => (int) $account['id'],
                'trans_id' => $paymentCode,
            ]);
            $payment = $statement->fetch();

            Response::json([
                'status' => true,
                'paid' => (bool) $payment,
                'payment' => $payment ?: null,
            ]);
        } catch (Throwable) {
            Response::json([
                'status' => false,
                'paid' => false,
                'message' => 'Cannot check payment right now',
            ], 500);
        }
    }

    public function profile(): never
    {
        $user = $_SESSION['user'] ?? null;

        View::render('profile', [
            'title' => "T\u{00E0}i kho\u{1EA3}n",
            'user' => $user,
            'account' => $user ? $this->accountProfile($user) : null,
            'transactions' => $user ? $this->depositHistory($user) : [],
            'feedbackHistory' => $user ? $this->feedbackHistory($user) : [],
            'activeTab' => $_GET['tab'] ?? 'info',
            'emailSuccess' => $this->pullFlash('profile_email_success'),
            'emailError' => $this->pullFlash('profile_email_error'),
            'passwordSuccess' => $this->pullFlash('profile_password_success'),
            'passwordError' => $this->pullFlash('profile_password_error'),
            'feedbackSuccess' => $this->pullFlash('profile_feedback_success'),
            'feedbackError' => $this->pullFlash('profile_feedback_error'),
            'feedbackValues' => $this->pullArrayFlash('profile_feedback_values'),
        ]);
    }

    public function submitProfileFeedback(): never
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $type = trim((string) ($_POST['type'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $allowedTypes = ['bug', 'feature'];

        $_SESSION['profile_feedback_values'] = [
            'type' => $type,
            'subject' => $subject,
            'content' => $content,
        ];

        if (!in_array($type, $allowedTypes, true) || $subject === '' || $content === '' || strlen($subject) > 180) {
            $_SESSION['profile_feedback_error'] = "Vui lòng chọn đúng loại phản hồi và nhập đầy đủ tiêu đề, nội dung.";
            header('Location: /profile?tab=feedback');
            exit;
        }

        try {
            $account = $this->accountProfile($user);

            Database::connection()->prepare(
                'insert into user_feedback (user_id, type, subject, content, status, created_at, updated_at)
                 values (:user_id, :type, :subject, :content, :status, now(), now())'
            )->execute([
                'user_id' => (int) ($account['id'] ?? 0),
                'type' => $type,
                'subject' => $subject,
                'content' => $content,
                'status' => 'new',
            ]);

            unset($_SESSION['profile_feedback_values']);
            $_SESSION['profile_feedback_success'] = "Đã gửi phản hồi thành công.";
            $_SESSION['toast_success'] = "Cảm ơn bạn, phản hồi đã được gửi.";
            header('Location: /profile?tab=feedback');
            exit;
        } catch (Throwable) {
            $_SESSION['profile_feedback_error'] = "Không thể gửi phản hồi lúc này. Vui lòng thử lại sau.";
            header('Location: /profile?tab=feedback');
            exit;
        }
    }

    public function submitProfileEmail(): never
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $password = trim((string) ($_POST['password'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['profile_email_error'] = "Vui l\u{00F2}ng nh\u{1EAD}p m\u{1EAD}t kh\u{1EA9}u v\u{00E0} email h\u{1EE3}p l\u{1EC7}.";
            header('Location: /profile?tab=settings&modal=email');
            exit;
        }

        try {
            $account = $this->accountWithPassword((string) ($user['username'] ?? ''));

            if (!$account) {
                $_SESSION['profile_email_error'] = "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n trong database.";
                header('Location: /profile?tab=settings&modal=email');
                exit;
            }

            if (!password_verify($password, (string) $account['password'])) {
                $_SESSION['profile_email_error'] = "M\u{1EAD}t kh\u{1EA9}u x\u{00E1}c th\u{1EF1}c kh\u{00F4}ng \u{0111}\u{00FA}ng.";
                header('Location: /profile?tab=settings&modal=email');
                exit;
            }

            $statement = Database::connection()->prepare(
                'update users set email = :email, updated_at = now() where id = :id'
            );
            $statement->execute([
                'email' => $email,
                'id' => $account['id'],
            ]);

            unset($_SESSION['pending_email_otp'], $_SESSION['pending_email']);
            $_SESSION['profile_email_success'] = "C\u{1EAD}p nh\u{1EAD}t email th\u{00E0}nh c\u{00F4}ng.";
            $_SESSION['toast_success'] = "C\u{1EAD}p nh\u{1EAD}t email th\u{00E0}nh c\u{00F4}ng!";
            header('Location: /profile?tab=settings');
            exit;
        } catch (Throwable) {
            $_SESSION['profile_email_error'] = "Kh\u{00F4}ng th\u{1EC3} c\u{1EAD}p nh\u{1EAD}t email l\u{00FA}c n\u{00E0}y.";
            header('Location: /profile?tab=settings&modal=email');
            exit;
        }
    }

    public function submitProfilePassword(): never
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            header('Location: /login');
            exit;
        }

        $currentPassword = trim((string) ($_POST['current_password'] ?? ''));
        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));

        if ($currentPassword === '' || strlen($newPassword) < 6 || $newPassword !== $confirmPassword) {
            $_SESSION['profile_password_error'] = "Ki\u{1EC3}m tra l\u{1EA1}i m\u{1EAD}t kh\u{1EA9}u hi\u{1EC7}n t\u{1EA1}i v\u{00E0} m\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1}.";
            header('Location: /profile?tab=settings&modal=password');
            exit;
        }

        try {
            $account = $this->accountWithPassword((string) ($user['username'] ?? ''));

            if (!$account) {
                $_SESSION['profile_password_error'] = "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n trong database.";
                header('Location: /profile?tab=settings&modal=password');
                exit;
            }

            if (!password_verify($currentPassword, (string) $account['password'])) {
                $_SESSION['profile_password_error'] = "M\u{1EAD}t kh\u{1EA9}u hi\u{1EC7}n t\u{1EA1}i kh\u{00F4}ng \u{0111}\u{00FA}ng.";
                header('Location: /profile?tab=settings&modal=password');
                exit;
            }

            $statement = Database::connection()->prepare(
                'update users set password = :password, updated_at = now() where id = :id'
            );
            $statement->execute([
                'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                'id' => $account['id'],
            ]);

            $_SESSION['profile_password_success'] = "\u{0110}\u{1ED5}i m\u{1EAD}t kh\u{1EA9}u th\u{00E0}nh c\u{00F4}ng.";
            $_SESSION['toast_success'] = "\u{0110}\u{1ED5}i m\u{1EAD}t kh\u{1EA9}u th\u{00E0}nh c\u{00F4}ng!";
            header('Location: /profile?tab=settings');
            exit;
        } catch (Throwable) {
            $_SESSION['profile_password_error'] = "Kh\u{00F4}ng th\u{1EC3} \u{0111}\u{1ED5}i m\u{1EAD}t kh\u{1EA9}u l\u{00FA}c n\u{00E0}y.";
            header('Location: /profile?tab=settings&modal=password');
            exit;
        }
    }

    public function login(): never
    {
        View::render('home', $this->homePayload([
            'title' => "\u{0110}\u{0103}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
            'authModal' => 'login',
            'loginError' => null,
        ]));
    }

    public function forgotPassword(): never
    {
        View::render('home', $this->homePayload([
            'title' => "Qu\u{00EA}n m\u{1EAD}t kh\u{1EA9}u Ninja School Blue",
            'authModal' => 'forgot',
            'forgotError' => null,
            'forgotSuccess' => null,
            'forgotStep' => $this->forgotPasswordStep(),
            'forgotUsername' => $_SESSION['forgot_password']['username'] ?? '',
            'forgotEmail' => $_SESSION['forgot_password']['email'] ?? '',
        ]));
    }

    public function submitForgotPassword(): never
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::render('home', $this->homePayload([
                'title' => "Qu\u{00EA}n m\u{1EAD}t kh\u{1EA9}u Ninja School Blue",
                'authModal' => 'forgot',
                'forgotError' => "Vui l\u{00F2}ng nh\u{1EAD}p t\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p v\u{00E0} email \u{0111}\u{00E3} \u{0111}\u{0103}ng k\u{00FD}.",
                'forgotSuccess' => null,
                'forgotUsername' => $username,
                'forgotEmail' => $email,
            ]), 422);
        }

        try {
            $statement = Database::connection()->prepare(
                'select id, username, email from users where username = :username and lower(email) = lower(:email) limit 1'
            );
            $statement->execute([
                'username' => $username,
                'email' => $email,
            ]);
            $account = $statement->fetch();

            if (!$account) {
                View::render('home', $this->homePayload([
                    'title' => "Qu\u{00EA}n m\u{1EAD}t kh\u{1EA9}u Ninja School Blue",
                    'authModal' => 'forgot',
                    'forgotError' => "T\u{00E0}i kho\u{1EA3}n ho\u{1EB7}c email kh\u{00F4}ng \u{0111}\u{00FA}ng.",
                    'forgotSuccess' => null,
                    'forgotUsername' => $username,
                    'forgotEmail' => $email,
                ]), 404);
            }

            $otp = (string) random_int(100000, 999999);
            $_SESSION['forgot_password'] = [
                'user_id' => $account['id'],
                'username' => $account['username'],
                'email' => $account['email'],
                'otp' => $otp,
                'expires_at' => time() + 10 * 60,
                'attempts' => 0,
                'verified' => false,
            ];

            $sent = $this->sendOtpEmail((string) $account['email'], (string) $account['username'], $otp);
            $message = $sent
                ? "\u{0110}\u{00E3} g\u{1EED}i m\u{00E3} OTP \u{0111}\u{1EBF}n email \u{0111}\u{0103}ng k\u{00FD} c\u{1EE7}a t\u{00E0}i kho\u{1EA3}n."
                : "\u{0110}\u{00E3} t\u{1EA1}o m\u{00E3} OTP demo: " . $otp . ". M\u{00E1}y ch\u{1EE7} ch\u{01B0}a c\u{1EA5}u h\u{00EC}nh g\u{1EED}i mail.";

            View::render('home', $this->homePayload([
                'title' => "Qu\u{00EA}n m\u{1EAD}t kh\u{1EA9}u Ninja School Blue",
                'authModal' => 'forgot',
                'forgotError' => null,
                'forgotSuccess' => $message,
                'forgotStep' => 'otp',
                'forgotUsername' => $username,
                'forgotEmail' => $email,
            ]));
        } catch (Throwable) {
            View::render('home', $this->homePayload([
                'title' => "Qu\u{00EA}n m\u{1EAD}t kh\u{1EA9}u Ninja School Blue",
                'authModal' => 'forgot',
                'forgotError' => "Kh\u{00F4}ng th\u{1EC3} g\u{1EED}i OTP l\u{00FA}c n\u{00E0}y.",
                'forgotSuccess' => null,
                'forgotUsername' => $username,
                'forgotEmail' => $email,
            ]), 500);
        }
    }

    public function verifyForgotPasswordOtp(): never
    {
        $otp = trim((string) ($_POST['otp'] ?? ''));
        $state = $_SESSION['forgot_password'] ?? null;

        if (!is_array($state) || !$this->validForgotState($state)) {
            unset($_SESSION['forgot_password']);
            $this->renderForgotPasswordStep('request', "OTP kh\u{00F4}ng h\u{1EE3}p l\u{1EC7} ho\u{1EB7}c \u{0111}\u{00E3} h\u{1EBF}t h\u{1EA1}n.", null, 422);
        }

        if (($state['attempts'] ?? 0) >= 5) {
            $this->renderForgotPasswordStep('otp', "B\u{1EA1}n \u{0111}\u{00E3} nh\u{1EAD}p sai qu\u{00E1} s\u{1ED1} l\u{1EA7}n cho ph\u{00E9}p.", null, 429);
        }

        if (!preg_match('/^[0-9]{6}$/', $otp) || !hash_equals((string) $state['otp'], $otp)) {
            $_SESSION['forgot_password']['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
            $this->renderForgotPasswordStep('otp', "OTP kh\u{00F4}ng h\u{1EE3}p l\u{1EC7} ho\u{1EB7}c \u{0111}\u{00E3} h\u{1EBF}t h\u{1EA1}n.", null, 422);
        }

        $_SESSION['forgot_password']['verified'] = true;
        $this->renderForgotPasswordStep('reset', null, "X\u{00E1}c th\u{1EF1}c OTP th\u{00E0}nh c\u{00F4}ng. Vui l\u{00F2}ng \u{0111}\u{1EB7}t m\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i.");
    }

    public function resetForgotPassword(): never
    {
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $state = $_SESSION['forgot_password'] ?? null;

        if (!is_array($state) || !$this->validForgotState($state) || empty($state['verified'])) {
            unset($_SESSION['forgot_password']);
            $this->renderForgotPasswordStep('request', "Phi\u{00EA}n \u{0111}\u{1EB7}t l\u{1EA1}i m\u{1EAD}t kh\u{1EA9}u kh\u{00F4}ng h\u{1EE3}p l\u{1EC7} ho\u{1EB7}c \u{0111}\u{00E3} h\u{1EBF}t h\u{1EA1}n.", null, 422);
        }

        if (strlen($password) < 6 || $password !== $confirmPassword) {
            $this->renderForgotPasswordStep('reset', "M\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1} v\u{00E0} ph\u{1EA3}i tr\u{00F9}ng nhau.", null, 422);
        }

        try {
            Database::connection()->prepare('update users set password = :password, updated_at = now() where id = :id')->execute([
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'id' => (int) $state['user_id'],
            ]);
        } catch (Throwable) {
            $this->renderForgotPasswordStep('reset', "Kh\u{00F4}ng th\u{1EC3} \u{0111}\u{1EB7}t l\u{1EA1}i m\u{1EAD}t kh\u{1EA9}u l\u{00FA}c n\u{00E0}y.", null, 500);
        }

        unset($_SESSION['forgot_password']);
        $_SESSION['toast_success'] = "\u{0110}\u{1EB7}t l\u{1EA1}i m\u{1EAD}t kh\u{1EA9}u th\u{00E0}nh c\u{00F4}ng. Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p.";
        header('Location: /login');
        exit;
    }

    public function submitLogin(): never
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            View::render('home', $this->homePayload([
                'title' => "\u{0110}\u{0103}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                'authModal' => 'login',
                'loginError' => "Vui l\u{00F2}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n v\u{00E0} m\u{1EAD}t kh\u{1EA9}u.",
            ]), 422);
        }

        try {
            $account = $this->accountWithPassword($username);

            if (!$account || !password_verify($password, (string) $account['password'])) {
                View::render('home', $this->homePayload([
                    'title' => "\u{0110}\u{0103}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                    'authModal' => 'login',
                    'loginError' => "T\u{00E0}i kho\u{1EA3}n ho\u{1EB7}c m\u{1EAD}t kh\u{1EA9}u kh\u{00F4}ng \u{0111}\u{00FA}ng.",
                ]), 401);
            }

            if (!$this->isAccountAccessible($account)) {
                View::render('home', $this->homePayload([
                    'title' => "\u{0110}\u{0103}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                    'authModal' => 'login',
                    'loginError' => "T\u{00E0}i kho\u{1EA3}n \u{0111}ang b\u{1ECB} kh\u{00F3}a ho\u{1EB7}c ch\u{01B0}a \u{0111}\u{01B0}\u{1EE3}c k\u{00ED}ch ho\u{1EA1}t.",
                ]), 403);
            }
        } catch (Throwable) {
            View::render('home', $this->homePayload([
                'title' => "\u{0110}\u{0103}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                'authModal' => 'login',
                'loginError' => "Kh\u{00F4}ng th\u{1EC3} \u{0111}\u{0103}ng nh\u{1EAD}p l\u{00FA}c n\u{00E0}y. Vui l\u{00F2}ng ki\u{1EC3}m tra database.",
            ]), 500);
        }

        $_SESSION['user'] = [
            'id' => $account['id'],
            'username' => $account['username'],
            'display_name' => $account['username'],
            'login_at' => date('Y-m-d H:i:s'),
        ];
        $_SESSION['toast_success'] = "\u{0110}\u{0103}ng nh\u{1EAD}p th\u{00E0}nh c\u{00F4}ng!";

        header('Location: /');
        exit;
    }

    public function register(): never
    {
        View::render('home', $this->homePayload([
            'title' => "\u{0110}\u{0103}ng k\u{00FD} t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
            'authModal' => 'register',
            'registerError' => null,
            'registerSubmitted' => false,
            'registerValues' => [
                'username' => '',
                'email' => '',
            ],
            'captchaQuestion' => $this->newCaptchaQuestion(),
        ]));
    }

    public function submitRegister(): never
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));
        $captcha = trim((string) ($_POST['captcha'] ?? ''));
        $captchaAnswer = (string) ($_SESSION['register_captcha_answer'] ?? '');
        $usernameMaxLength = $this->userColumnLength('username', 30);
        $emailMaxLength = $this->userColumnLength('email', 20);

        if (
            $username === ''
            || strlen($username) < 3
            || strlen($username) > $usernameMaxLength
            || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)
            || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
            || strlen($email) > $emailMaxLength
            || strlen($password) < 6
            || $password !== $confirmPassword
            || $captcha === ''
            || $captcha !== $captchaAnswer
        ) {
            View::render('home', $this->homePayload([
                'title' => "\u{0110}\u{0103}ng k\u{00FD} t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                'authModal' => 'register',
                'registerSubmitted' => false,
                'registerError' => "Ki\u{1EC3}m tra l\u{1EA1}i t\u{00EA}n t\u{00E0}i kho\u{1EA3}n (3-{$usernameMaxLength} k\u{00FD} t\u{1EF1}), email n\u{1EBF}u c\u{00F3} kh\u{00F4}ng qu\u{00E1} {$emailMaxLength} k\u{00FD} t\u{1EF1}, m\u{1EAD}t kh\u{1EA9}u t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1} v\u{00E0} captcha.",
                'registerValues' => [
                    'username' => $username,
                    'email' => $email,
                ],
                'captchaQuestion' => $this->newCaptchaQuestion(),
            ]), 422);
        }

        try {
            $connection = Database::connection();

            $exists = $connection->prepare(
                'select id from users where lower(username) = lower(:username) or (:email <> \'\' and email is not null and lower(email) = lower(:email)) limit 1'
            );
            $exists->execute([
                'username' => $username,
                'email' => $email,
            ]);

            if ($exists->fetch()) {
                View::render('home', $this->homePayload([
                    'title' => "\u{0110}\u{0103}ng k\u{00FD} t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                    'authModal' => 'register',
                    'registerSubmitted' => false,
                    'registerError' => "T\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p ho\u{1EB7}c email \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.",
                    'registerValues' => [
                        'username' => $username,
                        'email' => $email,
                    ],
                    'captchaQuestion' => $this->newCaptchaQuestion(),
                ]), 409);
            }

            if ($this->usesModernUserSchema()) {
                $statement = $connection->prepare(
                    'insert into users (name, username, email, password, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, created_at, updated_at)
                     values (:name, :username, :email, :password, 1, 1, 1, 0, :balance, 0, 0, 0, 0, now(), now())'
                );
                $statement->execute([
                    'name' => null,
                    'username' => $username,
                    'email' => $email !== '' ? $email : null,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'balance' => $this->registerBonusAmount(),
                ]);
            } else {
                $statement = $connection->prepare(
                    'insert into users (name, username, email, password, ban, is_active, type_admin, money, totalmoney, tongnapthang, created_at, updated_at)
                     values (:name, :username, :email, :password, 0, 1, 0, :money, 0, 0, now(), now())'
                );
                $statement->execute([
                    'name' => null,
                    'username' => $username,
                    'email' => $email !== '' ? $email : null,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'money' => $this->registerBonusAmount(),
                ]);
            }
            $account = [
                'id' => (int) $connection->lastInsertId(),
                'username' => $username,
            ];
        } catch (Throwable $exception) {
            View::render('home', $this->homePayload([
                'title' => "\u{0110}\u{0103}ng k\u{00FD} t\u{00E0}i kho\u{1EA3}n Ninja School Blue",
                'authModal' => 'register',
                'registerSubmitted' => false,
                'registerError' => $this->databaseErrorMessage("Kh\u{00F4}ng th\u{1EC3} t\u{1EA1}o t\u{00E0}i kho\u{1EA3}n l\u{00FA}c n\u{00E0}y. Vui l\u{00F2}ng ki\u{1EC3}m tra database.", $exception),
                'registerValues' => [
                    'username' => $username,
                    'email' => $email,
                ],
                'captchaQuestion' => $this->newCaptchaQuestion(),
            ]), 500);
        }

        unset($_SESSION['register_captcha_answer']);
        unset($_SESSION['register_captcha_question']);
        $_SESSION['toast_success'] = "\u{0110}\u{0103}ng k\u{00FD} th\u{00E0}nh c\u{00F4}ng! Vui l\u{00F2}ng \u{0111}\u{0103}ng nh\u{1EAD}p \u{0111}\u{1EC3} ti\u{1EBF}p t\u{1EE5}c.";

        header('Location: /login');
        exit;
    }

    public function logout(): never
    {
        unset($_SESSION['user']);
        unset($_SESSION['admin_login']);
        header('Location: /');
        exit;
    }

    public function warning(): never
    {
        View::render('warning', ['title' => "C\u{1EA3}nh b\u{00E1}o l\u{1EEB}a \u{0111}\u{1EA3}o"]);
    }

    public function community(): never
    {
        View::render('community', ['title' => "C\u{1ED9}ng \u{0111}\u{1ED3}ng"]);
    }

    public function contentApi(): array
    {
        return [
            'posts' => $this->content->posts(),
            'downloads' => $this->content->downloads(),
        ];
    }

    public function notFound(): never
    {
        View::render('404', ['title' => "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y trang"], 404);
    }

    private function homePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Ninja School Blue',
            'posts' => $this->content->featuredPosts(),
            'downloads' => $this->content->downloads(),
            'stats' => $this->content->stats(),
            'captchaQuestion' => $this->currentCaptchaQuestion(),
        ], $overrides);
    }

    private function currentCaptchaQuestion(): string
    {
        $question = $_SESSION['register_captcha_question'] ?? null;
        $answer = $_SESSION['register_captcha_answer'] ?? null;

        if (is_string($question) && $question !== '' && is_string($answer) && $answer !== '') {
            return $question;
        }

        return $this->newCaptchaQuestion();
    }

    private function newCaptchaQuestion(): string
    {
        $first = random_int(2, 9);
        $second = random_int(1, 8);
        $question = "{$first} + {$second} = ?";

        $_SESSION['register_captcha_answer'] = (string) ($first + $second);
        $_SESSION['register_captcha_question'] = $question;

        return $question;
    }

    private function accountProfile(array $sessionUser): array
    {
        $username = (string) ($sessionUser['username'] ?? '');

        try {
            $statement = Database::connection()->prepare($this->accountProfileQuery());
            $statement->execute(['username' => $username]);
            $account = $statement->fetch();

            if ($account) {
                $_SESSION['user']['id'] = $account['id'];
                $_SESSION['user']['display_name'] = $account['name'] ?: $account['username'];

                return $account;
            }
        } catch (Throwable) {
            // Keep the profile visible while the database is being set up.
        }

        return [
            'id' => $sessionUser['id'] ?? null,
            'username' => $username,
            'email' => null,
            'status' => 1,
            'activated' => 1,
            'active' => 1,
            'role' => 0,
            'tongnap' => 0,
            'tongNapThang' => 0,
            'tongNapTuan' => 0,
            'quanew' => 0,
            'money' => 0,
            'totalmoney' => 0,
            'ban' => 0,
        ];
    }

    private function depositHistory(array $sessionUser): array
    {
        try {
            $account = $this->accountProfile($sessionUser);

            if (empty($account['id'])) {
                return [];
            }

            $statement = Database::connection()->prepare(
                'select id, amount, payment_method, transaction_code, status, description, created_at from deposit_history where user_id = :user_id order by created_at desc, id desc limit 20'
            );
            $statement->execute(['user_id' => $account['id']]);

            return $statement->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    private function feedbackHistory(array $sessionUser): array
    {
        try {
            $account = $this->accountProfile($sessionUser);

            if (empty($account['id'])) {
                return [];
            }

            $statement = Database::connection()->prepare(
                'select id, type, subject, content, status, created_at
                 from user_feedback
                 where user_id = :user_id
                 order by created_at desc, id desc
                 limit 20'
            );
            $statement->execute(['user_id' => $account['id']]);

            return $statement->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    private function accountWithPassword(string $username): ?array
    {
        $statement = Database::connection()->prepare($this->accountWithPasswordQuery());
        $statement->execute(['username' => $username]);
        $account = $statement->fetch();

        return $account ?: null;
    }

    private function databaseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 't', 'yes', 'y'], true);
    }

    private function paymentEnabled(): bool
    {
        return $this->databaseBool(env('PAYMENT_ENABLED', 'true'));
    }

    private function registerBonusAmount(): int
    {
        if (!$this->databaseBool(env('REGISTER_BONUS_ENABLED', 'true'))) {
            return 0;
        }

        return self::REGISTER_BONUS_AMOUNT;
    }

    private function accountForUsername(string $username): ?array
    {
        $statement = Database::connection()->prepare(
            'select id, username from users where username = :username limit 1'
        );
        $statement->execute(['username' => $username]);
        $account = $statement->fetch();

        return $account ?: null;
    }

    private function activeBankAccount(?int $id = null): ?array
    {
        try {
            $connection = Database::connection();
            $sql = 'select id, bank_name, bank_code, acc_num, acc_name, code, bank_rate
                    from bank_accounts
                    where is_active = true';
            $params = [];

            if ($id !== null) {
                $sql .= ' and id = :id';
                $params['id'] = $id;
            }

            $sql .= ' order by sort_order asc, id asc limit 1';
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            $account = $statement->fetch();

            if ($account) {
                return $account;
            }
        } catch (Throwable) {
            // Fall back to environment config for first-time setup.
        }

        $bankCode = trim(env('VIETQR_BANK_CODE', env('WEB2M_BANK_CODE', '')));
        $accountNumber = trim(env('VIETQR_ACCOUNT_NUMBER', env('WEB2M_BANK_ACCOUNT_NUMBER', '')));
        $accountName = trim(env('VIETQR_ACCOUNT_NAME', env('WEB2M_BANK_ACCOUNT_NAME', '')));
        $prefix = trim(env('VIETQR_BANK_PREFIX', env('WEB2M_BANK_PREFIX', $bankCode)));

        if ($bankCode === '' || $accountNumber === '' || $accountName === '' || $prefix === '') {
            return null;
        }

        return [
            'id' => 0,
            'bank_name' => trim(env('VIETQR_BANK_NAME', env('WEB2M_BANK_NAME', $bankCode))) ?: $bankCode,
            'bank_code' => $bankCode,
            'acc_num' => $accountNumber,
            'acc_name' => $accountName,
            'code' => strtoupper($prefix),
            'bank_rate' => (float) env('PAYMENT_COIN_RATE', '1'),
        ];
    }

    private function depositAmounts(): array
    {
        $configured = trim(env('PAYMENT_AMOUNTS', '10000,20000,50000,100000,200000,500000,1000000'));
        $amounts = [];

        foreach (explode(',', $configured) as $value) {
            $amount = (int) trim($value);

            if ($amount > 0) {
                $amounts[$amount] = ['amount' => $amount];
            }
        }

        return array_values($amounts);
    }

    private function vietQrUrl(array $bankAccount, int $amount, string $transferContent): string
    {
        return sprintf(
            'https://api.vietqr.io/%s/%s/%d/%s/qr_only.jpg',
            rawurlencode((string) $bankAccount['bank_code']),
            rawurlencode((string) $bankAccount['acc_num']),
            $amount,
            rawurlencode($transferContent)
        );
    }

    private function coinAmount(int|float $amount, float $rate): int
    {
        return (int) floor((float) $amount * $rate);
    }

    private function buildTransferContent(array $bankAccount, array $account): string
    {
        $prefix = strtoupper(trim((string) ($bankAccount['code'] ?? '')));
        $userId = max(0, (int) ($account['id'] ?? 0));
        $paymentRef = random_int(0, 999);

        return $prefix . $userId . str_pad((string) $paymentRef, self::PAYMENT_REF_WIDTH, '0', STR_PAD_LEFT);
    }

    private function effectiveBankRate(array $bankAccount): float
    {
        $rate = (float) $bankAccount['bank_rate'];
        $campaign = $this->activePromotionCampaign();

        if ($campaign) {
            $rate *= 1 + ((float) $campaign['bonus_percent'] / 100);
        }

        return $rate;
    }

    private function activePromotionCampaign(): ?array
    {
        try {
            $statement = Database::connection()->query(
                'select id, name, bonus_percent
                 from promotion_campaigns
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

    private function validBankWebhookToken(): bool
    {
        $expectedTokens = array_filter([
            trim(env('WEB2M_ACCESS_TOKEN', '')),
            trim(env('BANK_WEBHOOK_TOKEN', '')),
        ]);

        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if ($authorization === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strtolower((string) $name) === 'authorization') {
                    $authorization = (string) $value;
                    break;
                }
            }
        }

        if ($expectedTokens === [] || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return false;
        }

        $provided = trim($matches[1]);

        foreach ($expectedTokens as $expected) {
            if (hash_equals($expected, $provided)) {
                return true;
            }
        }

        return false;
    }

    private function processBankWebhookItem(array $item, array $payload): void
    {
        $description = trim((string) ($item['description'] ?? ''));
        $transactionId = trim((string) ($item['transactionID'] ?? $item['transaction_id'] ?? ''));
        $bank = strtoupper(trim((string) ($item['bank'] ?? '')));
        $amount = $this->moneyValue($item['amount'] ?? null);

        if ($description === '' || $transactionId === '' || $bank === '' || $amount <= 0) {
            return;
        }

        $match = $this->userIdFromTransferContent($description, $bank);

        if ($match === null) {
            return;
        }

        [$userId, $bankAccount, $transferCode] = $match;
        $connection = Database::connection();
        $rawPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $connection->beginTransaction();

        try {
            $exists = $connection->prepare('select id from payments where trans_id = :trans_id limit 1');
            $exists->execute(['trans_id' => $transferCode]);

            if ($exists->fetch()) {
                $connection->commit();
                return;
            }

            $user = $connection->prepare(
                'select id, username, balance, tongnap, tongNapThang, tongNapTuan, '
                . self::MONTHLY_RESET_COLUMN . ', '
                . self::WEEKLY_RESET_COLUMN . '
                 from users
                 where id = :id
                 limit 1
                 for update'
            );
            $user->execute(['id' => $userId]);
            $userRow = $user->fetch();

            if (!$userRow) {
                $connection->commit();
                return;
            }

            $rate = $bankAccount ? $this->effectiveBankRate($bankAccount) : (float) env('PAYMENT_COIN_RATE', '1');
            $coinAmount = $this->coinAmount($amount, $rate);
            $this->applySuccessfulDeposit($connection, $userRow, $amount, $coinAmount);
            $this->activateUserOnEligibleDeposit($connection, $userId, $amount);

            $payment = $connection->prepare(
                'insert into payments (user_id, currency_id, type, amount, balance, description, extra, created_at, updated_at, trans_id, player_name, received)
                 values (:user_id, :currency_id, :type, :amount, :balance, :description, :extra, now(), now(), :trans_id, :player_name, :received)'
            );
            $payment->execute([
                'user_id' => $userId,
                'currency_id' => 'VND',
                'type' => 1,
                'amount' => $amount,
                'balance' => max(0, (int) ($userRow['balance'] ?? 0)) + $coinAmount,
                'description' => 'Nap tien thanh cong qua VietQR: ' . $description,
                'extra' => json_encode([
                    'bank_transaction_id' => $transactionId,
                    'bank' => $bank,
                    'coin_amount' => $coinAmount,
                    'raw_payload' => json_decode($rawPayload, true),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'trans_id' => $transferCode,
                'player_name' => (string) ($userRow['username'] ?? ''),
                'received' => 1,
            ]);
            $paymentId = (int) $connection->lastInsertId();

            $deposit = $connection->prepare(
                'insert into deposits (user_id, payment_id, amount, coin_amount, payment_method, transaction_code, status, description)
                 values (:user_id, :payment_id, :amount, :coin_amount, :payment_method, :transaction_code, :status, :description)'
            );
            $deposit->execute([
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'coin_amount' => $coinAmount,
                'payment_method' => 'vietqr',
                'transaction_code' => $transactionId,
                'status' => 'success',
                'description' => "N\u{1EA1}p ti\u{1EC1}n th\u{00E0}nh c\u{00F4}ng qua VietQR: " . $description,
            ]);

            $history = $connection->prepare(
                'insert into deposit_history (user_id, amount, payment_method, transaction_code, status, description)
                 values (:user_id, :amount, :payment_method, :transaction_code, :status, :description)'
            );
            $history->execute([
                'user_id' => $userId,
                'amount' => $amount,
                'payment_method' => 'vietqr',
                'transaction_code' => $transactionId,
                'status' => 'success',
                'description' => "N\u{1EA1}p ti\u{1EC1}n th\u{00E0}nh c\u{00F4}ng qua VietQR: " . $description . ' - coin: ' . $coinAmount,
            ]);

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function userIdFromTransferContent(string $description, string $bank): ?array
    {
        $prefixes = [$bank];
        $bankAccounts = [];

        try {
            $statement = Database::connection()->query(
                'select id, bank_name, bank_code, code, bank_rate from bank_accounts where is_active = true order by sort_order asc, id asc'
            );
            $bankAccounts = $statement->fetchAll();

            foreach ($bankAccounts as $account) {
                $prefixes[] = (string) $account['code'];
            }
        } catch (Throwable) {
            // Use webhook bank as the minimum viable prefix.
        }

        foreach (array_unique(array_filter(array_map('strtoupper', $prefixes))) as $prefix) {
            if (!preg_match('/(' . preg_quote($prefix, '/') . ')([0-9]+)/i', $description, $matches)) {
                continue;
            }

            $resolvedUserId = $this->extractUserIdFromTransferDigits((string) ($matches[2] ?? ''));

            if ($resolvedUserId === null) {
                continue;
            }

            $matchedAccount = null;

            foreach ($bankAccounts as $account) {
                if (strtoupper((string) $account['code']) === $prefix) {
                    $matchedAccount = $account;
                    break;
                }
            }

            return [$resolvedUserId, $matchedAccount, strtoupper((string) $matches[1] . (string) $matches[2])];
        }

        return null;
    }

    private function extractUserIdFromTransferDigits(string $digits): ?int
    {
        if (!ctype_digit($digits) || strlen($digits) <= self::PAYMENT_REF_WIDTH) {
            return null;
        }

        $userIdDigits = substr($digits, 0, -self::PAYMENT_REF_WIDTH);
        $paymentRefDigits = substr($digits, -self::PAYMENT_REF_WIDTH);

        if ($userIdDigits === '' || !ctype_digit($userIdDigits) || !ctype_digit($paymentRefDigits)) {
            return null;
        }

        return max(0, (int) $userIdDigits) ?: null;
    }

    private function shouldResetTongNapThang(mixed $resetAt): bool
    {
        if (!$resetAt) {
            return true;
        }

        $timestamp = strtotime((string) $resetAt);

        if ($timestamp === false) {
            return true;
        }

        return date('Y-m', $timestamp) !== date('Y-m');
    }

    private function shouldResetTongNapTuan(mixed $resetAt): bool
    {
        if (!$resetAt) {
            return true;
        }

        $timestamp = strtotime((string) $resetAt);

        if ($timestamp === false) {
            return true;
        }

        return date('o-W', $timestamp) !== date('o-W');
    }

    private function moneyValue(mixed $value): float
    {
        $normalized = str_replace(['.', ',', ' ', 'VND', 'vnd'], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function webhookPayload(): array
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $json = json_decode($rawBody, true);

        if (is_array($json)) {
            return $json;
        }

        return $_POST ?: $_GET;
    }

    private function pullFlash(string $key): ?string
    {
        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return is_string($message) ? $message : null;
    }

    private function pullArrayFlash(string $key): array
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return is_array($value) ? $value : [];
    }

    private function forgotPasswordStep(): string
    {
        $state = $_SESSION['forgot_password'] ?? null;

        if (!is_array($state) || !$this->validForgotState($state)) {
            return 'request';
        }

        return !empty($state['verified']) ? 'reset' : 'otp';
    }

    private function validForgotState(array $state): bool
    {
        return !empty($state['user_id'])
            && !empty($state['username'])
            && !empty($state['email'])
            && !empty($state['otp'])
            && (int) ($state['expires_at'] ?? 0) >= time();
    }

    private function renderForgotPasswordStep(string $step, ?string $error = null, ?string $success = null, int $status = 200): never
    {
        $state = $_SESSION['forgot_password'] ?? [];

        View::render('home', $this->homePayload([
            'title' => "Qu\u{00EA}n m\u{1EAD}t kh\u{1EA9}u Ninja School Blue",
            'authModal' => 'forgot',
            'forgotError' => $error,
            'forgotSuccess' => $success,
            'forgotStep' => $step,
            'forgotUsername' => $state['username'] ?? '',
            'forgotEmail' => $state['email'] ?? '',
        ]), $status);
    }

    private function sendOtpEmail(string $email, string $username, string $otp): bool
    {
        Mail::to($email)->send(new PasswordOtpMail($otp, 10));

        return true;
    }

    private function databaseErrorMessage(string $fallback, ?Throwable $exception = null): string
    {
        if (!in_array(strtolower((string) env('APP_DEBUG', 'false')), ['1', 'true', 't', 'yes', 'y'], true)) {
            return $fallback;
        }

        if (!$exception) {
            return $fallback;
        }

        return $fallback . ' Chi tiet: ' . $exception->getMessage();
    }

    private function userTableSchema(): array
    {
        if ($this->userTableSchema !== null) {
            return $this->userTableSchema;
        }

        try {
            $rows = Database::connection()->query('show columns from users')->fetchAll();
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

        return isset($schema['status'], $schema['active'], $schema['role']);
    }

    private function userColumnLength(string $column, int $default): int
    {
        $schema = $this->userTableSchema();
        $type = (string) ($schema[strtolower($column)]['Type'] ?? '');

        if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
            return (int) $matches[1];
        }

        return $default;
    }

    private function userHasColumn(string $column): bool
    {
        $schema = $this->userTableSchema();

        return isset($schema[strtolower($column)]);
    }

    private function accountWithPasswordQuery(): string
    {
        if ($this->usesModernUserSchema()) {
            return 'select id, username, password, status, activated, active, role from users where lower(username) = lower(:username) limit 1';
        }

        return 'select id, username, password, ban, is_active, type_admin from users where lower(username) = lower(:username) limit 1';
    }

    private function accountProfileQuery(): string
    {
        if ($this->usesModernUserSchema()) {
            return 'select id, name, username, email, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, balance as money, tongnap as totalmoney, case when status <> 1 or activated = 0 or active = 0 then 1 else 0 end as ban, created_at, updated_at from users where username = :username limit 1';
        }

        return 'select id, name, username, email, 1 as status, 1 as activated, is_active as active, type_admin as role, totalmoney as tongnap, tongnapthang as tongNapThang, 0 as tongNapTuan, money as quanew, money, totalmoney, ban, created_at, updated_at from users where username = :username limit 1';
    }

    private function isAccountAccessible(array $account): bool
    {
        if ($this->usesModernUserSchema()) {
            return (int) ($account['status'] ?? 0) === 1
                && $this->databaseBool($account['activated'] ?? true)
                && $this->databaseBool($account['active'] ?? true);
        }

        return !$this->databaseBool($account['ban'] ?? false)
            && $this->databaseBool($account['is_active'] ?? true);
    }

    private function applySuccessfulDeposit(\PDO $connection, array $userRow, float $amount, int $coinAmount): void
    {
        $connection->prepare(
            'update users
             set balance = :balance,
                 tongnap = :tongnap,
                 tongNapThang = :tong_nap_thang,
                 tongNapTuan = :tong_nap_tuan,
                 ' . self::MONTHLY_RESET_COLUMN . ' = :monthly_reset_at,
                 ' . self::WEEKLY_RESET_COLUMN . ' = :weekly_reset_at,
                 updated_at = now()
             where id = :user_id'
        )->execute([
            'balance' => max(0, (int) ($userRow['balance'] ?? 0)) + $coinAmount,
            'tongnap' => max(0, (int) ($userRow['tongnap'] ?? 0)) + (int) $amount,
            'tong_nap_thang' => $this->nextMonthlyDepositTotal($userRow, $amount),
            'tong_nap_tuan' => $this->nextWeeklyDepositTotal($userRow, $amount),
            'monthly_reset_at' => date('Y-m-d H:i:s'),
            'weekly_reset_at' => date('Y-m-d H:i:s'),
            'user_id' => (int) ($userRow['id'] ?? 0),
        ]);
    }

    private function activateUserOnEligibleDeposit(\PDO $connection, int $userId, float $amount): void
    {
        if ($amount < self::ACTIVATION_DEPOSIT_AMOUNT) {
            return;
        }

        $updates = [
            'status = 1',
            'activated = 1',
            'active = 1',
        ];

        if ($this->userHasColumn('kh')) {
            $updates[] = 'kh = 1';
        }

        $updates[] = 'updated_at = now()';

        $connection->prepare(
            'update users
             set ' . implode(",\n                 ", $updates) . '
             where id = :id'
        )->execute(['id' => $userId]);
    }

    private function nextMonthlyDepositTotal(array $userRow, float $amount): int
    {
        $current = $this->shouldResetTongNapThang($userRow[self::MONTHLY_RESET_COLUMN] ?? null)
            ? 0
            : max(0, (int) ($userRow['tongNapThang'] ?? 0));

        return $current + (int) $amount;
    }

    private function nextWeeklyDepositTotal(array $userRow, float $amount): int
    {
        $current = $this->shouldResetTongNapTuan($userRow[self::WEEKLY_RESET_COLUMN] ?? null)
            ? 0
            : max(0, (int) ($userRow['tongNapTuan'] ?? 0));

        return $current + (int) $amount;
    }
}
