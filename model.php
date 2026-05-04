<?php
session_start();
require("DbHandler.php"); 
$db = new DbHandler();
$db->dbConnect();

// إذا ما فيه جلسة للمستخدم، رجعه للصفحة الرئيسية (login)
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم من قاعدة البيانات
$user = $db->getUserInfo($_SESSION['user_id']); 

// جلب القيم المرسلة من select.php عبر GET
$selected_location = isset($_GET['location_name']) ? $_GET['location_name'] : '';
$selected_date     = isset($_GET['date']) ? $_GET['date'] : '';
$selected_time_full = isset($_GET['time']) ? $_GET['time'] : ''; // مثلا "10:00 - 11:00"
$selected_time_parts = explode(' - ', $selected_time_full);
$selected_time_start = $selected_time_parts[0] ?? '';
$selected_time_end   = $selected_time_parts[1] ?? '';

// **جلب الـ IDs من الـ URL (مهم جداً للتعامل مع قاعدة البيانات)**
$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
$date_id     = isset($_GET['date_id']) ? intval($_GET['date_id']) : 0;
$time_id     = isset($_GET['time_id']) ? intval($_GET['time_id']) : 0;
$error_message = ""; // متغير لتخزين رسائل الخطأ

// ==========================================================
// ====== معالجة طلب تأكيد التصريح (زر التأكيد) ======
// ==========================================================
if (isset($_POST['confirm_permit'])) {
    // التأكد من وجود الـ IDs المطلوبة في الـ POST (تم تمريرها من الحقول المخفية)
    $post_location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
    $post_date_id     = isset($_POST['date_id']) ? intval($_POST['date_id']) : 0;
    $post_time_id     = isset($_POST['time_id']) ? intval($_POST['time_id']) : 0;

    $user_id = $_SESSION['user_id'];

    if ($post_location_id > 0 && $post_date_id > 0 && $post_time_id > 0) {
        $result = $db->createPermit($user_id, $post_location_id, $post_date_id, $post_time_id);

        if (is_int($result) && $result > 0) {
            // النجاح: $result هو permit_id
            // إعادة التوجيه إلى enter.php مع رقم التصريح (permit_id)
            header("Location: enter.php?permit_id=" . $result); 
            exit();
        } else {
            // فشل الإدراج
            $error_message = "فشل إنشاء التصريح: " . $result;
        }
    } else {
        $error_message = "بيانات التصريح غير مكتملة. الرجاء المحاولة مرة أخرى.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نموذج طلب تصريح - أمانة الأحساء</title>
    <style>
        /* (كود CSS كما هو) */
        * {margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
        body {background-color:#f5f5f5; color:#333; line-height:1.6; padding-bottom:30px;}

        /* الهيدر */
        header {
            display:flex; align-items:center; justify-content:space-between;
            padding:15px 30px; background-color:white;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
            border-bottom:3px solid #7c1515;
        }
        .logo-section {display:flex; align-items:center; gap:15px;}
        .logo-img {
            width:80px; height:80px; border:2px solid #7c1515;
            border-radius:8px; background:linear-gradient(135deg, #7c1515 0%, #c9a36a 100%);
            display:flex; align-items:center; justify-content:center; color:white;
            font-weight:bold; font-size:16px; text-align:center; padding:5px;
        }
        .logo-text h1 {font-size:18px; color:#7c1515; line-height:1.3;}
        .logo-text h1:first-child {font-weight:bold;}
        .logo-text h1:last-child {font-size:16px; color:#555;}

        .header-buttons {display:flex; gap:10px;}
        .header-btn {
            display:flex; align-items:center; gap:5px;
            padding:8px 15px; border-radius:6px; cursor:pointer;
            font-weight:bold; transition:all 0.3s;
        }
        .home-btn {background:#f8f9fa; color:#555; border:1px solid #dee2e6;}
        .permits-btn {background:#c9a36a; color:white;}
        .logout-btn {background:#7c1515; color:white;}
        .header-btn:hover {opacity:0.9; transform:translateY(-2px);}
        .home-btn:hover {background:#e9ecef; color:#333;}

        /* شريط التقدم */
        .progress-container {max-width:800px; margin:20px auto 30px; padding:0 20px;}
        .progress-bar {display:flex; align-items:center; justify-content:space-between; position:relative; margin-bottom:15px;}
        .progress-line {position:absolute; top:15px; left:20%; right:20%; height:3px; background-color:#ddd; z-index:1;}
        .progress-step {display:flex; flex-direction:column; align-items:center; z-index:2;}
        .step-circle {
            width:35px; height:35px; border-radius:50%; background-color:#ddd;
            display:flex; align-items:center; justify-content:center;
            font-weight:bold; color:white; margin-bottom:8px; border:3px solid #ddd;
        }
        .step-circle.completed {background-color:#28a745; border-color:#28a745;}
        .step-circle.active {background-color:#7c1515; border-color:#7c1515;}
        .step-label {font-size:14px; font-weight:bold; color:#555; text-align:center;}
        .step-label.completed {color:#28a745;}
        .step-label.active {color:#7c1515;}

        /* المحتوى الرئيسي */
        .container {max-width:800px; margin:30px auto; padding:0 20px;}
        .page-title {
            text-align:center; margin-bottom:30px; color:#7c1515;
            font-size:28px; font-weight:bold; position:relative; padding-bottom:10px;
        }
        .page-title:after {
            content:""; position:absolute; bottom:0; left:35%; right:35%; height:3px; background-color:#c9a36a;
        }
        .form-container {
            background-color:white; border-radius:10px; padding:30px;
            box-shadow:0 5px 15px rgba(0,0,0,0.1); border:2px solid #7c1515;
        }
        .form-header {text-align:center; margin-bottom:25px; padding-bottom:15px; border-bottom:2px dashed #c9a36a;}
        .form-details {display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:25px;}
        .detail-group {margin-bottom:15px;}
        .detail-label {font-weight:bold; color:#555; margin-bottom:5px; display:block;}
        .detail-value {
            background-color:#f9f9f9; border:1px solid #ddd; border-radius:5px;
            padding:10px 15px; font-size:16px; min-height:44px; display:flex; align-items:center;
        }

        .checkbox-group {
            display:flex; align-items:center; gap:10px;
            margin:25px 0; padding:15px; background-color:#f9f9f9; border-radius:5px;
        }
        .checkbox-group input {width:18px; height:18px;}
        .checkbox-group label {margin-bottom:0; color:#333;}

        /* زر التأكيد مثل القديم */
        .submit-btn {
            background-color:#7c1515; color:white; border:none;
            padding:12px 30px; font-size:18px; border-radius:5px;
            cursor:pointer; display:block; margin:0 auto;
            transition:background 0.3s; font-weight:bold;
        }
        .submit-btn:hover {background-color:#5a1010;}
        .submit-btn:disabled {background-color:#cccccc; cursor:not-allowed;}
        
        .error {
            background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
            padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center;
        }

        /* مودال الخروج */
        .modal {display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background-color:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;}
        .modal-content {background-color:#fff; padding:30px 40px; border-radius:12px; text-align:center;
            max-width:400px; box-shadow:0 5px 15px rgba(0,0,0,0.3);}
        .modal-content p {font-size:18px; color:#333; margin-bottom:20px;}
        .modal-buttons button {padding:10px 25px; margin:0 10px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;}
        #confirmLogout {background-color:#c9a36a; color:white;}
        #confirmLogout:hover {background-color:#7c1515;}
        #cancelLogout {background-color:#ddd; color:#555;}
        #cancelLogout:hover {background-color:#bbb;}

        @media (max-width:768px) {
            header {flex-direction:column; gap:15px;}
            .form-details {grid-template-columns:1fr;}
            .page-title:after {left:25%; right:25%;}
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
        <div class="header-btn home-btn">🏠 الرئيسية</div>
        <div class="header-btn permits-btn">📄 تصاريحي</div>
        <div class="header-btn logout-btn">🚪 تسجيل الخروج</div>
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
            <div class="step-circle active">2</div>
            <div class="step-label active">تأكيد</div>
        </div>
        <div class="progress-step">
            <div class="step-circle">3</div>
            <div class="step-label">تصدير</div>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title">نموذج طلب تصريح</h1>
    <div class="form-container">
        <div class="form-header">
            <div class="detail-label" style="font-size:20px; color:#7c1515;">نموذج طلب تصريح</div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="model.php?location_id=<?php echo $location_id; ?>&location_name=<?php echo urlencode($selected_location); ?>&date_id=<?php echo $date_id; ?>&date=<?php echo urlencode($selected_date); ?>&time_id=<?php echo $time_id; ?>&time=<?php echo urlencode($selected_time_full); ?>">

            <input type="hidden" name="location_id" value="<?php echo $location_id; ?>">
            <input type="hidden" name="date_id" value="<?php echo $date_id; ?>">
            <input type="hidden" name="time_id" value="<?php echo $time_id; ?>">

            <div class="form-details">
                <div class="detail-group">
                    <span class="detail-label">الاسم:</span>
                    <div class="detail-value"><?php echo htmlspecialchars($user['full_name'] ?? 'غير متوفر'); ?></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">الهوية:</span>
                    <div class="detail-value"><?php echo htmlspecialchars($user['user_id'] ?? 'غير متوفر'); ?></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">رقم الجوال:</span>
                    <div class="detail-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'غير متوفر'); ?></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">موقع الحجز:</span>
                    <div class="detail-value"><?php echo htmlspecialchars($selected_location); ?></div>
                </div>
            </div>

            <div class="form-details">
                <div class="detail-group">
                    <span class="detail-label">التاريخ:</span>
                    <div class="detail-value"><?php echo htmlspecialchars($selected_date); ?></div>
                </div>
                <div class="detail-group">
                    <span class="detail-label">الوقت:</span>
                    <div class="detail-value">
                        من: <?php echo htmlspecialchars($selected_time_start); ?> إلى: <?php echo htmlspecialchars($selected_time_end); ?>
                    </div>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="agreement" required>
                <label for="agreement">أقر بصحة بياناتي والإلتزام بالوقت المحدد للتصريح</label>
            </div>

            <button type="submit" name="confirm_permit" class="submit-btn" id="submitBtn" disabled>تأكيد</button>
        </form>
        </div>
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
    // الأزرار
    document.querySelector('.home-btn').addEventListener('click', () => window.location.href = 'select.php');
    document.querySelector('.permits-btn').addEventListener('click', () => window.location.href = 'tasareh.php');

    // تفعيل زر التأكيد
    const agreementCheckbox = document.getElementById('agreement');
    const submitBtn = document.getElementById('submitBtn');

    agreementCheckbox.addEventListener('change', () => {
        submitBtn.disabled = !agreementCheckbox.checked;
    });

    // مودال تسجيل الخروج
    const logoutBtn = document.querySelector('.logout-btn');
    const modal = document.getElementById('logoutModal');
    const confirmBtn = document.getElementById('confirmLogout');
    const cancelBtn = document.getElementById('cancelLogout');

    logoutBtn.addEventListener('click', () => modal.style.display = 'flex');
    confirmBtn.addEventListener('click', () => window.location.href = 'logout.php');
    cancelBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

    // تحميل شعار
    window.addEventListener('load', () => {
        const logoContainer = document.querySelector('.logo-img');
        const testImage = new Image();
        testImage.src = 'ms.png';
        testImage.onload = () => {
            logoContainer.innerHTML = '';
            logoContainer.style.backgroundImage = 'url(ms.png)';
            logoContainer.style.backgroundSize = 'contain';
            logoContainer.style.backgroundRepeat = 'no-repeat';
            logoContainer.style.backgroundPosition = 'center';
            logoContainer.style.backgroundColor = 'transparent';
            logoContainer.style.border = 'none';
        };
    });
</script>

</body>
</html>