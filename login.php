<?php
session_start();
require("DbHandler.php");

$db = new DbHandler();
$db->dbConnect();
$errorMsg = "";

if(isset($_POST['loginBtn'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $loginResult = $db->loginUser($username, $password);

    if($loginResult === "success"){
        // تسجيل دخول ناجح
        header("Location: select.php");
        exit();
    } else {
        // رسالة خطأ
        $errorMsg = $loginResult;
    }
}
?>





<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
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

        /* الهيدر - نفس التصميم بالضبط */
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
            object-fit: contain;
            border: none;
            border-radius: 0;
            background-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7c1515;
            font-weight: bold;
            font-size: 14px;
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

        /* العنوان - نفس التصميم بالضبط */
        .page-title {
            text-align: center;
            margin: 30px auto;
            color: #7c1515;
            font-size: 28px;
            font-weight: bold;
            position: relative;
            padding-bottom: 15px;
            max-width: 800px;
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

        /* المحتوى الرئيسي */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .center-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #c9a36a;
            width: 100%;
            max-width: 600px;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 14px 0;
            background: #e5e5e5;
            color: #333;
            font-size: 16px;
            cursor: pointer;
            border: none;
            outline: none;
            transition: all 0.3s;
        }

        .tab.active {
            background: #c9a36a;
            color: #fff;
            font-weight: bold;
        }

        .form-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid #7c1515;
            margin-top: 0;
        }

        .form-container input {
            width: 100%;
            padding: 12px 15px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .form-container input:focus {
            outline: none;
            border-color: #c9a36a;
            box-shadow: 0 0 0 2px rgba(201, 163, 106, 0.2);
        }

        .form-container a {
            display: block;
            margin: 12px 0;
            font-size: 14px;
            color: #555;
            text-decoration: none;
            text-align: center;
        }

        .form-container a:hover {
            text-decoration: underline;
            color: #7c1515;
        }

        .btn {
            display: block;
            width: 100%;
            margin-top: 20px;
            text-align: center;
            background: #c9a36a;
            color: #fff;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            padding: 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #7c1515;
        }
    </style>
</head>

<body>
    <!-- الهيدر - نفس التصميم بالضبط -->
    <header>
        <div class="logo-section">
            <div class="logo-img">شعار<br>الأمانة</div>
            <div class="logo-text">
                <h1>أمانة الأحساء</h1>
                <h1>Alahsa Municipality</h1>
            </div>
        </div>

        <!-- بدون أزرار -->
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        <h1 class="page-title">تسجيل الدخول</h1>

        <div class="center-box">
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="showForm('user')">باسم المستخدم</button>
                <button class="tab" onclick="showForm('nafath')">عن طريق تطبيق نفاذ</button>
            </div>

            <!-- Forms -->
            <div id="userForm" class="form-container">
                <form method="post" action="">
    <input type="text" name="username" placeholder="اسم المستخدم / الرقم الوظيفي" required>
    <input type="password" name="password" placeholder="كلمة المرور" required>
    <button type="submit" class="btn" name="loginBtn">تسجيل الدخول</button>

    <!-- هنا رسالة الخطأ لو فيه -->
    <?php if(isset($errorMsg) && $errorMsg != "") { ?>
        <p style="color:red;text-align:center;margin-top:10px;"><?= $errorMsg ?></p>
    <?php } ?>
</form>

            </div>


            <div id="nafathForm" class="form-container" style="display: none;">
                <input type="text" placeholder="رقم الهوية">
                <button class="btn">تسجيل الدخول</button>
            </div>
        </div>
    </div>

    <script>
        function showForm(type) {
            document.getElementById("userForm").style.display = (type === "user") ? "block" : "none";
            document.getElementById("nafathForm").style.display = (type === "nafath") ? "block" : "none";

            let tabs = document.querySelectorAll(".tab");
            tabs.forEach(tab => tab.classList.remove("active"));
            if (type === "user") {
                tabs[0].classList.add("active");
            } else {
                tabs[1].classList.add("active");
            }
        }

        // محاولة تحميل الشعار بعد تحميل الصفحة
        window.addEventListener('load', function () {
            const logoImg = document.querySelector('.logo-img');
            const img = new Image();
            img.src = 'ms.png';

            img.onload = function () {
                logoImg.innerHTML = '';
                logoImg.style.backgroundImage = 'url(ms.png)';
                logoImg.style.backgroundSize = 'contain';
                logoImg.style.backgroundRepeat = 'no-repeat';
                logoImg.style.backgroundPosition = 'center';
                logoImg.style.border = 'none';
                logoImg.style.borderRadius = '0';
                logoImg.style.backgroundColor = 'transparent';
            };

            img.onerror = function () {
                console.log('لم يتم العثور على صورة الشعار ms.png');
            };
        });
    </script>
</body>

</html>