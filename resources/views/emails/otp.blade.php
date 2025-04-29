<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your OTP</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('{{ asset('dashboard/pngtree.png') }}') no-repeat top center;
            background-size: cover;
        }
        .email-container {
            background-color: white;
            max-width: 500px;
            margin: 80px auto;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .logo {
            margin-top: 20px;
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-align: left;
            padding-left: 30px;
        }
        .date {
            color: white;
            text-align: right;
            padding-right: 30px;
            margin-top: -40px;
            font-size: 14px;
        }
        .otp-title {
            font-size: 20px;
            margin: 20px 0;
        }
        .otp-body {
            font-size: 14px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .otp-code {
            font-size: 28px;
            letter-spacing: 15px;
            color: #c62828;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="logo">LADY DRIVER</div>
    <div class="date">{{ date('d M, Y') }}</div>

    <div class="email-container">
        <div class="otp-title">Your OTP</div>
        <div class="otp-body">
            Hey Tomy,<br><br>
            Thank you for choosing LADY DRIVER. Use the following OTP to complete your registration. OTP is valid for 5 minutes. Do not share this code with others, including LADY DRIVER team.
        </div>
        <div class="otp-code">{{ $otp }}</div>
    </div>

</body>
</html>
