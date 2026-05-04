<?php
session_start();
require("DbHandler.php"); 
$db = new DbHandler();
$db->dbConnect();

// إذا ما فيه جلسة للمستخدم، رجعه للصفحة الرئيسية
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// جلب التواريخ والأوقات حسب الموقع
if(isset($_GET['action'])){
    if($_GET['action'] == 'getDates' && isset($_GET['location_id'])){
        $dates = $db->getAvailableDates(intval($_GET['location_id']));
        echo json_encode($dates);
        exit();
    }

    if($_GET['action'] == 'getTimes' && isset($_GET['date_id'])){
        $times = $db->getAvailableTimes(intval($_GET['date_id']));
        echo json_encode($times);
        exit();
    }
}

// جلب كل المواقع عند تحميل الصفحة
$locations = $db->getLocations();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طلب تصريح</title>
<style>
* 
{
    margin: 0;
     padding: 0; 
     box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
body
 {
    background-color: #f5f5f5;
     color: #333; line-height: 1.6;
      padding-bottom: 30px;
    }

/* الهيدر */
header 
{
    display: flex;
     align-items: center;
      justify-content: space-between;
       padding: 15px 30px; background-color: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         border-bottom: 3px solid #7c1515;
        }
.logo-section 
{
    display: flex; 
    align-items: center;
     gap: 15px;
    }
.logo-img 
{ 
    width: 80px; 
    height: 80px; 
    object-fit: contain; 
    border: none; background-color:
     transparent; display: flex;
      align-items: center; 
      justify-content: center;
       color: #7c1515; 
       font-weight: bold;
        font-size: 14px; 
        text-align: center; 
        padding: 5px;
    }
.logo-text h1 
{
    font-size: 18px; 
    color: #7c1515; 
    line-height: 1.3;
}
.logo-text h1:first-child 
{
    font-weight: bold;
}
.logo-text h1:last-child 
{
    font-size: 16px; 
    color: #555;
}
.header-buttons 
{
    display: flex; 
    gap: 10px;
}
.header-btn 
{
    display: flex;
     align-items: center; 
     gap: 5px; padding: 8px 15px; 
     border-radius: 6px; 
     cursor: pointer;
      font-weight: bold;
       transition: all 0.3s;
    }
.permits-btn 
{
    background: #c9a36a;
     color: white;
    }
.logout-btn 
{
    background: #7c1515;
     color: white;
    }
.header-btn:hover 
{
    opacity: 0.9;
     transform: translateY(-2px);
    }

/* شريط التقدم */
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

.step-label.active {
    color: #7c1515;
}

/* العنوان */
.page-title 
{text-align: center;
     margin: 30px auto; 
     color: #7c1515;
      font-size: 28px; 
      font-weight: bold; 
      position: relative; 
      padding-bottom: 15px;
    }
.page-title:after 
{content: "";
     position: absolute;
      bottom: 0; 
      left: 35%; 
      right: 35%; 
      height: 3px; 
      background-color: #c9a36a;
    }

/* الورقة */
.container 
{max-width: 800px; margin: 30px auto; padding: 0 20px;}
.form-container
 {
    background-color: white;
     border-radius: 10px;
      padding: 30px; 
      box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
      border: 2px solid #7c1515;}
label
 {
    display: block; 
    margin-bottom: 8px; 
    font-weight: bold; 
    color: #555;}
select 
{
    width: 100%;
     padding: 12px 15px; 
     margin-bottom: 20px;
      border: 1px solid #ddd; 
      border-radius: 5px; 
      font-size: 16px; 
      background-color: white;
       transition: border 0.3s;}
select:focus 
{
    outline: none; 
    border-color: #c9a36a; 
    box-shadow: 0 0 0 2px rgba(201,163,106,0.2);}
.btn 
{
    background-color: #ddd; 
    color: #777;
     border: none;
      padding: 12px 30px; 
      font-size: 18px; 
      border-radius: 5px; 
      cursor: not-allowed; 
      display: block; 
      margin: 0 auto;
       transition: background 0.3s;
        font-weight: bold;
         width: 200px;}
.btn.active 
{
    background-color: #c9a36a; 
    color: white; 
    cursor: pointer;}
.btn.active:hover 
{
    background-color: #7c1515;}

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
        <div class="logo-img">شعار<br>الأمانة</div>
        <div class="logo-text">
            <h1>أمانة الأحساء</h1>
            <h1>Alahsa Municipality</h1>
        </div>
    </div>
    <div class="header-buttons">
        <div class="header-btn permits-btn"><span>📄</span><span>تصاريحي</span></div>
        <div class="header-btn logout-btn"><span>🚪</span><span>تسجيل الخروج</span></div>
    </div>
</header>

<!-- شريط التقدم المضاف -->
<div class="progress-container">
    <div class="progress-bar">
        <div class="progress-line"></div>
        <div class="progress-step">
            <div class="step-circle active">1</div>
            <div class="step-label active">تحديد</div>
        </div>
        <div class="progress-step">
            <div class="step-circle">2</div>
            <div class="step-label">تأكيد</div>
        </div>
        <div class="progress-step">
            <div class="step-circle">3</div>
            <div class="step-label">تصدير</div>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title">طلب تصريح</h1>
    <div class="form-container">
        <label>تحديد الموقع</label>
        <select id="location">
            <option value="">-- اختر الموقع --</option>
            <?php foreach($locations as $loc){ echo "<option value='{$loc['location_id']}'>{$loc['location_name']}</option>"; } ?>
        </select>
        <select id="date"><option value="">-- اختر التاريخ --</option></select>
        <select id="time"><option value="">-- اختر الوقت --</option></select>
        <button id="submitBtn" class="btn">تنفيذ</button>
    </div>
</div>


<!-- مربع تأكيد الخروج -->
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
// زر تصاريحي
document.querySelector('.permits-btn').addEventListener('click', () => { window.location.href = 'tasareh.php'; });

// الشعار
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

// النموذج
const locationSelect = document.getElementById("location");
const dateSelect = document.getElementById("date");
const timeSelect = document.getElementById("time");
const submitBtn = document.getElementById("submitBtn");

// تعطيل الزر افتراضياً
submitBtn.disabled = true;

// دالة للتحقق من اكتمال النموذج
function checkFormCompletion() {
    if (locationSelect.value && dateSelect.value && timeSelect.value) {
        submitBtn.classList.add("active");
        submitBtn.disabled = false;
    } else {
        submitBtn.classList.remove("active");
        submitBtn.disabled = true;
    }
}

// التحقق عند تغيير أي خيار
locationSelect.addEventListener("change", function(){
    let locId = this.value;
    dateSelect.innerHTML = '<option>جاري التحميل...</option>';
    timeSelect.innerHTML = '<option value="">-- اختر الوقت --</option>';
    fetch(`?action=getDates&location_id=${locId}`)
    .then(res => res.json())
    .then(data => {
        let html = '<option value="">-- اختر التاريخ --</option>';
        data.forEach(d => { html += `<option value="${d.date_id}">${d.available_date}</option>`; });
        dateSelect.innerHTML = html;
        checkFormCompletion(); // تحقق بعد تحميل التواريخ
    });
});

dateSelect.addEventListener("change", function(){
    let dateId = this.value;
    timeSelect.innerHTML = '<option>جاري التحميل...</option>';
    fetch(`?action=getTimes&date_id=${dateId}`)
    .then(res => res.json())
    .then(data => {
        let html = '<option value="">-- اختر الوقت --</option>';
        data.forEach(t => { html += `<option value="${t.time_id}">${t.available_time_start} - ${t.available_time_end}</option>`; });
        timeSelect.innerHTML = html;
        checkFormCompletion(); // تحقق بعد تحميل الأوقات
    });
});

// التحقق عند تغيير الوقت
timeSelect.addEventListener("change", checkFormCompletion);

// عند الضغط على زر التنفيذ - الذهاب لصفحة model.php
submitBtn.addEventListener("click", function() {
    if (locationSelect.value && dateSelect.value && timeSelect.value) {
        const locationText = locationSelect.options[locationSelect.selectedIndex].text;
        const dateText = dateSelect.options[dateSelect.selectedIndex].text;
        const timeText = timeSelect.options[timeSelect.selectedIndex].text;

        window.location.href = `model.php?location_id=${locationSelect.value}&location_name=${encodeURIComponent(locationText)}&date_id=${dateSelect.value}&date=${encodeURIComponent(dateText)}&time_id=${timeSelect.value}&time=${encodeURIComponent(timeText)}`;
    }
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