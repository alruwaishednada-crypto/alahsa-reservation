<?php
session_start();
require("DbHandler.php");

// ==========================================================
// ====== منطق تحديد حالة التصريح  ======
// ==========================================================
function calculatePermitStatus($date, $time_end) {
    // دمج التاريخ والوقت لإنشاء وقت انتهاء كامل
    $permit_end_datetime_str = $date . ' ' . $time_end;
    
    try {
        $permit_end_datetime = new DateTime($permit_end_datetime_str);
        $now = new DateTime();
        $permit_date_only = new DateTime($date);
        $today_date_only = new DateTime($now->format('Y-m-d'));

        // 1. منتهية: اذا صار التاريخ زي تاريخ الانتهاء (أي مر وقته فعلياً)
        if ($now >= $permit_end_datetime) {
             return ['status' => 'منتهية', 'class' => 'expired'];
        }
        
        // 2. مكتملة: اذا صار التاريخ زي التاريخ اللي اختاره المستخدم (تاريخ اليوم)
        // يتم اعتباره مكتمل لتصاريح اليوم التي لم ينتهِ وقتها بعد.
        if ($permit_date_only->format('Y-m-d') === $today_date_only->format('Y-m-d')) {
            return ['status' => 'مكتملة', 'class' => 'completed']; 
        }

        // 3. نشط: اذا كان التاريخ قبل التاريخ اللي اختاره المستخدم (أي التصريح في المستقبل)
        if ($permit_date_only > $today_date_only) {
            return ['status' => 'نشط', 'class' => 'active'];
        }
        
        // حالة احتياطية (غير محددة/نشطة افتراضياً)
        return ['status' => 'نشط', 'class' => 'active'];

    } catch (Exception $e) {
        return ['status' => 'خطأ في التاريخ', 'class' => 'unknown']; 
    }
}
// ==========================================================
// ====== جلب التصاريح بناءً على المستخدم وكلمة البحث ======
// ==========================================================

// إذا ما فيه جلسة للمستخدم، رجعه للصفحة الرئيسية (login)
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// جلب كلمة البحث من الـ URL (GET)
$search_term = $_GET['search_term'] ?? '';

$db = new DbHandler();
$db->dbConnect();

// جلب التصاريح
$permits = $db->getUserPermits($user_id, $search_term);

