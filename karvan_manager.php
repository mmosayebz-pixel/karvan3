<?php
/**
 * فایل اصلی مدیریت کاروان
 * تمام عملیات سایت از این فایل مدیریت می‌شود
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// پوشه دیتابیس‌ها
define('DB_PATH', __DIR__ . '/databases/');
define('MANAGER_DB', DB_PATH . 'manager_database.sqlite');

// ============================================
// توابع کمکی
// ============================================

// تابع لاگ خطا - شماره 999
function logError_999($message, $file = '') {
    $logPath = __DIR__ . '/logs/error_log.txt';
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    file_put_contents($logPath, date('Y-m-d H:i:s') . " - [$file] $message\n", FILE_APPEND);
}
// پایان تابع logError_999

// تابع لاگ دیباگ - شماره 998
function logDebug_999($message, $file = '') {
    $logPath = __DIR__ . '/logs/debug_log.txt';
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    file_put_contents($logPath, date('Y-m-d H:i:s') . " - [$file] $message\n", FILE_APPEND);
}
// پایان تابع logDebug_999

// تابع اتصال به دیتابیس مدیر - شماره 001
function connectManagerDB_001() {
    try {
        if (!file_exists(MANAGER_DB)) {
            throw new Exception("دیتابیس مدیر وجود ندارد. لطفاً initial.php را اجرا کنید.");
        }
        $db = new PDO("sqlite:" . MANAGER_DB);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        logError_999("خطا در اتصال به دیتابیس مدیر: " . $e->getMessage(), "karvan_manager.php");
        return null;
    }
}
// پایان تابع connectManagerDB_001

// تابع اتصال به دیتابیس کاروان - شماره 002
function connectKarvanDB_002($serialNumber) {
    try {
        $dbPath = DB_PATH . $serialNumber . '.sqlite';
        if (!file_exists($dbPath)) {
            throw new Exception("دیتابیس کاروان با شماره $serialNumber وجود ندارد.");
        }
        $db = new PDO("sqlite:" . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        logError_999("خطا در اتصال به دیتابیس کاروان $serialNumber: " . $e->getMessage(), "karvan_manager.php");
        return null;
    }
}
// پایان تابع connectKarvanDB_002

// تابع ایجاد دیتابیس کاروان جدید - شماره 003
function createKarvanDatabase_003($serialNumber) {
    try {
        $dbPath = DB_PATH . $serialNumber . '.sqlite';
        if (file_exists($dbPath)) {
            return false; // دیتابیس قبلاً وجود دارد
        }
        
        $db = new PDO("sqlite:" . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // جدول users
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            phone TEXT,
            chat_permission TEXT DEFAULT 'read_write',
            is_active INTEGER DEFAULT 1,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // جدول chat_messages
        $db->exec("CREATE TABLE chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // جدول user_locations برای ذخیره موقعیت‌ها
        $db->exec("CREATE TABLE user_locations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            latitude REAL NOT NULL,
            longitude REAL NOT NULL,
            location_time DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        logDebug_999("دیتابیس کاروان $serialNumber ایجاد شد", "karvan_manager.php");
        return true;
    } catch (PDOException $e) {
        logError_999("خطا در ایجاد دیتابیس کاروان $serialNumber: " . $e->getMessage(), "karvan_manager.php");
        return false;
    }
}
// پایان تابع createKarvanDatabase_003

// تابع تولید شماره سریال بعدی - شماره 004
function getNextSerialNumber_004() {
    $db = connectManagerDB_001();
    if (!$db) return null;
    
    try {
        $stmt = $db->query("SELECT MAX(serial_number) as max_serial FROM karvanha");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextSerial = $result['max_serial'] ? $result['max_serial'] + 1 : 1001;
        return $nextSerial;
    } catch (PDOException $e) {
        logError_999("خطا در دریافت شماره سریال بعدی: " . $e->getMessage(), "karvan_manager.php");
        return null;
    }
}
// پایان تابع getNextSerialNumber_004

// تابع تولید رمز تصادفی ۶ رقمی - شماره 005
function generateRandomPassword_005() {
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}
// پایان تابع generateRandomPassword_005

// ============================================
// مدیریت درخواست‌های API
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';

// بررسی اکشن درخواست
switch ($action) {
    case 'manager_login':
        managerLogin_006();
        break;
    case 'change_manager_password':
        changeManagerPassword_007();
        break;
    case 'create_karvan_request':
        createKarvanRequest_008();
        break;
    case 'approve_karvan':
        approveKarvan_009();
        break;
    case 'get_karvan_list':
        getKarvanList_010();
        break;
    case 'update_karvan':
        updateKarvan_011();
        break;

case 'get_all_requests':
    getAllRequests_026();
    break;

    case 'get_karvan_messages':
        getKarvanMessages_012();
        break;
    case 'delete_message':
        deleteMessage_013();
        break;
    case 'user_register':
        userRegister_014();
        break;
    case 'user_login':
        userLogin_015();
        break;
    case 'send_location':
        sendLocation_016();
        break;
    case 'get_locations':
        getLocations_017();
        break;
    case 'leader_login':
        leaderLogin_018();
        break;
    case 'get_pending_requests':
        getPendingRequests_019();
        break;
    case 'approve_user':
        approveUser_020();
        break;
    case 'send_chat_message':
        sendChatMessage_021();
        break;
    case 'get_chat_messages':
        getChatMessages_022();
        break;
    case 'delete_all_messages':
        deleteAllMessages_023();
        break;
    case 'get_users_list':
        getUsersList_024();
        break;
    case 'update_user':
        updateUser_025();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'اکشن نامعتبر']);
        break;
}

// ============================================
// پیاده‌سازی توابع API
// ============================================

// تابع ورود مدیر - شماره 006
function managerLogin_006() {
    // ورودی: password (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $password = isset($input['password']) ? $input['password'] : '';
    
    if (empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'رمز عبور را وارد کنید']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->prepare("SELECT password FROM manager_password WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['password'] === $password) {
            echo json_encode(['status' => 'success', 'message' => 'ورود موفق']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'رمز عبور اشتباه است']);
        }
    } catch (PDOException $e) {
        logError_999("خطا در ورود مدیر: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع managerLogin_006

// تابع تغییر رمز مدیر - شماره 007
function changeManagerPassword_007() {
    // ورودی: current_password, new_password (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $currentPassword = isset($input['current_password']) ? $input['current_password'] : '';
    $newPassword = isset($input['new_password']) ? $input['new_password'] : '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'همه فیلدها را پر کنید']);
        return;
    }
    
    if (strlen($newPassword) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'رمز جدید حداقل ۶ کاراکتر باشد']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        // بررسی رمز فعلی
        $stmt = $db->prepare("SELECT password FROM manager_password WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['password'] !== $currentPassword) {
            echo json_encode(['status' => 'error', 'message' => 'رمز فعلی اشتباه است']);
            return;
        }
        
        // به‌روزرسانی رمز
        $stmt = $db->prepare("UPDATE manager_password SET password = ? WHERE id = 1");
        $stmt->execute([$newPassword]);
        
        logDebug_999("رمز مدیر تغییر کرد", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'رمز با موفقیت تغییر کرد']);
    } catch (PDOException $e) {
        logError_999("خطا در تغییر رمز مدیر: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع changeManagerPassword_007

// تابع ایجاد درخواست کاروان جدید - شماره 008
function createKarvanRequest_008() {
    // ورودی: karvan_name, phone (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...', 'request_id': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $karvanName = isset($input['karvan_name']) ? trim($input['karvan_name']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    
    if (empty($karvanName) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'نام کاروان و شماره تماس را وارد کنید']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO new_requests (karvan_name, phone, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$karvanName, $phone]);
        $requestId = $db->lastInsertId();
        
        logDebug_999("درخواست کاروان جدید: $karvanName - $phone", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'درخواست شما ثبت شد', 'request_id' => $requestId]);
    } catch (PDOException $e) {
        logError_999("خطا در ثبت درخواست کاروان: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت درخواست']);
    }
}
// پایان تابع createKarvanRequest_008

// تابع تایید کاروان توسط مدیر - شماره 009
function approveKarvan_009() {
    // ورودی: request_id, karvan_name, phone (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...', 'serial': '...', 'password': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = isset($input['request_id']) ? intval($input['request_id']) : 0;
    $karvanName = isset($input['karvan_name']) ? trim($input['karvan_name']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    
    if ($requestId <= 0 || empty($karvanName) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        // بررسی وجود درخواست
        $stmt = $db->prepare("SELECT * FROM new_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo json_encode(['status' => 'error', 'message' => 'درخواست پیدا نشد یا قبلاً بررسی شده است']);
            return;
        }
        
        // تولید شماره سریال و رمز
        $serialNumber = getNextSerialNumber_004();
        if ($serialNumber === null) {
            echo json_encode(['status' => 'error', 'message' => 'خطا در تولید شماره سریال']);
            return;
        }
        
        $karvanPassword = generateRandomPassword_005();
        
        // ایجاد دیتابیس کاروان
        if (!createKarvanDatabase_003($serialNumber)) {
            echo json_encode(['status' => 'error', 'message' => 'خطا در ایجاد دیتابیس کاروان']);
            return;
        }
        
        // ثبت در جدول karvanha
        $stmt = $db->prepare("INSERT INTO karvanha (name, serial_number, password, case_status, max_number) VALUES (?, ?, ?, 1, 100)");
        $stmt->execute([$karvanName, $serialNumber, $karvanPassword]);
        
        // به‌روزرسانی وضعیت درخواست
        $stmt = $db->prepare("UPDATE new_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // ثبت رییس کاروان در دیتابیس کاروان
        $karvanDB = connectKarvanDB_002($serialNumber);
        if ($karvanDB) {
            // رییس کاروان با username ویژه
            $stmt = $karvanDB->prepare("INSERT INTO users (username, password, phone, chat_permission, is_active) VALUES ('leader', ?, ?, 'read_write', 1)");
            $stmt->execute([$karvanPassword, $phone]);
            
            // اضافه کردن رییس به جدول موقعیت‌ها
            $stmt = $karvanDB->prepare("INSERT INTO user_locations (username, latitude, longitude, location_time) VALUES ('leader', 0, 0, datetime('now'))");
            $stmt->execute();
        }
        
        logDebug_999("کاروان $karvanName با شماره $serialNumber تایید شد", "karvan_manager.php");
        
        echo json_encode([
            'status' => 'success',
            'message' => 'کاروان با موفقیت ایجاد شد',
            'serial' => $serialNumber,
            'password' => $karvanPassword,
            'karvan_name' => $karvanName
        ]);
    } catch (PDOException $e) {
        logError_999("خطا در تایید کاروان: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع approveKarvan_009

// تابع دریافت لیست کاروان‌ها - شماره 010
function getKarvanList_010() {
    // ورودی: ندارد
    // خروجی: لیست کاروان‌ها با تمام اطلاعات
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->query("SELECT * FROM karvanha ORDER BY serial_number");
        $karvanha = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $karvanha]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت لیست کاروان‌ها: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات']);
    }
}
// پایان تابع getKarvanList_010

// تابع ویرایش کاروان - شماره 011
function updateKarvan_011() {
    // ورودی: id, name, serial_number, password, case_status, max_number (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $name = isset($input['name']) ? trim($input['name']) : '';
    $serialNumber = isset($input['serial_number']) ? intval($input['serial_number']) : 0;
    $password = isset($input['password']) ? trim($input['password']) : '';
    $caseStatus = isset($input['case_status']) ? intval($input['case_status']) : 1;
    $maxNumber = isset($input['max_number']) ? intval($input['max_number']) : 100;
    
    if ($id <= 0 || empty($name) || $serialNumber <= 0 || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->prepare("UPDATE karvanha SET name = ?, serial_number = ?, password = ?, case_status = ?, max_number = ? WHERE id = ?");
        $stmt->execute([$name, $serialNumber, $password, $caseStatus, $maxNumber, $id]);
        
        logDebug_999("کاروان $name به‌روزرسانی شد", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'اطلاعات با موفقیت به‌روزرسانی شد']);
    } catch (PDOException $e) {
        logError_999("خطا در ویرایش کاروان: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع updateKarvan_011

// تابع دریافت پیام‌های چت کاروان - شماره 012
function getKarvanMessages_012() {
    // ورودی: serial_number (GET)
    // خروجی: لیست پیام‌ها
    
    $serialNumber = isset($_GET['serial']) ? intval($_GET['serial']) : 0;
    
    if ($serialNumber <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'شماره سریال نامعتبر']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $stmt = $db->query("SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 100");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $messages]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت پیام‌ها: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت پیام‌ها']);
    }
}
// پایان تابع getKarvanMessages_012

// تابع حذف پیام - شماره 013
function deleteMessage_013() {
    // ورودی: serial_number, message_id (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $messageId = isset($input['message_id']) ? intval($input['message_id']) : 0;
    
    if ($serialNumber <= 0 || $messageId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM chat_messages WHERE id = ?");
        $stmt->execute([$messageId]);
        
        logDebug_999("پیام $messageId از کاروان $serialNumber حذف شد", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'پیام با موفقیت حذف شد']);
    } catch (PDOException $e) {
        logError_999("خطا در حذف پیام: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع deleteMessage_013

// تابع ثبت‌نام کاربر - شماره 014
function userRegister_014() {
    // ورودی: serial_number, username, phone (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    
    if ($serialNumber <= 0 || empty($username) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        // بررسی وجود کاروان و فعال بودن
        $stmt = $db->prepare("SELECT * FROM karvanha WHERE serial_number = ? AND case_status = 1");
        $stmt->execute([$serialNumber]);
        $karvan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$karvan) {
            echo json_encode(['status' => 'error', 'message' => 'کاروان با این شماره سریال فعال نیست']);
            return;
        }
        
        // ثبت درخواست در دیتابیس کاروان (جدول جدید برای درخواست‌ها)
        // برای سادگی، درخواست‌ها را در جدول new_requests مدیر ذخیره می‌کنیم
        $stmt = $db->prepare("INSERT INTO new_requests (karvan_name, phone, status) VALUES (?, ?, 'pending_user')");
        $stmt->execute(["کاروان $serialNumber - $username", $phone]);
        $requestId = $db->lastInsertId();
        
        logDebug_999("درخواست ثبت‌نام کاربر $username در کاروان $serialNumber", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'درخواست شما برای عضویت در کاروان ثبت شد', 'request_id' => $requestId]);
    } catch (PDOException $e) {
        logError_999("خطا در ثبت‌نام کاربر: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع userRegister_014

// تابع ورود کاربر - شماره 015
function userLogin_015() {
    // ورودی: serial_number, username, password (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...', 'chat_permission': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    
    if ($serialNumber <= 0 || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND is_active = 1");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'status' => 'success',
                'message' => 'ورود موفق',
                'chat_permission' => $user['chat_permission'],
                'username' => $user['username']
            ]);
        } else {
            // بررسی اینکه کاربر وجود دارد اما غیرفعال است
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 0");
            $stmt->execute([$username]);
            $inactiveUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($inactiveUser) {
                echo json_encode(['status' => 'error', 'message' => 'دسترسی شما منقضی شده است']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'نام کاربری یا رمز عبور اشتباه است']);
            }
        }
    } catch (PDOException $e) {
        logError_999("خطا در ورود کاربر: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع userLogin_015

// تابع ارسال موقعیت - شماره 016
function sendLocation_016() {
    // ورودی: serial, username, latitude, longitude, location_time (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $latitude = isset($input['latitude']) ? floatval($input['latitude']) : 0;
    $longitude = isset($input['longitude']) ? floatval($input['longitude']) : 0;
    $locationTime = isset($input['location_time']) ? $input['location_time'] : date('Y-m-d H:i:s');
    
    if ($serialNumber <= 0 || empty($username) || $latitude == 0 || $longitude == 0) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO user_locations (username, latitude, longitude, location_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $latitude, $longitude, $locationTime]);
        
        logDebug_999("موقعیت $username در کاروان $serialNumber ذخیره شد", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'موقعیت با موفقیت ذخیره شد']);
    } catch (PDOException $e) {
        logError_999("خطا در ذخیره موقعیت: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع sendLocation_016

// تابع دریافت موقعیت‌ها - شماره 017
// تابع دریافت موقعیت‌ها - شماره 017
// ورودی: serial (GET)
// خروجی: آخرین موقعیت هر فرد
// تابع دریافت موقعیت‌ها - شماره 017
// ورودی: serial (GET)
// خروجی: آخرین موقعیت هر فرد + موقعیت فعلی کاربر (اگر ارسال نشده باشد)
function getLocations_017() {
    $serialNumber = isset($_GET['serial']) ? intval($_GET['serial']) : 0;
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    
    if ($serialNumber <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'شماره سریال نامعتبر']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        // دریافت آخرین موقعیت هر فرد
        $sql = "SELECT * FROM user_locations 
                WHERE id IN (
                    SELECT MAX(id) 
                    FROM user_locations 
                    GROUP BY username
                ) 
                ORDER BY location_time DESC";
        $stmt = $db->query($sql);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // اگر کاربر مشخص شده و در لیست نیست، یک رکورد پیش‌فرض برای او ایجاد کن
        if (!empty($username)) {
            $found = false;
            foreach ($locations as $loc) {
                if ($loc['username'] === $username) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // کاربر موقعیتی ندارد - یک موقعیت پیش‌فرض (تهران) با پیام خاص اضافه کن
                $locations[] = [
                    'username' => $username,
                    'latitude' => 35.6892,
                    'longitude' => 51.3890,
                    'location_time' => date('Y-m-d H:i:s'),
                    'is_default' => true  // علامت‌گذاری به عنوان موقعیت پیش‌فرض
                ];
            }
        }
        
        echo json_encode(['status' => 'success', 'data' => $locations]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت موقعیت‌ها: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت موقعیت‌ها']);
    }
}
// پایان تابع getLocations_017
// پایان تابع getLocations_017
// پایان تابع getLocations_017

// تابع ورود رییس کاروان - شماره 018
function leaderLogin_018() {
    // ورودی: serial_number, password (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $password = isset($input['password']) ? trim($input['password']) : '';
    
    if ($serialNumber <= 0 || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM karvanha WHERE serial_number = ? AND password = ? AND case_status = 1");
        $stmt->execute([$serialNumber, $password]);
        $karvan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($karvan) {
            echo json_encode([
                'status' => 'success',
                'message' => 'ورود موفق',
                'karvan_name' => $karvan['name']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'شماره سریال یا رمز عبور اشتباه است یا کاروان غیرفعال است']);
        }
    } catch (PDOException $e) {
        logError_999("خطا در ورود رییس: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع leaderLogin_018

// تابع دریافت درخواست‌های pending - شماره 019
function getPendingRequests_019() {
    // ورودی: serial (GET)
    // خروجی: لیست درخواست‌های کاربران برای این کاروان
    
    $serialNumber = isset($_GET['serial']) ? intval($_GET['serial']) : 0;
    
    if ($serialNumber <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'شماره سریال نامعتبر']);
        return;
    }
    
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM new_requests WHERE karvan_name LIKE ? AND status = 'pending_user'");
        $stmt->execute(["کاروان $serialNumber - %"]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $requests]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت درخواست‌ها: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت درخواست‌ها']);
    }
}
// پایان تابع getPendingRequests_019

// تابع تایید کاربر توسط رییس - شماره 020
function approveUser_020() {
    // ورودی: serial, username, phone, password (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $userPassword = isset($input['password']) ? trim($input['password']) : '';
    
    if ($serialNumber <= 0 || empty($username) || empty($phone) || empty($userPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    // اگر رمز ارسال نشده، تولید می‌کنیم
    if (empty($userPassword)) {
        $userPassword = generateRandomPassword_005();
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        // ثبت کاربر در دیتابیس کاروان
        $stmt = $db->prepare("INSERT INTO users (username, password, phone, chat_permission, is_active) VALUES (?, ?, ?, 'read_write', 1)");
        $stmt->execute([$username, $userPassword, $phone]);
        
        // اضافه کردن کاربر به جدول موقعیت‌ها
        $stmt = $db->prepare("INSERT INTO user_locations (username, latitude, longitude, location_time) VALUES (?, 0, 0, datetime('now'))");
        $stmt->execute([$username]);
        
        // به‌روزرسانی درخواست در دیتابیس مدیر
        $managerDB = connectManagerDB_001();
        if ($managerDB) {
            $stmt = $managerDB->prepare("UPDATE new_requests SET status = 'approved' WHERE karvan_name = ? AND phone = ? AND status = 'pending_user'");
            $stmt->execute(["کاروان $serialNumber - $username", $phone]);
        }
        
        logDebug_999("کاربر $username در کاروان $serialNumber تایید شد", "karvan_manager.php");
        echo json_encode([
            'status' => 'success',
            'message' => 'کاربر با موفقیت تایید شد',
            'password' => $userPassword
        ]);
    } catch (PDOException $e) {
        logError_999("خطا در تایید کاربر: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع approveUser_020

// تابع ارسال پیام چت - شماره 021
function sendChatMessage_021() {
    // ورودی: serial, username, message (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';
    
    if ($serialNumber <= 0 || empty($username) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        // بررسی دسترسی کاربر برای نوشتن
        $stmt = $db->prepare("SELECT chat_permission FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || ($user['chat_permission'] !== 'read_write' && $username !== 'leader')) {
            echo json_encode(['status' => 'error', 'message' => 'شما اجازه ارسال پیام ندارید']);
            return;
        }
        
        $stmt = $db->prepare("INSERT INTO chat_messages (username, message) VALUES (?, ?)");
        $stmt->execute([$username, $message]);
        
        logDebug_999("پیام از $username در کاروان $serialNumber ارسال شد", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'پیام با موفقیت ارسال شد']);
    } catch (PDOException $e) {
        logError_999("خطا در ارسال پیام: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع sendChatMessage_021

// تابع دریافت پیام‌های چت - شماره 022
function getChatMessages_022() {
    // ورودی: serial (GET)
    // خروجی: لیست پیام‌ها
    
    $serialNumber = isset($_GET['serial']) ? intval($_GET['serial']) : 0;
    
    if ($serialNumber <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'شماره سریال نامعتبر']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $stmt = $db->query("SELECT * FROM chat_messages ORDER BY created_at ASC LIMIT 50");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $messages]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت پیام‌های چت: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت پیام‌ها']);
    }
}
// پایان تابع getChatMessages_022

// تابع حذف همه پیام‌ها - شماره 023
function deleteAllMessages_023() {
    // ورودی: serial (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    
    if ($serialNumber <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'شماره سریال نامعتبر']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $db->exec("DELETE FROM chat_messages");
        
        logDebug_999("همه پیام‌های کاروان $serialNumber حذف شدند", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'همه پیام‌ها با موفقیت حذف شدند']);
    } catch (PDOException $e) {
        logError_999("خطا در حذف پیام‌ها: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع deleteAllMessages_023

// تابع دریافت لیست کاربران - شماره 024
function getUsersList_024() {
    // ورودی: serial (GET)
    // خروجی: لیست کاربران
    
    $serialNumber = isset($_GET['serial']) ? intval($_GET['serial']) : 0;
    
    if ($serialNumber <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'شماره سریال نامعتبر']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $stmt = $db->query("SELECT * FROM users WHERE username != 'leader' ORDER BY username");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $users]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت لیست کاربران: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات']);
    }
}
// پایان تابع getUsersList_024

// تابع ویرایش کاربر - شماره 025
function updateUser_025() {
    // ورودی: serial, username, password, chat_permission, is_active (POST)
    // خروجی: {'status': 'success'/'error', 'message': '...'}
    
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = isset($input['serial']) ? intval($input['serial']) : 0;
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    $chatPermission = isset($input['chat_permission']) ? trim($input['chat_permission']) : 'read_write';
    $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;
    
    if ($serialNumber <= 0 || empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
        return;
    }
    
    $db = connectKarvanDB_002($serialNumber);
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'دیتابیس کاروان پیدا نشد']);
        return;
    }
    
    try {
        $sql = "UPDATE users SET chat_permission = ?, is_active = ?";
        $params = [$chatPermission, $isActive];
        
        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = $password;
        }
        
        $sql .= " WHERE username = ?";
        $params[] = $username;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        logDebug_999("کاربر $username در کاروان $serialNumber به‌روزرسانی شد", "karvan_manager.php");
        echo json_encode(['status' => 'success', 'message' => 'اطلاعات کاربر با موفقیت به‌روزرسانی شد']);
    } catch (PDOException $e) {
        logError_999("خطا در ویرایش کاربر: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در سیستم']);
    }
}
// پایان تابع updateUser_025

// تابع دریافت تمام درخواست‌ها - شماره 026
function getAllRequests_026() {
    $db = connectManagerDB_001();
    if (!$db) {
        echo json_encode(['status' => 'error', 'message' => 'خطا در اتصال به دیتابیس']);
        return;
    }
    
    try {
        $stmt = $db->query("SELECT * FROM new_requests ORDER BY created_at DESC");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $requests]);
    } catch (PDOException $e) {
        logError_999("خطا در دریافت درخواست‌ها: " . $e->getMessage(), "karvan_manager.php");
        echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت درخواست‌ها']);
    }
}
// پایان تابع getAllRequests_026

?>