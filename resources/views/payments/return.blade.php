{{-- resources/views/payments/return.blade.php --}}
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نتيجة الدفع</title>
    <style>
        :root{
            --green: #28a745;
            --red: #dc3545;
            --bg: #f7f9fc;
            --card: #ffffff;
            --muted: #6c757d;
        }
        html,body{
            height:100%;
            margin:0;
            font-family: "Segoe UI", Tahoma, Arial, "Noto Naskh Arabic", sans-serif;
            background: linear-gradient(180deg,#eef5fb 0%, #f7f9fc 100%);
            color: #222;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        .wrap{
            min-height:100%;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            box-sizing:border-box;
        }
        .card{
            width:100%;
            max-width:760px;
            background:var(--card);
            border-radius:12px;
            box-shadow: 0 10px 30px rgba(15,23,42,0.08);
            padding:28px;
            box-sizing:border-box;
            text-align:right;
        }
        .status {
            display:flex;
            align-items:center;
            gap:16px;
        }
        .status .icon{
            width:84px;
            height:84px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:40px;
            color:#fff;
            flex-shrink:0;
        }
        .status .title{
            font-size:20px;
            font-weight:700;
        }
        .meta{
            margin-top:18px;
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:12px;
        }
        .meta .meta-item{
            background: #fbfcfe;
            padding:12px;
            border-radius:8px;
            border:1px solid #f1f5f9;
        }
        .meta .label{ font-size:12px; color:var(--muted); margin-bottom:6px; display:block; }
        .meta .value{ font-weight:600; font-size:15px; }

        .message{
            margin-top:18px;
            padding:14px;
            border-radius:8px;
            background: #fff;
            border:1px dashed #eef2f7;
            color:#333;
        }

        .actions{
            margin-top:20px;
            display:flex;
            gap:12px;
            justify-content:flex-end;
            flex-wrap:wrap;
        }
        .btn{
            padding:10px 16px;
            border-radius:8px;
            border:none;
            cursor:pointer;
            font-weight:600;
            text-decoration:none;
            display:inline-flex;
            gap:8px;
            align-items:center;
        }
        .btn-primary{
            background: var(--green);
            color:#fff;
        }
        .btn-outline{
            background:transparent;
            color:var(--muted);
            border:1px solid #e6e9ef;
        }
        .small{
            font-size:13px;
            color:var(--muted);
            margin-top:12px;
        }

        /* responsive */
        @media (max-width:600px){
            .meta{ grid-template-columns: 1fr; }
            .status .title{ font-size:18px; }
            .status .icon{ width:64px; height:64px; font-size:32px; }
            .card{ padding:18px; }
            .actions{ justify-content:stretch; }
            .btn{ width:100%; justify-content:center; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card" role="main" aria-live="polite">
            @php
                // المتغيرات المتوقعة من الكنترولر
                $status = $status ?? strtolower(request('orderStatus') ?: request('status') ?: 'unknown'); // e.g. PAID or FAILED
                $merchantRef = $merchantRefNum ?? request('merchantRefNum') ?? request('merchantRefNumber') ?? null;
                $fawryRef = $referenceNumber ?? request('referenceNumber') ?? request('fawryRefNumber') ?? null;
                $amount = $amount ?? request('amount') ?? request('paymentAmount') ?? null;
                $message = $message ?? $message ?? request('message') ?? null;
                $isSuccess = in_array(strtoupper($status), ['PAID','SUCCESS','COMPLETED','PAID_SUCCESS','SUCCESSFUL']);
            @endphp

            <div class="status">
                <div class="icon" style="background: {{ $isSuccess ? 'var(--green)' : 'var(--red)' }};">
                    @if($isSuccess)
                        ✓
                    @else
                        ✕
                    @endif
                </div>

                <div>
                    <div class="title">
                        @if($isSuccess)
                            تم استلام الدفع بنجاح
                        @else
                            فشل أو إلغاء عملية الدفع
                        @endif
                    </div>
                    <div class="small">
                        @if($isSuccess)
                            شكراً — دفعت {{ $amount ? number_format((float)$amount,2) . ' EGP' : '' }}. يتم الآن معالجة تفعيل الحساب.
                        @else
                            حدثت مشكلة في عملية الدفع. حاول مرة أخرى أو تواصل مع الدعم.
                        @endif
                    </div>
                </div>
            </div>

            <div class="meta" aria-hidden="{{ $isSuccess ? 'false' : 'false' }}">
                <div class="meta-item">
                    <span class="label">رقم مرجع النظام (merchantRefNum)</span>
                    <div class="value">{{ $merchantRef ?? '__' }}</div>
                </div>
                <div class="meta-item">
                    <span class="label">رقم فوري/مرجع الدفع (Fawry Ref)</span>
                    <div class="value">{{ $fawryRef ?? '__' }}</div>
                </div>
                <div class="meta-item">
                    <span class="label">المبلغ</span>
                    <div class="value">{{ $amount ? number_format((float)$amount,2) . ' EGP' : '__' }}</div>
                </div>
                <div class="meta-item">
                    <span class="label">حالة العملية</span>
                    <div class="value">{{ strtoupper($status) }}</div>
                </div>
            </div>

            @if($message)
                <div class="message">
                    {{ $message }}
                </div>
            @endif

            <div class="actions" role="navigation">
                <!-- button to open mobile app using deep link (replace with your app scheme) -->
                <a href="myapp://payments/result?merchantRef={{ $merchantRef ?? '' }}&status={{ $status }}" class="btn btn-outline" title="فتح التطبيق">
                    فتح التطبيق
                </a>

                <!-- main action (go back to dashboard or show receipt) -->
                <a href="{{ url('/') }}" class="btn btn-primary" title="العودة للرئيسية">
                    العودة للرئيسية
                </a>
            </div>

            <div class="small" style="direction: rtl; text-align: right;">
                إذا كانت العملية ناجحة وانت لم تَرَ تنشيط الحساب بعد، رُبما سيحتاج النظام بضع ثوانٍ لمعالجة التأكيد. إذا لم يتم التفعيل خلال دقيقتين، تواصل مع الدعم مع إرفاق رقم المرجع أعلاه.
            </div>
        </div>
    </div>

    <script>
        // لو عايز تعيد التوجيه تلقائياً بعد نجاح الدفع إلى داخل التطبيق أو صفحة معينة:
        (function(){
            var isSuccess = {{ $isSuccess ? 'true' : 'false' }};
            if (isSuccess) {
                // إعادة توجيه للـ deep link بعد 2 ثانية (اختياري)
                setTimeout(function(){
                    // افتح التطبيق إن أمكن
                    window.location.href = "myapp://payments/result?merchantRef={{ $merchantRef ?? '' }}&status={{ $status }}";
                }, 2000);
            }
        })();
    </script>
</body>
</html>