?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصاريحي - أمانة الأحساء</title>
    <style>
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

        /* الهيدر - نفس التصميم */
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
            /* رمادي فاتح جداً */
            color: #555;
            /* لون نص رمادي */
            border: 1px solid #dee2e6;
        }

        .permits-btn {
            background: #c9a36a;
            /* ذهبي - كما في الصفحة الأصلية */
            color: white;
        }

        .logout-btn {
            background: #7c1515;
            /* أحمر - كما في الصفحة الأصلية */
            color: white;
        }

        .header-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .home-btn:hover {
            background: #e9ecef;
            /* رمادي أغمق قليلاً عند hover */
            color: #333;
        }

        /* المحتوى الرئيسي */
        .container {
            max-width: 1000px;
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

        /* فلتر البحث */
        /* تم تعديل هذا القسم ليتضمن نموذج البحث المفعل */
        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 250px; /* زيادة العرض ليتناسب مع الإدخال الواحد */
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .filter-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-btn {
            background-color: #7c1515;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-end;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background-color: #5a1010;
        }

        /* بطاقة التصريح */
        .permit-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            border-right: 4px solid #7c1515;
        }

        .permit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }

        .permit-number {
            font-size: 18px;
            color: #7c1515;
            font-weight: bold;
        }

        .permit-status {
            background-color: #c9a36a;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        /* الأنماط الجديدة لحالة التصريح */
        .permit-status.status-active {
            background-color: #c9a36a; /* نشط */
        }

        .permit-status.status-completed { 
            background-color: #007bff; /* مكتملة */
        }

        .permit-status.status-expired {
            background-color: #dc3545; /* منتهية */
        }
        
        .permit-status.status-unknown {
            background-color: #6c757d; /* غير محدد */
        }
        
        /* نهاية الأنماط الجديدة */


        .permit-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .permit-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none; /* مهم للروابط A */
            display: inline-block; /* مهم للروابط A */
            text-align: center;
        }

        .view-btn {
            background-color: #7c1515;
            color: white;
        }

        .print-btn {
            background-color: #c9a36a;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* رسالة عدم وجود تصاريح */
        .no-permits {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        }

        .no-permits-icon {
            font-size: 50px;
            margin-bottom: 20px;
            color: #ddd;
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

            .filter-section {
                flex-direction: column;
            }

            .permit-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .permit-details {
                grid-template-columns: 1fr;
            }

            .permit-actions {
                justify-content: center;
            }

            .page-title:after {
                left: 25%;
                right: 25%;
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
        cursor: pointer;}
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
            <a href="select.php" class="header-btn home-btn">
                <span>🏠</span>
                <span>الرئيسية</span>
            </a>
            <div class="header-btn logout-btn">
                <span>🚪</span>
                <span>تسجيل الخروج</span>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">تصاريحي</h1>

        <form method="GET" action="tasareh.php" class="filter-section">
            <div class="filter-group">
                <label for="search_term" class="filter-label">رقم التصريح أو اسم الموقع أو التاريخ:</label>
                <input type="text" id="search_term" name="search_term" class="filter-input" placeholder="ابحث برقم التصريح، اسم الموقع، أو التاريخ" value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <button type="submit" class="search-btn">بحث</button>
        </form>

        <div class="permits-list">
            
            <?php if (empty($permits)): ?>
                <div class="no-permits permit-card">
                    <p class="no-permits-icon">⚠️</p>
                    <p style="font-size: 18px; color: #7c1515; font-weight: bold;">لا توجد تصاريح لعرضها حالياً.</p>
                    <?php if (!empty($search_term)): ?>
                        <p style="color: #555; margin-top: 10px;">لا توجد نتائج مطابقة لبحثك عن: <strong><?php echo htmlspecialchars($search_term); ?></strong></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($permits as $permit): 
                    // حساب حالة التصريح باستخدام الدالة المضافة
                    $status_info = calculatePermitStatus($permit['available_date'], $permit['available_time_end']);
                ?>
                <div class="permit-card">
                    <div class="permit-header">
                        <div class="permit-number">تصريح دخول رقم: <?php echo htmlspecialchars($permit['permit_number']); ?></div>
                        <div class="permit-status status-<?php echo $status_info['class']; ?>">
                            <?php echo $status_info['status']; ?>
                        </div>
                    </div>

                    <div class="permit-details">
                        <div class="detail-item">
                            <span class="detail-label">الموقع:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($permit['location_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">التاريخ:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($permit['available_date']); ?></span> 
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">الوقت:</span>
                            <span class="detail-value">من: <?php echo htmlspecialchars($permit['available_time_start']); ?> إلى: <?php echo htmlspecialchars($permit['available_time_end']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">تاريخ الإصدار:</span>
                            <span class="detail-value"><?php echo htmlspecialchars(date('Y/m/d', strtotime($permit['created_at']))); ?></span>
                        </div>
                        </div>

                    <div class="permit-actions">
                        <a href="enter.php?permit_id=<?php echo $permit['permit_id']; ?>" class="action-btn view-btn">عرض التفاصيل</a>
                        
                        <a href="javascript:void(0)" onclick="window.open('enter.php?permit_id=<?php echo $permit['permit_id']; ?>', '_blank').print()" class="action-btn print-btn">طباعة</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
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

    <footer>
        <p>جميع الحقوق محفوظة © أمانة الأحساء 2023</p>
    </footer>

    <script>
        // إضافة تفاعل للأزرار
        document.querySelector('.home-btn').addEventListener('click', function () {
            window.location.href = 'select.php'; // تغيير لصفحة الرئيسية
        });

        /*  document.querySelector('.logout-btn').addEventListener('click', function () {
            window.location.href = 'logout.php';
        }); */

        // تم إلغاء الحاجة لهذه الوظائف بعد استبدال الأزرار الثابتة بروابط ديناميكية
        /*
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const permitNumber = this.closest('.permit-card').querySelector('.permit-number').textContent;
                alert('عرض تفاصيل: ' + permitNumber);
            });
        });

        document.querySelectorAll('.print-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const permitNumber = this.closest('.permit-card').querySelector('.permit-number').textContent;
                if (confirm('هل تريد طباعة ' + permitNumber + '؟')) {
                    alert('جاري الطباعة...');
                }
            });
        });

        // تم إلغاء الحاجة لهذه الوظيفة بعد تفعيل زر البحث بـ FORM submit
        document.querySelector('.search-btn').addEventListener('click', function () {
            alert('جاري البحث في التصاريح...');
        });
        */

        // محاولة تحميل صورة الشعار إذا كانت موجودة
        window.addEventListener('load', function () {
            const logoContainer = document.querySelector('.logo-img');
            const testImage = new Image();
            testImage.src = 'ms.png';

            testImage.onload = function () {
                logoContainer.innerHTML = '';
                logoContainer.style.backgroundImage = 'url(ms.png)';
                logoContainer.style.backgroundSize = 'contain';
                logoContainer.style.backgroundRepeat = 'no-repeat';
                logoContainer.style.backgroundPosition = 'center';
                logoContainer.style.backgroundColor = 'transparent';
                logoContainer.style.border = 'none';
            };
        });
        
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
    </script>
</body>

</html>