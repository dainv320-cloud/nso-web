<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Mã OTP đặt lại mật khẩu</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827;">
    <h2>Mã OTP đặt lại mật khẩu</h2>
    <p>Bạn vừa yêu cầu đặt lại mật khẩu. Vui lòng sử dụng mã OTP bên dưới:</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #f97316;">
        {{ $otp }}
    </p>
    <p>Mã OTP có hiệu lực trong {{ $ttlMinutes }} phút.</p>
    <p>Nếu bạn không yêu cầu thao tác này, vui lòng bỏ qua email này.</p>
</body>
</html>
