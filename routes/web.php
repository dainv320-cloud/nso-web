<?php

use App\Controllers\SiteController;
use App\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

require_once base_path('src/bootstrap.php');

Route::get('/', fn () => (new SiteController())->home());

Route::get('/news', fn () => (new SiteController())->news('/news'));
Route::get('/tin-tuc', fn () => (new SiteController())->news('/tin-tuc'));
Route::get('/su-kien', fn () => (new SiteController())->news('/su-kien'));
Route::get('/tinh-nang', fn () => (new SiteController())->news('/tinh-nang'));
Route::get('/huong-dan', fn () => (new SiteController())->news('/huong-dan'));
Route::get('/thong-bao', fn () => (new SiteController())->news('/thong-bao'));
Route::get('/news/{slug}', fn (string $slug) => (new SiteController())->post($slug))->where('slug', '[a-z0-9-]+');

Route::get('/download', fn () => (new SiteController())->download());
Route::get('/payment', fn () => (new SiteController())->payment());
Route::post('/payment', fn () => (new SiteController())->submitPayment());
Route::get('/payment/status', fn () => (new SiteController())->paymentStatus());
Route::post('/payment/cancel', fn () => (new SiteController())->cancelPayment());
Route::get('/profile', fn () => (new SiteController())->profile());
Route::post('/profile/email', fn () => (new SiteController())->submitProfileEmail());
Route::post('/profile/password', fn () => (new SiteController())->submitProfilePassword());
Route::post('/profile/feedback', fn () => (new SiteController())->submitProfileFeedback());
Route::get('/login', fn () => (new SiteController())->login());
Route::post('/login', fn () => (new SiteController())->submitLogin());
Route::get('/forgot-password', fn () => (new SiteController())->forgotPassword());
Route::post('/forgot-password', fn () => (new SiteController())->submitForgotPassword());
Route::post('/forgot-password/verify-otp', fn () => (new SiteController())->verifyForgotPasswordOtp());
Route::post('/forgot-password/reset', fn () => (new SiteController())->resetForgotPassword());
Route::get('/register', fn () => (new SiteController())->register());
Route::post('/register', fn () => (new SiteController())->submitRegister());
Route::get('/logout', fn () => (new SiteController())->logout());
Route::get('/canh-bao-lua-dao', fn () => (new SiteController())->warning());
Route::get('/community', fn () => (new SiteController())->community());

