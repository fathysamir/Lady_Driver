{{-- <h3>مرحباً {{ $name }}</h3>

<p>لقد تلقينا طلب إعادة تعيين كلمة المرور لحسابك.</p>

<p>
    اضغط على الزر أدناه لتعيين كلمة المرور الجديدة:
</p>

<a href="{{ $resetUrl }}" style="display:inline-block;padding:10px 20px;background:#3490dc;color:#fff;text-decoration:none;border-radius:5px;">
    إعادة تعيين كلمة المرور
</a>

<p>إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد.</p>

<p>مع تحيات فريق <strong>Ifikra Dev</strong></p> --}}


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
   
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            /* background: url('https://api.lady-driver.com/dashboard/pngtree.png') no-repeat top center;
            background-size: cover; */
        }

        .email-container {
            background-color: white;
            max-width: 500px;
            margin: 80px auto;
            padding: 10px 30px 30px 30px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .kk {
            height: 604;

        }

        .kk_container {
            display: flex;
            padding-top: 20px;
        }

        .logo {
            width: 50%;
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-align: left;
            padding-left: 30px;
        }

        .date {
            width: 50%;
            color: white;
            text-align: right;
            padding-right: 30px;
            font-weight: bold;
            font-size: 14px;
        }

        .otp-title {
            font-size: 20px;
            margin-bottom: 20px;
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

        .img_ {
            width: 200px;
            height: 200px;
            border-radius: 14px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 5px 15px 15px 5px;
                max-width: 330px;
            }

            .img_ {
                width: 120px;
                height: 120px;
                border-radius: 10px;
            }

            .otp-code {
                letter-spacing: 5px;
                font-size: 24px;
                color: #c62828;
            }

            .otp-title {
                font-size: 16px;
                margin-bottom: 10px;
            }
            .logo {
                font-size: 16px;
                padding-left: 15px;
            }
            .date {
                font-size: 10px;
                padding-right: 15px;
            }

           
        }
    </style>
</head>

<body>
    <div class="kk"
        style="background-image: url('https://api.lady-driver.com/dashboard/pngtree.png'); background-repeat: no-repeat;  background-size: cover;">
        <div class="kk_container">
            <div class="logo">LADY DRIVER</div>
            <div class="date">{{ date('d M, Y') }}</div>
        </div>


        <div class="email-container">
            <div><img class="img_" src="https://api.lady-driver.com/dashboard/logo2.jpeg"></div>
            <div class="otp-title">Hello {{ $name }},<br>This is link for reset password : <a href="{{ $resetUrl }}">👉🏻 Here</a> <br> use it before it gets bored 😉
            </div>
            <div class="otp-body">

                Thank you for choosing LADY DRIVER. Use the following OTP to complete your registration. OTP is valid
                for 5 minutes. Do not share this code with others, including LADY DRIVER team.
            </div>
            <div class="otp-code">{{ $otp }}</div>
        </div>
    </div>


</body>

</html>
