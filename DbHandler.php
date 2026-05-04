<?php
require("DbConfig.php");
class DbHandler extends DbConfig {
    public $conn;
    protected $databaseName;
    protected $hostName;
    protected $uName;
    protected $passCode;

    public function __construct() {
        //create object to access the database configuration data
        $dbPara = new DbConfig();
        $this -> databaseName = $dbPara -> dbName;
        $this -> hostName = $dbPara -> serverName;
        $this -> uName = $dbPara -> userName;
        $this -> passCode = $dbPara ->passCode;
        $dbPara = NULL;
    }
  
   public function dbConnect(){
        try{
	        $this->conn = new mysqli($this->hostName, $this->uName, $this->passCode, $this->databaseName);
		
            if( mysqli_connect_errno() ){
                throw new Exception("Could not connect to database.");   
            } else{
                return true;
            }
        }catch(Exception $e){
            throw new Exception($e->getMessage());   
        }
    }

    // ====== فنكشن تسجيل الدخول بالـ username ======
public function loginUser($username, $password){
    $password = md5($password); // تشفير كلمة المرور

    // نجيب user_id + username معًا
    $stmt = $this->conn->prepare("SELECT user_id, username FROM Users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        // نحفظ البيانات في السيشن
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['username'] = $row['username'];

        $stmt->close();
        return "success";
    } else {
        $stmt->close();
        return "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}
// ====== جلب كل المواقع ======
public function getLocations() {
    $stmt = $this->conn->prepare("SELECT location_id, location_name FROM Locations ORDER BY location_name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $locations = [];
    while($row = $result->fetch_assoc()){
        $locations[] = $row;
    }
    $stmt->close();
    return $locations;
}

// ====== جلب التواريخ المتاحة لموقع معين (اليوم فصاعدًا) ======
public function getAvailableDates($location_id) {
    $stmt = $this->conn->prepare("
        SELECT date_id, available_date 
        FROM Available_Dates 
        WHERE location_id = ? AND available_date >= CURDATE()
        ORDER BY available_date ASC
    ");
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dates = [];
    while($row = $result->fetch_assoc()){
        $dates[] = $row;
    }
    $stmt->close();
    return $dates;
}

// ====== جلب الأوقات المتاحة لتاريخ معين ======
public function getAvailableTimes($date_id) {
    $stmt = $this->conn->prepare("
        SELECT time_id, available_time_start, available_time_end 
        FROM Available_Times 
        WHERE date_id = ?
        ORDER BY available_time_start ASC
    ");
    $stmt->bind_param("i", $date_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $times = [];
    while($row = $result->fetch_assoc()){
        $times[] = $row;
    }
    $stmt->close();
    return $times;
}

// ====== جلب بيانات المستخدم بالـ user_id لصفحة المودل======
public function getUserInfo($user_id) {
    $stmt = $this->conn->prepare("
        SELECT user_id, full_name, email, phone_number, username 
        FROM Users 
        WHERE user_id = ?
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // يحتوي على user_id الآن
    $stmt->close();
    return $user;
}

public function generateUniquePermitNumber() {
    // توليد رقم تصريح بسيط وفريد يعتمد على التاريخ والوقت وجزء عشوائي
    return 'PRM' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// ==========================================================
// ====== الدالة المضافة لتوليد رمز الباركود (PUBLIC) ======
// ==========================================================
public function generateBarcodeString($permit_number) {
    // رمز الباركود هو نفسه رقم التصريح لغرض التبسيط
    return $permit_number; 
}

// ==========================================================
// ====== الدالة المضافة لإنشاء تصريح جديد ======
// ==========================================================
// ==========================================================
// ====== الدالة المضافة لإنشاء تصريح جديد ======
// ==========================================================
public function createPermit($user_id, $location_id, $date_id, $time_id) {
    
    // 1. التحقق من عدم وجود حجز مسبق لنفس المستخدم ونفس التفاصيل (الموقع، التاريخ، الوقت)
    $check_stmt = $this->conn->prepare("
        SELECT permit_id 
        FROM Permits 
        WHERE user_id = ? AND location_id = ? AND date_id = ? AND time_id = ?
    ");
    $check_stmt->bind_param("siii", $user_id, $location_id, $date_id, $time_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    // إذا كان عدد الصفوف أكبر من صفر، فهذا يعني وجود حجز مسبق
    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        // إرجاع رسالة خطأ واضحة
        return "لقد حجزت مسبقًا لنفس الموقع والتاريخ والوقت. لا يمكن تكرار الحجز."; 
    }
    $check_stmt->close();
    
    // 2. المتابعة في حال عدم وجود حجز مسبق
    $permit_number = $this->generateUniquePermitNumber();
    $barcode = $this->generateBarcodeString($permit_number);
    
    // الإدراج في جدول Permits
    $stmt = $this->conn->prepare("
        INSERT INTO Permits (user_id, location_id, date_id, time_id, permit_number, barcode) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("siiiss", 
        $user_id, 
        $location_id, 
        $date_id, 
        $time_id, 
        $permit_number, 
        $barcode
    );
    
    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        return $last_id; 
    } else {
        $error = $stmt->error;
        $stmt->close();
        return "فشل قاعدة البيانات أثناء الإدراج: " . $error; 
    }
}

// ==========================================================
// ====== الدالة المضافة لجلب تفاصيل التصريح الكاملة ======
// ==========================================================
public function getPermitDetails($permit_id) {
    $stmt = $this->conn->prepare("
        SELECT 
            p.permit_id, p.permit_number, p.barcode, p.status, p.created_at, p.expires_at,
            u.full_name, u.user_id, u.username, u.phone_number,
            l.location_name,
            ad.available_date,
            at.available_time_start, at.available_time_end
        FROM Permits p
        JOIN Users u ON p.user_id = u.user_id
        JOIN Locations l ON p.location_id = l.location_id
        JOIN Available_Dates ad ON p.date_id = ad.date_id
        JOIN Available_Times at ON p.time_id = at.time_id
        WHERE p.permit_id = ?
    ");
    
    $stmt->bind_param("i", $permit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $permit = $result->fetch_assoc();
    $stmt->close();
    return $permit;
}
// ====== جلب كل تصاريح المستخدم مع إمكانية البحث ======
public function getUserPermits($user_id, $search_term = '') {
    // نجلب البيانات الأساسية للتصريح والموقع والوقت
    $query = "
        SELECT 
            p.permit_id, p.permit_number, p.created_at, p.expires_at,
            l.location_name,
            ad.available_date,
            at.available_time_start, at.available_time_end
        FROM Permits p
        JOIN Locations l ON p.location_id = l.location_id
        JOIN Available_Dates ad ON p.date_id = ad.date_id
        JOIN Available_Times at ON p.time_id = at.time_id
        WHERE p.user_id = ?
    ";
    
    $params = [$user_id];
    $types = "i";

    if (!empty($search_term)) {
        // البحث برقم التصريح أو اسم الموقع أو التاريخ
        $query .= " AND (
            p.permit_number LIKE ? OR
            l.location_name LIKE ? OR
            ad.available_date LIKE ?
        )";
        $search_param = "%" . $search_term . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    // نرتب حسب التاريخ الأحدث أولاً
    $query .= " ORDER BY ad.available_date DESC, at.available_time_start DESC";

    $stmt = $this->conn->prepare($query);
    
    // ربط المعاملات
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
    $permits = [];
    while($row = $result->fetch_assoc()){
        $permits[] = $row;
    }
    $stmt->close();
    return $permits;
}



    
}

?>
