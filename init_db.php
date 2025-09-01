<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';

try {
    
    if (file_exists(DB_FILE)) {
        unlink(DB_FILE);
        echo "已删除旧的数据库文件。\n";
    }

    $db = Database::getInstance();
    $dbConn = $db->getConnection();
    
    
    $dbConn->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        is_admin INTEGER DEFAULT 0,
        api_key TEXT UNIQUE,
        storage_quota INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )');

    
    $dbConn->exec('CREATE TABLE IF NOT EXISTS system_settings (
        name TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )');

    
    $dbConn->exec('CREATE TABLE IF NOT EXISTS invite_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        created_by INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        used_by INTEGER DEFAULT NULL,
        used_at DATETIME DEFAULT NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (used_by) REFERENCES users(id)
    )');

    
    $dbConn->exec("INSERT OR REPLACE INTO system_settings (name, value) VALUES ('default_user_quota', '" . DEFAULT_USER_QUOTA . "')");

    
    $userManager = new UserManager();
    $quota = ADMIN_SPACE_QUOTA; // 管理员使用管理员配额
    $adminId = $userManager->createUser('admin', 'admin', null, true, $quota);
    
    if (!$adminId) {
        throw new Exception("创建管理员账号失败");
    }

    
    $dbConn->exec('CREATE TABLE IF NOT EXISTS shares (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        file_path TEXT NOT NULL,
        share_code TEXT NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');

    echo "数据库初始化成功！\n";
    echo "数据库文件位置：" . DB_FILE . "\n";
    echo "默认管理员账号：admin\n";
    echo "默认管理员密码：admin\n";

} catch (PDOException $e) {
    die("数据库错误: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("初始化错误: " . $e->getMessage() . "\n");
} 