<?php
/**
 * فایل راه‌اندازی اولیه سیستم
 * این فایل فقط یکبار برای ایجاد دیتابیس مدیر اجرا می‌شود
 * پس از اجرا باید حذف شود
 */

// تابع ایجاد دیتابیس مدیر - شماره 001
function createManagerDatabase_001() {
    $dbPath = __DIR__ . '/databases/manager_database.sqlite';
    $dbDir = dirname($dbPath);
    
    // ایجاد پوشه databases اگر وجود ندارد
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }
    
    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // ایجاد جدول manager_password
        $db->exec("CREATE TABLE IF NOT EXISTS manager_password (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            password TEXT NOT NULL
        )");
        
        // ایجاد جدول karvanha
        $db->exec("CREATE TABLE IF NOT EXISTS karvanha (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            serial_number INTEGER UNIQUE NOT NULL,
            password TEXT NOT NULL,
            case_status INTEGER DEFAULT 1,
            max_number INTEGER DEFAULT 100,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // ایجاد جدول new_requests
        $db->exec("CREATE TABLE IF NOT EXISTS new_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            karvan_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // درج رمز پیش‌فرض مدیر
        $stmt = $db->prepare("INSERT INTO manager_password (password) VALUES ('123456')");
        $stmt->execute();
        
        // ایجاد لاگ موفقیت
        logDebug_999("دیتابیس مدیر با موفقیت ایجاد شد", "initial.php");
        
        return true;
    } catch (PDOException $e) {
        logError_999("خطا در ایجاد دیتابیس مدیر: " . $e->getMessage(), "initial.php");
        return false;
    }
}
// پایان تابع createManagerDatabase_001

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

// اجرای تابع اصلی
if (createManagerDatabase_001()) {
    echo "دیتابیس مدیر با موفقیت ایجاد شد.<br>";
    echo "رمز پیش‌فرض مدیر: 123456<br>";
    echo "<strong>لطفاً این فایل را پس از اجرا حذف کنید.</strong>";
} else {
    echo "خطا در ایجاد دیتابیس مدیر. لطفاً لاگ‌ها را بررسی کنید.";
}
?>