<?php
/**
 * includes/db.php
 * اتصال قاعدة البيانات + دوال مساعدة
 */
declare(strict_types=1);

// ─── إعدادات الاتصال — حماية البيانات برمجياً عبر متغيرات البيئة ──────────────
define('DB_HOST',    getenv('DB_HOST') ?: 'mysql-38536dc4-sajjadhendy-d651.aivencloud.com');
define('DB_PORT',    getenv('DB_PORT') ?: '14481');
define('DB_NAME',    getenv('DB_NAME') ?: 'emergency_matrix_db');
define('DB_USER',    getenv('DB_USER') ?: 'avnadmin');
// تم إزالة كلمة المرور الصريحة نهائياً لتجاوز حظر GitHub الأمنّي
define('DB_PASS',    getenv('DB_PASSWORD')); 
define('DB_CHARSET', 'utf8mb4');


// ─── Singleton PDO ─────────────────────────────────────────────
class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo !== null) return self::$pdo;

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone='+03:00'",
            ]);
        } catch (PDOException $e) {
            error_log('[DB] ' . $e->getMessage());
            http_response_code(503);
            die(json_encode(['success'=>false,'message'=>'خطأ في قاعدة البيانات','data'=>null],
                JSON_UNESCAPED_UNICODE));
        }

        return self::$pdo;
    }
}

function db(): PDO { return Database::get(); }

// ─── استجابة JSON موحّدة ────────────────────────────────────────
function jsonOut(bool $ok, mixed $data=null, string $msg='', int $code=200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['success'=>$ok,'message'=>$msg,'data'=>$data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
