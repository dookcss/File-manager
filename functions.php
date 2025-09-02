<?php
require_once 'config.php';
require_once 'database.php';


function login($username, $password) {
    $userManager = new UserManager();
    $user = $userManager->verifyUser($username, $password);
    if ($user) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}


function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user && $user['is_admin'] == 1;
}

function logout() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    session_destroy();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, email, api_key, is_admin, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        if (empty($user['api_key'])) {
            $apiKey = generateApiKey();
            $updateStmt = $db->prepare("UPDATE users SET api_key = ? WHERE id = ?");
            $updateStmt->execute([$apiKey, $user['id']]);
            $user['api_key'] = $apiKey;
        }
        
        return $user;
    } catch (Exception $e) {
        error_log('Error in getCurrentUser: ' . $e->getMessage());
        return null;
    }
}


function createFolder($name, $parent = UPLOAD_PATH) {
    $path = $parent . '/' . basename($name);
    if (!file_exists($path)) {
        if (mkdir($path, 0755)) {
            if (!@chmod($path, 0755)) {
                error_log("Failed to set permissions on new folder: $path");
            }
            return ['success' => true, 'message' => '文件夹创建成功'];
        }
    }
    error_log("Failed to create folder: $path");
    return ['success' => false, 'message' => '文件夹创建失败，请联系管理员检查权限'];
}

function deleteFolder($path) {
    $userPath = getUserStoragePath();
    if (!$userPath) {
        return ['success' => false, 'message' => '用户未登录'];
    }

    $fullPath = $userPath . '/' . trim($path, '/');

    if (!is_dir($fullPath)) {
        return ['success' => false, 'message' => '文件夹不存在'];
    }

    $realTarget = realpath($fullPath);
    $realUser = realpath($userPath);
    if ($realTarget === false || strpos($realTarget, $realUser) !== 0) {
        return ['success' => false, 'message' => '无权访问此目录'];
    }

    if (deleteDirectory($fullPath)) {
        return ['success' => true, 'message' => '文件夹删除成功'];
    }
    return ['success' => false, 'message' => '文件夹删除失败'];
}


function searchFiles($keyword, $dir = null) {
    if ($dir === null) {
        $dir = getUserStoragePath();
    }

    $results = [];
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                $path = $dir . '/' . $item;
                if (stripos($item, $keyword) !== false) {
                    $results[] = [
                        'name' => $item,
                        'size' => filesize($path),
                        'type' => filetype($path),
                        'modified' => date('Y-m-d H:i:s', filemtime($path)),
                        'path' => $path
                    ];
                }
                if (is_dir($path)) {
                    $subResults = searchFiles($keyword, $path);
                    $results = array_merge($results, $subResults);
                }
            }
        }
    }
    return $results;
}


function getPreviewUrl($file) {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'])) {
        return 'preview.php?file=' . urlencode($file['path']);
    }
    
    if (in_array($extension, [
        'txt', 'csv', 'md', 'php', 'js', 'css', 'html', 'htm', 'xml', 'json', 'yaml', 'yml','plain',
        'ini', 'log', 'conf', 'config', 'sh', 'bash', 'sql', 'properties', 'env', 'gitignore',
        'c', 'cpp', 'h', 'hpp', 'java', 'py', 'rb', 'go', 'rust', 'ts', 'tsx', 'jsx', 'vue',
        'gradle', 'groovy', 'perl', 'php4', 'php5', 'phtml', 'swift', 'r', 'scala', 'kotlin','lua'
    ])) {
        return 'preview.php?file=' . urlencode($file['path']);
    }
    
    if ($extension === 'pdf') {
        return 'preview.php?file=' . urlencode($file['path']);
    }
    
    if (in_array($extension, VIDEO_EXTENSIONS)) {
        return 'video.php?file=' . urlencode($file['path']);
    }
    
    return false;
}


function getFileList($dir = null, $subdir = '') {
    if ($dir === null) {
        $dir = getUserStoragePath();
    }
    
    $currentPath = $dir;
    if ($subdir) {
        $currentPath .= '/' . trim($subdir, '/');
        if (strpos(realpath($currentPath), realpath($dir)) !== 0) {
            return [];
        }
    }
    
    $files = [];
    if (is_dir($currentPath)) {
        $items = scandir($currentPath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                $path = $currentPath . '/' . $item;
                $relativePath = $subdir ? ($subdir . '/' . $item) : $item;
                $files[] = [
                    'name' => $item,
                    'size' => filesize($path),
                    'type' => filetype($path),
                    'modified' => date('Y-m-d H:i:s', filemtime($path)),
                    'path' => $relativePath
                ];
            }
        }
    }
    return $files;
}


