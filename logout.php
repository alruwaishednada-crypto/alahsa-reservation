<?php
session_start();

// إزالة جميع بيانات الجلسة
session_unset();
session_destroy();

// إعادة توجيه المستخدم إلى صفحة تسجيل الدخول
header("Location: login.php");
exit();
?>
