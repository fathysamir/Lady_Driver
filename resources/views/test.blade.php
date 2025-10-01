<!DOCTYPE html>
<html>

<head>
    <title>Live Location</title>
	<style>
    /* Reset خفيف */
    :root{
      --bg:#f7f8fb;
      --card:#ffffff;
      --muted:#6b7280;
      --accent:#0f172a;
      --primary:#0b5fff;
      --border:#e6e9ef;
      --radius:12px;
      font-family: "Segoe UI", Tahoma, "Noto Sans Arabic", system-ui, sans-serif;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      background:linear-gradient(180deg,var(--bg),#f3f5f9);
      color:var(--accent);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      padding:40px 20px;
      line-height:1.6;
    }

    .wrap{
      max-width:930px;
      margin:0 auto;
    }

    .card{
      background:var(--card);
      border:1px solid var(--border);
      border-radius:var(--radius);
      padding:28px;
      box-shadow:0 6px 20px rgba(12,24,48,0.06);
    }

    h1{
      margin:0 0 10px;
      font-size:26px;
    }
    .lead{
      color:var(--muted);
      margin:0 0 22px;
    }

    section + section{margin-top:18px}

    h2{
      margin:18px 0 10px;
      font-size:18px;
      color:var(--primary);
    }

    ul{padding-inline-start:1.05rem;margin:6px 0 14px}
    li{margin:8px 0}

    .meta{
      display:flex;
      gap:18px;
      flex-wrap:wrap;
      margin:10px 0 14px;
    }
    .meta .item{
      background:#fbfcff;
      border:1px solid var(--border);
      padding:10px 12px;
      border-radius:10px;
      min-width:160px;
    }
    .muted{color:var(--muted); font-size:14px}

    .contact{
      margin-top:16px;
      padding-top:14px;
      border-top:1px dashed var(--border);
    }

    /* Responsive */
    @media (max-width:640px){
      body{padding:18px}
      .card{padding:18px}
    }
  </style>
</head>

<body>
    <div class="wrap">
    <div class="card" role="main" aria-labelledby="policy-title">
      <h1 id="policy-title">سياسة الخصوصية — شركة ليـدي درايفر للنقل الذكي</h1>
      <p class="lead">
        مرحبًا بك في ليـدي درايفر. نولي خصوصية وسلامة وراحة مستخدماتنا أهمية قصوى. تشرح هذه السياسة كيف نجمع ونستخدم ونحمي المعلومات الشخصية وفقًا للتشريعات المصرية.
      </p>

      <div class="meta" aria-hidden="false">
        <div class="item">
          <div class="muted">الاسم التجاري</div>
          <div><strong>Lady Driver للنقل الذكي</strong></div>
        </div>
        <div class="item">
          <div class="muted">السجل التجاري</div>
          <div><strong>243941</strong></div>
        </div>
        <div class="item">
          <div class="muted">المقر</div>
          <div><strong>القاهرة، مصر</strong></div>
        </div>
      </div>

      <section>
        <h2>1. المعلومات التي نجمعها</h2>
        <h3 class="muted">أ. المعلومات الشخصية</h3>
        <ul>
          <li>الاسم الكامل</li>
          <li>رقم الهاتف</li>
          <li>البريد الإلكتروني</li>
          <li>عنوان المنزل أو نقاط الالتقاء المفضلة</li>
          <li>معلومات الدفع والفوترة (عند الحاجة)</li>
          <li>صورة البطاقة أو رخصة القيادة للسائقين</li>
        </ul>

        <h3 class="muted">ب. المعلومات التقنية</h3>
        <ul>
          <li>بيانات الموقع (Geolocation) لتحسين تجربة الخدمة</li>
          <li>نوع الجهاز ونظام التشغيل</li>
          <li>عنوان الـ IP لتحسين الأمان وكشف الأنماط المشبوهة</li>
          <li>ملفات تعريف الارتباط وتقنيات التتبع لتحليل الأداء</li>
        </ul>
      </section>

      <section>
        <h2>2. كيف نستخدم المعلومات</h2>
        <ul>
          <li>توفير خدمات نقل آمنة وفعّالة</li>
          <li>تخصيص تجربة المستخدم بناءً على التفضيلات</li>
          <li>معالجة المدفوعات وإصدار الفواتير بأمان</li>
          <li>إرسال إشعارات الرحلات، العروض، والتحديثات</li>
          <li>الامتثال للقوانين واللوائح المصرية</li>
          <li>تعزيز الأمان ومراقبة النشاطات المشبوهة</li>
        </ul>
      </section>

      <section>
        <h2>3. مشاركة المعلومات مع أطراف ثالثة</h2>
        <p class="muted">لا نقوم ببيع أو تأجير بياناتك لطرف ثالث. ومع ذلك، قد نشارك بعض البيانات في الحالات التالية:</p>
        <ul>
          <li>مزودو الخدمات (بوابات الدفع، استضافة سحابيّة، إلخ) لضمان عمل الخدمة بسلاسة</li>
          <li>الامتثال القانوني: عند طلب الجهات المختصة أو بمقتضى القانون</li>
          <li>حالات الطوارئ: لحماية سلامة المستخدمين أو الجمهور</li>
        </ul>
      </section>

      <section>
        <h2>4. أمن البيانات</h2>
        <p class="muted">نتخذ إجراءات صارمة لحماية بياناتك، مثل:</p>
        <ul>
          <li>تشفير البيانات الحساسة</li>
          <li>استخدام بروتوكولات آمنة أثناء النقل والتخزين</li>
          <li>تقييد الوصول للأشخاص المصرّح لهم فقط</li>
          <li>نظم كشف التسلل ومراجعات أمنيّة دورية</li>
          <li>تفعيل المصادقة الثنائية عند الحاجة</li>
          <li>تسجيل النشاطات لمراقبة محاولات الوصول غير المصرّح بها</li>
          <li>تدريب الفريق على ممارسات الخصوصية والأمن</li>
        </ul>
      </section>

      <section>
        <h2>5. حقوق المستخدم</h2>
        <ul>
          <li>الاطّلاع على بياناتك المخزنة</li>
          <li>تصحيح المعلومات غير الدقيقة</li>
          <li>طلب حذف البيانات (وفق الالتزامات القانونية)</li>
          <li>سحب الموافقة أو إيقاف الاستخدام عبر إعدادات الحساب</li>
          <li>الاعتراض على المعالجة لأغراض محددة (مثل التسويق)</li>
          <li>طلب تقييد المعالجة أو نقل البيانات عند الإمكان تقنياً</li>
        </ul>
      </section>

      <section>
        <h2>6. تعليق أو حذف الحساب</h2>
        <p class="muted">تحتفظ الشركة بحق تعليق أو حذف أي حساب في حالات مثل:</p>
        <ul>
          <li>انتهاك القوانين المصرية أو سياسات الشركة</li>
          <li>سلوك يهدد سلامة المستخدمين</li>
          <li>تقديم معلومات مضللة أو احتيالية</li>
          <li>استخدام الخدمة بشكل يخالف شروط الاستخدام (حجوزات وهمية، إلغاءات متكررة بدون مبرر، إلخ)</li>
          <li>استغلال المنصة لأنشطة احتيالية أو جنائية</li>
          <li>نشر محتوى مسيء أو غير لائق</li>
        </ul>
        <p class="muted">سيُبلغ المستخدمون بالأسباب والإجراءات، وقد يتاح لهم الاستئناف وفق سياسة الشركة.</p>
      </section>

      <section>
        <h2>7. العمولات والعقوبات المالية</h2>
        <p class="muted">تحتفظ الشركة بالحق في تعديل عمولات السائقين وفقًا للظروف الاقتصادية واحتياجات التشغيل.</p>
        <p class="muted">قد تُفرض عقوبات مالية على السائقين في حالات مثل:</p>
        <ul>
          <li>إهانة العملاء أو التمييز</li>
          <li>ممارسات تخالف المعايير المهنية أو تضر بسمعة الشركة</li>
        </ul>
        <p class="muted">وفي حال ارتكب زبون مخالفة قانونية أو اعتدى لفظيًّا أو جسديًّا، تحتفظ الشركة بحق اتخاذ الإجراءات القانونية اللازمة.</p>
      </section>

      <section>
        <h2>8. السن الأدنى</h2>
        <p class="muted">الحد الأدنى لاستخدام خدمات ليـدي درايفر هو 18 سنة. لا يُسمح للقُصّر باستخدام الخدمة إلا بمرافقة بالغ مسؤول.</p>
      </section>

      <section>
        <h2>9. حماية بيانات الأطفال والقُصّر</h2>
        <p class="muted">نحترم خصوصية الأطفال دون 18 عامًا ولا نجمع بياناتهم الشخصية دون موافقة صريحة من الوالد أو الوصي القانوني. إذا ثبت جمع بيانات طفل بدون موافقة، سنتخذ خطوات سريعة لحذفها.</p>
      </section>

      <section>
        <h2>10. تحديثات سياسة الخصوصية</h2>
        <p class="muted">قد نُعدّل هذه السياسة من وقت لآخر استجابةً للتغيّرات القانونية أو التشغيلية. سيُعلم المستخدمون بالتحديثات المهمة عبر البريد الإلكتروني أو التطبيق، وستحتوي التحديثات على تاريخ السريان وملخص للتغييرات.</p>
        <p class="muted">الاستمرار في استخدام الخدمة بعد التحديث يعني قبولك للسياسة المعدّلة.</p>
      </section>

      <section>
        <h2>11. سياسة مكافحة التمييز والاستغلال</h2>
        <p class="muted">نؤمن بالقيمة الإنسانية والكرامة ونعتمد سياسة عدم تسامح مطلق مع التمييز أو الاستغلال أو التحرش بأنواعه. أي انتهاك يؤدي إلى تعليق نهائي للحساب واتخاذ الإجراءات القانونية إذا لزم الأمر.</p>
        <p class="muted">المنصة مخصّصة لتمكين المرأة وتوفير بيئة آمنة، وهذا لا يعني تمييزًا ضد الرجال، بل هدفنا توفير حماية وخصوصية أكبر للنساء في التنقل.</p>
      </section>

      <section class="contact" aria-labelledby="contact-title">
        <h2 id="contact-title">تواصل معنا</h2>
        <p class="muted">
          لأي استفسار بخصوص سياسة الخصوصية أو بياناتك الشخصية، تواصل معنا عبر:
        </p>
        <ul>
          <li><strong>البريد الإلكتروني:</strong> <a href="mailto:[email protected]">[email protected]</a></li>
          <li><strong>الهاتف:</strong> 01100362888 / 01154695582</li>
          <li><strong>العنوان:</strong> القاهرة، مصر</li>
          <li><strong>الموقع:</strong> lady-driver.com</li>
        </ul>
      </section>

     
    </div>
  </div>
</body>

</html>