function uploadFile($file, $action = 'skip') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => '无效的文件参数'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传失败'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => '文件大小超过限制（' . formatFileSize(MAX_UPLOAD_SIZE) . '）'];
    }

    if (!checkUserStorageQuota($file['size'])) {
        return ['success' => false, 'message' => '存储空间不足'];
    }

    $userPath = getUserStoragePath();
    if (!$userPath) {
        return ['success' => false, 'message' => '用户未登录'];
    }

    $filename = basename($file['name']);
    $destination = $userPath . '/' . $filename;

    if (file_exists($destination)) {
        if ($action === 'skip') {
            return [
                'success' => false, 
                'message' => '文件已存在', 
                'conflict' => true,
                'filename' => $filename
            ];
        } elseif ($action === 'rename') {
            $info = pathinfo($filename);
            $i = 1;
            while (file_exists($userPath . '/' . $info['filename'] . "($i)." . $info['extension'])) {
                $i++;
            }
            $filename = $info['filename'] . "($i)." . $info['extension'];
            $destination = $userPath . '/' . $filename;
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => '文件移动失败'];
    }

    return [
        'success' => true, 
        'message' => '文件上传成功', 
        'filename' => $filename
    ];
}


function deleteFile($filename) {
    $userPath = getUserStoragePath();
    if (!$userPath) {
        return ['success' => false, 'message' => '用户未登录'];
    }

    $path = $userPath . '/' . trim($filename, '/');
    
    if (!file_exists($path)) {
        return ['success' => false, 'message' => '文件不存在'];
    }


    if (strpos(realpath($path), realpath($userPath)) !== 0) {
        return ['success' => false, 'message' => '无权访问此文件'];
    }

    if (unlink($path)) {
        return ['success' => true, 'message' => '文件删除成功'];
    }

    return ['success' => false, 'message' => '文件删除失败'];
}


function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}


function getUserStoragePath() {
    if (!isLoggedIn()) {
        return false;
    }
    $path = USER_SPACE_PATH . '/' . $_SESSION['username'];
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}


function getUserStorageUsed($username = null) {
    $username = $username ?? $_SESSION['username'];
    $path = USER_SPACE_PATH . '/' . $username;
    
    if (!file_exists($path)) {
        return 0;
    }

    $size = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($files as $file) {
        $size += $file->getSize();
    }
    
    return $size;
}


function checkUserStorageQuota($fileSize) {
    $used = getUserStorageUsed();
    return ($used + $fileSize) <= USER_SPACE_QUOTA;
}


function generateApiKey() {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $key = '';
    for ($i = 0; $i < 12; $i++) {
        $key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $key;
}


function validateApiKey($apiKey) {
    if (empty($apiKey)) {
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    return $stmt->fetch() !== false;
}


function getUserQuota($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'];
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT storage_quota, is_admin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {

        if ($user['is_admin'] && $user['storage_quota'] === null) {
            return ADMIN_SPACE_QUOTA;
        }

        if ($user['storage_quota'] !== null) {
            return $user['storage_quota'];
        }

        return USER_SPACE_QUOTA;
    }
    
    return USER_SPACE_QUOTA;
}


function setUserQuota($userId, $quota) {
    if (!isAdmin()) {
        return ['success' => false, 'message' => '无权限执行此操作'];
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE users SET storage_quota = ? WHERE id = ?");
        if (!$stmt->execute([$quota, $userId])) {
            throw new Exception('更新失败');
        }
        
        $db->commit();
        return ['success' => true, 'message' => '存储配额设置成功'];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => '存储配额设置失败：' . $e->getMessage()];
    }
}


function getAllUsers() {
    if (!isAdmin()) {
        return [];
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, username, email, is_admin, created_at, last_login, storage_quota FROM users");
    $users = $stmt->fetchAll();
    
    foreach ($users as &$user) {
        $user['storage_used'] = getUserStorageUsed($user['username']);
        $user['storage_quota'] = $user['storage_quota'] ?? ($user['is_admin'] ? ADMIN_SPACE_QUOTA : USER_SPACE_QUOTA);
    }
    
    return $users;
}


function getSystemTotalUsed() {
    try {
        $db = Database::getInstance()->getConnection();
        $users = $db->query("SELECT username FROM users")->fetchAll();
        
        $totalUsed = 0;
        foreach ($users as $user) {
            $totalUsed += getUserStorageUsed($user['username']);
        }
        
        return $totalUsed;
    } catch (Exception $e) {
        error_log('获取系统总存储使用量失败：' . $e->getMessage());
        return 0;
    }
}


function getTotalUserQuotas() {
    try {
        $db = Database::getInstance()->getConnection();
        $users = $db->query("SELECT storage_quota, is_admin FROM users")->fetchAll();
        
        $totalQuota = 0;
        foreach ($users as $user) {
            if ($user['is_admin']) {
                $totalQuota += $user['storage_quota'] ?? ADMIN_SPACE_QUOTA;
            } else {
                $totalQuota += $user['storage_quota'] ?? USER_SPACE_QUOTA;
            }
        }
        
        return $totalQuota;
    } catch (Exception $e) {
        error_log('获取用户配额总和失败：' . $e->getMessage());
        return 0;
    }
}


function canRegisterNewUser() {
    try {
        $totalQuotas = getTotalUserQuotas();
        
        $newUserQuota = getDefaultUserQuota();
        
        if (($totalQuotas + $newUserQuota) > SYSTEM_TOTAL_QUOTA) {
            error_log('系统配额不足：当前总配额=' . formatFileSize($totalQuotas) . 
                     '，新用户配额=' . formatFileSize($newUserQuota) . 
                     '，系统限制=' . formatFileSize(SYSTEM_TOTAL_QUOTA));
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log('检查注册限制失败：' . $e->getMessage());
        return false;
    }
}


function getDefaultUserQuota() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT value FROM system_settings WHERE name = 'default_user_quota'");
    $result = $stmt->fetch();
    
    if (!$result) {
        return DEFAULT_USER_QUOTA;
    }
    
    return (int)$result['value'];
}


function setDefaultUserQuota($quota) {
    if (!isAdmin()) {
        return ['success' => false, 'message' => '无权限执行此操作'];
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        $stmt = $db->prepare("DELETE FROM system_settings WHERE name = 'default_user_quota'");
        $stmt->execute();
        
        $stmt = $db->prepare("INSERT INTO system_settings (name, value) VALUES ('default_user_quota', ?)");
        if (!$stmt->execute([(string)$quota])) {
            throw new Exception('设置失败');
        }
        
        $db->commit();
        return ['success' => true, 'message' => '默认配额设置成功'];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => '默认配额设置失败：' . $e->getMessage()];
    }
}


function deleteUserAccount($userId) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('用户不存在');
        }
        
        $userPath = USER_SPACE_PATH . '/' . $user['username'];
        if (file_exists($userPath)) {
            deleteDirectory($userPath);
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        $db->commit();
        return ['success' => true, 'message' => '账号注销成功'];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => '账号注销失败：' . $e->getMessage()];
    }
}


