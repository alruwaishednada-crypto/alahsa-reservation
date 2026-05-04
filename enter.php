<?php
session_start();
require("DbHandler.php"); // يجب تضمين ملف معالج قاعدة البيانات

// إذا ما فيه جلسة للمستخدم، رجعه للصفحة الرئيسية (login)
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$permit_details = null;
$error_message = "";

// ==========================================================
// ====== جلب معلومات التصريح للعرض بناءً على permit_id ======
// ==========================================================
if(isset($_GET['permit_id'])){
    $permit_id = intval($_GET['permit_id']);
    
    $db = new DbHandler();
    $db->dbConnect();
    
    // استخدام الدالة الجديدة لجلب كل التفاصيل
    $permit_details = $db->getPermitDetails($permit_id);
    
    if(!$permit_details){
        $error_message = "لم يتم العثور على معلومات التصريح المطلوبة. الرجاء التأكد من الرابط.";
    } 
    // شرط أمان إضافي: التأكد من أن التصريح يخص المستخدم الحالي
    else if ($permit_details['user_id'] !== $_SESSION['user_id']) {
        $error_message = "لا تملك صلاحية الوصول لهذا التصريح.";
        $permit_details = null;
    }
} else {
    $error_message = "الرجاء تحديد رقم تصريح لعرضه.";
}

