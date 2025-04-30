<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your OTP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .background {
            background-image: url('https://api.lady-driver.com/dashboard/pngtree.png');
            background-repeat: no-repeat;
            background-size: cover;
            padding: 20px;
        }

        .email-container {
            max-width: 500px;
            margin: auto;
            background-color: #ffffff;
            padding: 30px 20px;
            border-radius: 16px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header {
            display: flex;
            justify-content: space-between;
            padding: 0 10px;
            color: white;
            font-weight: bold;
        }

        .logo-text {
            font-size: 20px;
        }

        .date {
            font-size: 14px;
        }

        .logo-img {
            width: 160px;
            height: 160px;
            border-radius: 12px;
            margin: 20px auto;
        }

        .otp-title {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .otp-body {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .otp-code {
            font-size: 28px;
            letter-spacing: 10px;
            color: #c62828;
            font-weight: bold;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 20px 10px;
            }

            .logo-img {
                width: 120px;
                height: 120px;
            }

            .otp-code {
                letter-spacing: 5px;
                font-size: 24px;
            }

            .otp-title {
                font-size: 16px;
            }

            .header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="background">
        <div class="header">
            <div class="logo-text">LADY DRIVER</div>
            <div class="date">{{ date('d M, Y') }}</div>
        </div>

        <div class="email-container">
            <img src="https://api.lady-driver.com/dashboard/logo2.jpeg" alt="Logo" class="logo-img">
            <div class="otp-title">Hello {{ $name }},<br>Your OTP is here, use it before it gets bored 😉</div>
            <div class="otp-body">
                Thank you for choosing LADY DRIVER. Use the following OTP to complete your registration. The OTP is valid for 5 minutes. Do not share this code with anyone, including LADY DRIVER team.
            </div>
            <div class="otp-code">{{ $otp }}</div>
        </div>
    </div>
</body>
</html>