function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

function getMaxUploadSize() {
    $defaultSize = 500 * 1024 * 1024;
    
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    
    $uploadBytes = intval($uploadMax) * 1024 * 1024;
    $postBytes = intval($postMax) * 1024 * 1024;
    
    return min($defaultSize, $uploadBytes, $postBytes);
}

/**
 * 获取文件内容（API版本）
 * @param string $filePath 文件路径
 * @param string $apiKey API密钥
 * @return array 包含文件内容和版本的数组
 * @throws Exception 如果文件不存在或无法读取
 */
function getFileContent($filePath, $apiKey = null) {
    if ($apiKey) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT username FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('无效的API密钥');
        }
        
        $fullPath = USER_SPACE_PATH . '/' . $user['username'] . '/' . trim($filePath, '/');
        
        $userPath = USER_SPACE_PATH . '/' . $user['username'];
        if (strpos(realpath($fullPath), realpath($userPath)) !== 0) {
            throw new Exception('无权访问此文件');
        }
    } else {
        $fullPath = getUserStoragePath() . '/' . trim($filePath, '/');
    }
    
    if (!file_exists($fullPath)) {
        throw new Exception('文件不存在');
    }
    
    if (!is_readable($fullPath)) {
        throw new Exception('无法读取文件');
    }
    
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $allowedTypes = [
        'txt', 'md', 'log', 'ini', 'conf', 'env',
        'html', 'htm', 'css', 'js', 'jsx', 'ts', 'tsx', 'vue', 'json', 'xml','ini','conf','env','log','config',
        'php', 'py', 'java', 'rb', 'go', 'rs', 'scala', 'kt', 'kts',
        'c', 'cpp', 'h', 'hpp', 'cs',
        'yaml', 'yml', 'toml', 'properties',
        'sh', 'bash', 'zsh', 'fish',
        'sql', 'r', 'pl', 'swift', 'lua', 'tcl', 'ps1', 'psm1'
    ];
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('不支持编辑此类型的文件');
    }
    
    if (filesize($fullPath) > 6 * 1024) {
        throw new Exception('文件太大，超过6KB限制，不支持在线编辑');
    }
    
    $content = file_get_contents($fullPath);
    if ($content === false) {
        throw new Exception('读取文件失败');
    }
    
    return [
        'content' => $content,
        'version' => md5_file($fullPath)
    ];
}


function checkApiAuth() {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? null;
    
    if (!$apiKey) {
        throw new Exception('缺少API密钥');
    }
    
    if (!validateApiKey($apiKey)) {
        throw new Exception('无效的API密钥');
    }
    
    return true;
} 