// تهيئة المتغيرات للعرض في HTML/JS
$permit_number = $permit_details['permit_number'] ?? 'غير متوفر';
$barcode_value = $permit_details['barcode'] ?? 'غير متوفر';
$full_name = $permit_details['full_name'] ?? 'غير متوفر';
$national_id = $permit_details['user_id'] ?? 'غير متوفر';
$location_name = $permit_details['location_name'] ?? 'غير متوفر';
$permit_date = isset($permit_details['available_date']) ? date('Y/m/d', strtotime($permit_details['available_date'])) : 'غير متوفر';
$time_start = $permit_details['available_time_start'] ?? '';
$time_end = $permit_details['available_time_end'] ?? '';
$permit_time = ($time_start && $time_end) ? "من: {$time_start} إلى: {$time_end}" : 'غير متوفر';
$status = $permit_details['status'] ?? 'غير متوفر';
$expires_at = isset($permit_details['expires_at']) ? date('Y/m/d', strtotime($permit_details['expires_at'])) : 'غير متوفر';
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصريح الدخول - أمانة الأحساء</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    
    <style>
        /* (كود CSS كما هو، مع بعض التعديلات ليتناسب مع البيانات الديناميكية) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding-bottom: 30px;
        }

        /* الهيدر */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 3px solid #7c1515;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            border: 2px solid #7c1515;
            border-radius: 8px;
            background: linear-gradient(135deg, #7c1515 0%, #c9a36a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            padding: 5px;
        }

        .logo-text h1 {
            font-size: 18px;
            color: #7c1515;
            line-height: 1.3;
        }

        .logo-text h1:first-child {
            font-weight: bold;
        }

        .logo-text h1:last-child {
            font-size: 16px;
            color: #555;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .home-btn {
            background: #f8f9fa;
            color: #555;
            border: 1px solid #dee2e6;
        }

        .permits-btn {
            background: #c9a36a;
            color: white;
        }

        .logout-btn {
            background: #7c1515;
            color: white;
        }

        .header-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .home-btn:hover {
            background: #e9ecef;
            color: #333;
        }

        /* شريط التقدم المضاف */
        .progress-container {
            max-width: 800px;
            margin: 20px auto 30px auto;
            padding: 0 20px;
        }

        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin-bottom: 15px;
        }

        .progress-line {
            position: absolute;
            top: 15px;
            left: 20%;
            right: 20%;
            height: 3px;
            background-color: #ddd;
            z-index: 1;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
        }

        .step-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-bottom: 8px;
            border: 3px solid #ddd;
        }

        .step-circle.completed {
            background-color: #28a745;
            border-color: #28a745;
        }

        .step-circle.active {
            background-color: #7c1515;
            border-color: #7c1515;
        }

        .step-label {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            text-align: center;
        }

        .step-label.completed {
            color: #28a745;
        }

        .step-label.active {
            color: #7c1515;
        }

        /* المحتوى الرئيسي */
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #7c1515;
            font-size: 28px;
            font-weight: bold;
            position: relative;
            padding-bottom: 10px;
        }

        .page-title:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 35%;
            right: 35%;
            height: 3px;
            background-color: #c9a36a;
        }

        .permit-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid #7c1515;
        }

        .permit-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #c9a36a;
        }

        .permit-number {
            font-size: 20px;
            color: #7c1515;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }

        .status-badge.نشط {
            background-color: #d4edda;
            color: #155724;
        }
        .status-badge.مكتمل, .status-badge.منتهي {
            background-color: #f8d7da;
            color: #721c24;
        }

        .permit-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }

        .detail-value {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 16px;
            min-height: 44px;
            display: flex;
            align-items: center;
        }

        .barcode-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            justify-content: center;
            flex-direction: column; /* جعل الباركود والرقم أسفل بعض */
        }

        .barcode-container {
            text-align: center;
        }
        
        /* تعديل CSS ليناسب SVG الباركود الذي تولده مكتبة JsBarcode */
        .barcode-section svg {
            max-width: 100%; 
            height: 80px;
        }

        .barcode-number {
            margin-top: 5px;
            font-family: monospace;
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }

        .time-section {
            background-color: #f0f8f0;
            border: 1px solid #7c1515;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }

        .time-value {
            font-size: 18px;
            font-weight: bold;
            color: #7c1515;
            margin-top: 5px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .print-btn {
            background-color: #c9a36a;
            color: white;
        }

        .download-btn {
            background-color: #7c1515;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* التذييل */
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            color: #777;
            font-size: 14px;
            border-top: 1px solid #eee;
        }

        /* رسالة الخطأ */
        .error {
            background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
            padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;
        }


        /* التكيف مع الشاشات الصغيرة */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            .header-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .permit-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .page-title:after {
                left: 25%;
                right: 25%;
            }

            .barcode-section {
                flex-direction: column;
                text-align: center;
            }

            .actions {
                flex-direction: column;
            }
        }
        /* CSS المودال */
        .modal {
            display: none; /* مهم جداً */
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content 
        {
            background-color: #fff;
            padding: 30px 40px; 
            border-radius: 12px;
            text-align: center;
            max-width: 400px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-content p 
        {
            font-size: 18px; 
            color: #333;
            margin-bottom: 20px;
        }
        .modal-buttons button 
        {
            padding: 10px 25px;
            margin: 0 10px;
            border: none;
            border-radius: 6px; 
            font-weight: bold; 
            cursor: pointer;
        }
        #confirmLogout 
        {
            background-color: #c9a36a;
            color: white;
        }
        #confirmLogout:hover 
        {
            background-color: #7c1515;
        }
        #cancelLogout 
        {
            background-color: #ddd;
            color: #555;
        }
        #cancelLogout:hover 
        {
            background-color: #bbb;
        }
        
        /* ========================================================== */
        /* ====== وسائط الطباعة: إخفاء العناصر غير المرغوب فيها ====== */
        /* ========================================================== */
        @media print {
            /* إخفاء الهيدر، شريط التقدم، وأزرار الإجراءات */
            header, 
            .progress-container, 
            .actions, 
            footer,
            .error {
                display: none !important; 
            }

            /* جعل جسم الصفحة يبدأ من الأعلى */
            body {
                background-color: white !important;
            }
            
            /* توسيع محتوى التصريح ليملأ الصفحة المطبوعة */
            .container, .permit-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            /* تعديل الهوامش الافتراضية للطباعة */
            @page {
                margin: 1cm;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-section">
            <div class="logo-img">أمانة<br>الأحساء</div>
            <div class="logo-text">
                <h1>أمانة الأحساء</h1>
                <h1>Alahsa Municipality</h1>
            </div>
        </div>

        <div class="header-buttons">
            <div class="header-btn home-btn">
                <span>🏠</span>
                <span>الرئيسية</span>
            </div>
            <div class="header-btn permits-btn">
                <span>📄</span>
                <span>تصاريحي</span>
            </div>
            <div class="header-btn logout-btn">
                <span>🚪</span>
                <span>تسجيل الخروج</span>
            </div>
        </div>
    </header>

    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="step-circle completed">✓</div>
                <div class="step-label completed">تحديد</div>
            </div>
            <div class="progress-step">
                <div class="step-circle completed">✓</div>
                <div class="step-label completed">تأكيد</div>
            </div>
            <div class="progress-step">
                <div class="step-circle active">3</div>
                <div class="step-label active">تصدير</div>
            </div>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">تصريح الدخول</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php elseif ($permit_details): ?>

            <div class="permit-container">
                <div class="permit-header">
                    <div class="permit-number">تم إصدار تصريح دخول برقم: <?php echo htmlspecialchars($permit_number); ?></div>
                    <div class="status-badge <?php echo $status; ?>"><?php echo htmlspecialchars($status); ?></div>
                </div>

                <div class="permit-details">
                    <div class="detail-group">
                        <span class="detail-label">الأسم:</span>
                        <div class="detail-value"><?php echo htmlspecialchars($full_name); ?></div>
                    </div>

                    <div class="detail-group">
                        <span class="detail-label">رقم الهوية:</span>
                        <div class="detail-value"><?php echo htmlspecialchars($national_id); ?></div>
                    </div>

                    <div class="detail-group">
                        <span class="detail-label">الموقع:</span>
                        <div class="detail-value"><?php echo htmlspecialchars($location_name); ?></div>
                    </div>

                    <div class="detail-group">
                        <span class="detail-label">تاريخ الانتهاء:</span>
                        <div class="detail-value"><?php echo htmlspecialchars($expires_at); ?></div>
                    </div>
                </div>

                <div class="barcode-section">
                    <div class="barcode-container">
                        <span class="detail-label">باركود التصريح:</span>
                        <svg id="barcode" class="barcode"></svg> 
                        <div class="barcode-number"><?php echo htmlspecialchars($barcode_value); ?></div>
                    </div>
                </div>

                <div class="detail-group" style="text-align: center;">
                    <span class="detail-label">التاريخ:</span>
                    <div class="detail-value" style="justify-content: center;"><?php echo htmlspecialchars($permit_date); ?></div>
                </div>

                <div class="time-section">
                    <div class="detail-label">الوقت:</div>
                    <div class="time-value"><?php echo htmlspecialchars($permit_time); ?></div>
                </div>

                <div class="actions">
                    <button class="action-btn print-btn">🖨️ طباعة التصريح</button>
                    <button class="action-btn download-btn">📥 تحميل التصريح (PNG)</button>
                </div>
            </div>
        <?php else: ?>
             <div class="error">عذراً، لا يمكن عرض التصريح حالياً. الرجاء المحاولة لاحقاً أو التحقق من صفحة "تصاريحي".</div>
        <?php endif; ?>
    </div>
    
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <p>هل أنت متأكد من تسجيل الخروج؟</p>
            <div class="modal-buttons">
                <button id="confirmLogout">نعم</button>
                <button id="cancelLogout">لا</button>
            </div>
        </div>
    </div>

    <script>
        // دالة عرض الباركود باستخدام مكتبة JsBarcode
        function generateBarcode(barcodeValue) {
            const barcodeElement = document.getElementById('barcode');
            if (barcodeElement && barcodeValue && barcodeValue !== 'غير متوفر') {
                JsBarcode(barcodeElement, barcodeValue, {
                    format: "CODE128",
                    width: 2,
                    height: 50,
                    displayValue: false,
                    background: "transparent"
                });
            } else if (barcodeElement) {
                barcodeElement.innerHTML = '<span>رمز الباركود غير متوفر</span>';
            }
        }
        
        window.addEventListener('load', function () {
            // كود الباركود الذي تم جلبه من PHP
            const barcodeData = "<?php echo $barcode_value; ?>";
            generateBarcode(barcodeData);

            // إضافة تفاعل لأزرار التنقل
            document.querySelector('.home-btn').addEventListener('click', function () {
                window.location.href = 'select.php'; 
            });

            document.querySelector('.permits-btn').addEventListener('click', function () {
                window.location.href = 'tasareh.php';
            });

            // 1. تفاعل زر الطباعة (Print Button) **(تعديل)**
            const printBtn = document.querySelector('.print-btn');
            if (printBtn) {
                printBtn.addEventListener('click', function () {
                    window.print(); // يشغل الطباعة مباشرة
                });
            }

            // 2. تفاعل زر التنزيل (Download Button) لتحويله إلى صورة PNG **(التعديل الرئيسي)**
            const downloadBtn = document.querySelector('.download-btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function () {
                    // نختار العنصر الذي نريد تحويله لصورة
                    const permitElement = document.querySelector('.permit-container'); 
                    
                    if (!permitElement) {
                        alert('عذراً، لم يتم العثور على عنصر التصريح لتنزيله.');
                        return;
                    }

                    // استخدام html2canvas لتحويل العنصر إلى Canvas
                    html2canvas(permitElement, {
                        scale: 2, // لزيادة دقة الصورة الناتجة
                        allowTaint: true, 
                        useCORS: true 
                    }).then(function (canvas) {
                        // تحويل الـ Canvas إلى صورة بصيغة Data URL
                        const imageURL = canvas.toDataURL("image/png");
                        
                        // إنشاء رابط وهمي (Link)
                        const a = document.createElement('a');
                        
                        // تعيين الصورة كبيانات للرابط
                        a.href = imageURL;
                        
                        // تعيين اسم الملف الذي سيتم تنزيله
                        const permitNumber = "<?php echo $permit_number; ?>";
                        a.download = `تصريح_دخول_${permitNumber}_امانة_الاحساء.png`;
                        
                        // النقر على الرابط لتشغيل التنزيل
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        
                        alert('تم تنزيل التصريح كصورة PNG بنجاح.');
                    }).catch(error => {
                        console.error('حدث خطأ أثناء إنشاء الصورة:', error);
                        alert('فشل تنزيل التصريح. تأكد من تحميل مكتبة html2canvas.');
                    });
                });
            }
            
            // محاولة تحميل صورة الشعار إذا كانت موجودة
            const logoContainer = document.querySelector('.logo-img');
            const testImage = new Image();
            testImage.src = 'ms.png';

            testImage.onload = function () {
                // إذا وجدت الصورة، استبدل الشعار النصي بالصورة
                logoContainer.innerHTML = '';
                logoContainer.style.backgroundImage = 'url(ms.png)';
                logoContainer.style.backgroundSize = 'contain';
                logoContainer.style.backgroundRepeat = 'no-repeat';
                logoContainer.style.backgroundPosition = 'center';
                logoContainer.style.backgroundColor = 'transparent';
                logoContainer.style.border = 'none';
            };

            testImage.onerror = function () {
                console.log('لم يتم العثور على صورة الشعار ms.png');
            };

            // مودال الخروج
            const logoutBtn = document.querySelector('.logout-btn');
            const modal = document.getElementById('logoutModal');
            const confirmBtn = document.getElementById('confirmLogout');
            const cancelBtn = document.getElementById('cancelLogout');

            logoutBtn.addEventListener('click', () => { 
                modal.style.display = 'flex'; 
            });

            confirmBtn.addEventListener('click', () => { 
                window.location.href = 'logout.php'; 
            });

            cancelBtn.addEventListener('click', () => { 
                modal.style.display = 'none'; 
            });

            window.addEventListener('click', (e) => { 
                if(e.target === modal){ 
                    modal.style.display = 'none'; 
                } 
            });
        });
    </script>
</body>

</html>