Route::get('/admin/login', fn () => (new AdminController())->login());
Route::post('/admin/login', fn () => (new AdminController())->submitLogin());
Route::get('/admin/logout', fn () => (new AdminController())->logout());
Route::get('/admin', fn () => (new AdminController())->dashboard());
Route::get('/admin/users', fn () => (new AdminController())->users());
Route::get('/admin/users/create', fn () => (new AdminController())->userForm());
Route::get('/admin/users/{id}/edit', fn (int $id) => (new AdminController())->userForm($id));
Route::get('/admin/users/{id}/delete', fn (int $id) => (new AdminController())->confirmUserDelete($id));
Route::post('/admin/users/save', fn () => (new AdminController())->saveUser());
Route::post('/admin/users/delete', fn () => (new AdminController())->deleteUser());
Route::get('/admin/posts', fn () => (new AdminController())->posts());
Route::get('/admin/items', fn () => (new AdminController())->items());
Route::get('/admin/posts/create', fn () => (new AdminController())->postForm());
Route::get('/admin/posts/{id}/edit', fn (int $id) => (new AdminController())->postForm($id));
Route::get('/admin/posts/{id}/delete', fn (int $id) => (new AdminController())->confirmPostDelete($id));
Route::post('/admin/posts/save', fn () => (new AdminController())->savePost());
Route::post('/admin/posts/delete', fn () => (new AdminController())->deletePost());
Route::post('/admin/uploads/image', fn () => (new AdminController())->uploadImage());
Route::get('/admin/payments', fn () => (new AdminController())->payments());
Route::post('/admin/payments/toggle', fn () => (new AdminController())->togglePayment());
Route::get('/admin/payments/{id}/edit', fn (int $id) => (new AdminController())->paymentForm($id));
Route::get('/admin/feedbacks', fn () => (new AdminController())->feedbacks());
Route::get('/admin/feedbacks/{id}/edit', fn (int $id) => (new AdminController())->feedbackForm($id));
Route::get('/admin/banks', fn () => (new AdminController())->banks());
Route::get('/admin/banks/create', fn () => (new AdminController())->bankForm());
Route::get('/admin/banks/{id}/edit', fn (int $id) => (new AdminController())->bankForm($id));
Route::get('/admin/banks/{id}/delete', fn (int $id) => (new AdminController())->confirmBankDelete($id));
Route::post('/admin/banks/save', fn () => (new AdminController())->saveBank());
Route::post('/admin/banks/delete', fn () => (new AdminController())->deleteBank());
Route::get('/admin/downloads', fn () => (new AdminController())->downloads());
Route::get('/admin/downloads/create', fn () => (new AdminController())->downloadForm());
Route::get('/admin/downloads/{id}/edit', fn (int $id) => (new AdminController())->downloadForm($id));
Route::get('/admin/downloads/{id}/delete', fn (int $id) => (new AdminController())->confirmDownloadDelete($id));
Route::post('/admin/downloads/save', fn () => (new AdminController())->saveDownload());
Route::post('/admin/downloads/delete', fn () => (new AdminController())->deleteDownload());
Route::get('/admin/nin-phucloi', fn () => (new AdminController())->ninPhucLoi());
Route::get('/admin/nin-phucloi/create', fn () => (new AdminController())->ninPhucLoiForm());
Route::get('/admin/nin-phucloi/{id}/edit', fn (int $id) => (new AdminController())->ninPhucLoiForm($id));
Route::get('/admin/nin-phucloi/{id}/delete', fn (int $id) => (new AdminController())->confirmNinPhucLoiDelete($id));
Route::post('/admin/nin-phucloi/save', fn () => (new AdminController())->saveNinPhucLoi());
Route::post('/admin/nin-phucloi/delete', fn () => (new AdminController())->deleteNinPhucLoi());
Route::get('/admin/gift-codes', fn () => (new AdminController())->giftCodes());
Route::get('/admin/gift-codes/create', fn () => (new AdminController())->giftCodeForm());
Route::get('/admin/gift-codes/{id}/edit', fn (int $id) => (new AdminController())->giftCodeForm($id));
Route::get('/admin/gift-codes/{id}/delete', fn (int $id) => (new AdminController())->confirmGiftCodeDelete($id));
Route::post('/admin/gift-codes/save', fn () => (new AdminController())->saveGiftCode());
Route::post('/admin/gift-codes/delete', fn () => (new AdminController())->deleteGiftCode());
Route::get('/admin/rates', fn () => (new AdminController())->rates());
Route::get('/admin/rates/create', fn () => (new AdminController())->campaignForm());
Route::get('/admin/rates/{id}/edit', fn (int $id) => (new AdminController())->campaignForm($id));
Route::get('/admin/rates/{id}/delete', fn (int $id) => (new AdminController())->confirmCampaignDelete($id));
Route::post('/admin/rates/save', fn () => (new AdminController())->saveCampaign());
Route::post('/admin/rates/delete', fn () => (new AdminController())->deleteCampaign());

Route::get('/{slug}', function (string $slug) {
    $reserved = [
        'api',
        'payment',
        'download',
        'profile',
        'login',
        'register',
        'forgot-password',
        'logout',
        'community',
        'canh-bao-lua-dao',
        'admin',
    ];

    if (in_array($slug, $reserved, true)) {
        return (new SiteController())->notFound();
    }

    return (new SiteController())->post($slug);
})->where('slug', '[a-z0-9-]+');
