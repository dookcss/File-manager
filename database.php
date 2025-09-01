<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $db = null;

    private function __construct() {
        $this->initDatabase();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initDatabase() {
        try {
            $this->db = new PDO('sqlite:' . DB_FILE);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->db;
    }

    public function createInviteCode($adminId, $expiresIn) {
        try {
            $code = bin2hex(random_bytes(16));
            $stmt = $this->db->prepare(
                "INSERT INTO invite_codes (code, created_by, expires_at) 
                 VALUES (?, ?, datetime('now', ?))");
            $stmt->execute([$code, $adminId, "+{$expiresIn} days"]);
            return $code;
        } catch (Exception $e) {
            error_log("创建邀请码失败: " . $e->getMessage());
            return false;
        }
    }

    public function getInviteCodes($adminId) {
        $stmt = $this->db->prepare(
            "SELECT ic.*, u.username as used_by_username,
             CASE 
                 WHEN ic.used_by IS NOT NULL THEN u.username
                 WHEN datetime('now') > ic.expires_at THEN '已过期'
                 ELSE '未使用'
             END as status
             FROM invite_codes ic 
             LEFT JOIN users u ON ic.used_by = u.id 
             WHERE ic.created_by = ? 
             ORDER BY ic.created_at DESC"
        );
        $stmt->execute([$adminId]);
        return $stmt->fetchAll();
    }

    public function deleteInviteCode($codeId, $adminId) {
        $stmt = $this->db->prepare(
            "DELETE FROM invite_codes 
             WHERE id = ? AND created_by = ? AND used_by IS NULL");
        return $stmt->execute([$codeId, $adminId]);
    }

    public function validateInviteCode($code) {
        $stmt = $this->db->prepare(
            "SELECT * FROM invite_codes 
             WHERE code = ? 
             AND used_by IS NULL 
             AND expires_at > datetime('now')");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function useInviteCode($code, $userId) {
        $stmt = $this->db->prepare(
            "UPDATE invite_codes 
             SET used_by = ?, used_at = CURRENT_TIMESTAMP 
             WHERE code = ? 
             AND used_by IS NULL 
             AND expires_at > datetime('now')");
        return $stmt->execute([$userId, $code]);
    }

    public function getUserByInviteCode($code) {
        $stmt = $this->db->prepare(
            "SELECT u.*, ic.code as invite_code 
             FROM users u 
             JOIN invite_codes ic ON ic.used_by = u.id 
             WHERE ic.code = ?"
        );
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
}

class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createUser($username, $password, $email = null, $isAdmin = false, $quota = null) {
        try {
            if ($quota === null) {
                $stmt = $this->db->prepare("SELECT value FROM system_settings WHERE name = 'default_user_quota'");
                $stmt->execute();
                $result = $stmt->fetch();
                $quota = $result ? $result['value'] : DEFAULT_USER_QUOTA;
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, password, email, is_admin, api_key, storage_quota) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            
            $result = $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $email,
                $isAdmin ? 1 : 0,
                generateApiKey(),
                $quota
            ]);

            if ($result) {
                $userPath = USER_SPACE_PATH . '/' . $username;
                if (!file_exists($userPath)) {
                    if (!mkdir($userPath, 0755, true)) {
                        throw new Exception('无法创建用户文件夹');
                    }
                }
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log('创建用户失败：' . $e->getMessage());
            throw $e;
        }
    }

    public function isUsernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function verifyUser($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }
        return false;
    }

    public function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function changePassword($userId, $newPassword) {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $userId
        ]);
    }

    public function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function getAllUsers() {
        return $this->db->query("SELECT id, username, email, created_at, last_login, is_admin FROM users")->fetchAll();
    }

    public function deleteUser($userId) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ? AND is_admin = 0");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('用户不存在或无法删除管理员');
            }
            
            $userPath = USER_SPACE_PATH . '/' . $user['username'];
            if (file_exists($userPath)) {
                if (!deleteDirectory($userPath)) {
                    throw new Exception('删除用户文件失败');
                }
            }
            
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
            if (!$stmt->execute([$userId])) {
                throw new Exception('删除用户记录失败');
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('删除用户失败：' . $e->getMessage());
            return false;
        }
    }

    public function resetPassword($userId) {
        try {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $password = '';
            
            $password .= $chars[rand(0, 25)];
            $password .= $chars[rand(26, 51)];
            $password .= $chars[rand(52, 61)];
            
            for($i = 0; $i < 5; $i++) {
                $password .= $chars[rand(0, strlen($chars) - 1)];
            }
            
            $password = str_shuffle($password);
            
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                throw new Exception('用户不存在');
            }
            
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([
                password_hash($password, PASSWORD_DEFAULT),
                $userId
            ]);
            
            if (!$result) {
                throw new Exception('密码更新失败');
            }
            
            $this->db->commit();
            return $password;
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('重置密码失败：' . $e->getMessage());
            return false;
        }
    }
} 