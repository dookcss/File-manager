<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error ($errno): $errstr in $errfile on line $errline");
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require_once '../functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

try {
    error_log('Request received: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Headers: ' . print_r(getallheaders(), true));
    
    $allHeaders = getallheaders();
    
    $apiKey = null;
    $possibleKeys = ['X-API-Key', 'x-api-key', 'HTTP_X_API_KEY'];
    
    foreach ($possibleKeys as $key) {
        if (isset($allHeaders[$key])) {
            $apiKey = trim($allHeaders[$key]);
            break;
        }
    }
    
    if (!$apiKey && isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
    }
    
    if (!$apiKey) {
        error_log('API Key not found in headers: ' . print_r($allHeaders, true));
        throw new Exception('缺少API密钥');
    }
    
    if (!validateApiKey($apiKey)) {
        error_log('Invalid API Key: ' . $apiKey);
        throw new Exception('无效的API密钥');
    }
    
    if (empty($_POST['file'])) {
        throw new Exception('缺少文件路径参数');
    }
    if (!isset($_POST['content'])) {
        throw new Exception('缺少文件内容参数');
    }
    if (empty($_POST['version'])) {
        throw new Exception('缺少文件版本参数');
    }

    $filePath = $_POST['file'];
    $content = $_POST['content'];
    $version = $_POST['version'];

    error_log("Processing file: $filePath");
    error_log("Content length: " . strlen($content));

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT username FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('无效的API密钥');
    }
    
    $fullPath = USER_SPACE_PATH . '/' . $user['username'] . '/' . trim($filePath, '/');
    error_log("Full path: $fullPath");
    
    if (!file_exists($fullPath)) {
        throw new Exception('文件不存在');
    }

    $userPath = USER_SPACE_PATH . '/' . $user['username'];
    if (strpos(realpath($fullPath), realpath($userPath)) !== 0) {
        throw new Exception('无权访问此文件');
    }

    if (!is_writable($fullPath)) {
        error_log("File not writable: $fullPath");
        error_log("File permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4));
        error_log("File owner: " . fileowner($fullPath));
        error_log("Process owner: " . posix_getuid());
        throw new Exception('文件没有写入权限');
    }

    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $allowedTypes = ['ini','json','txt', 'md', 'json', 'xml', 'html', 'css', 'js', 'php', 'py', 'java', 'c', 'cpp', 'h', 'hpp', 'ts', 'tsx', 'vue', 'go', 'rust', 'swift', 'kotlin', 'ruby', 'scala', 'dart', 'elixir', 'erlang', 'haskell', 'ocaml', 'pascal', 'perl', 'php', 'python', 'r', 'ruby', 'rust', 'scala', 'swift', 'typescript'];
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('不支持编辑此类型的文件');
    }

    if (strlen($content) > 6 * 1024) {
        throw new Exception('文件内容超过6KB限制，不支持在线编辑');
    }

    $currentVersion = md5_file($fullPath);
    if ($currentVersion !== $version) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => '文件已被其他用户修改，请刷新后重试',
            'current_version' => $currentVersion,
            'your_version' => $version
        ]);
        exit;
    }

    $tempFile = $fullPath . '.tmp';
    if (file_put_contents($tempFile, $content) === false) {
        error_log("Failed to write to temp file: $tempFile");
        throw new Exception('保存文件失败');
    }

    if (!rename($tempFile, $fullPath)) {
        error_log("Failed to rename temp file to: $fullPath");
        unlink($tempFile);
        throw new Exception('保存文件失败');
    }

    $newVersion = md5_file($fullPath);
    $fileInfo = [
        'name' => basename($fullPath),
        'path' => $filePath,
        'size' => filesize($fullPath),
        'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
        'version' => $newVersion
    ];

    echo json_encode([
        'success' => true,
        'message' => '文件保存成功',
        'file' => $fileInfo
    ]);

} catch (Exception $e) {
    error_log('Edit file error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code($e->getCode() === 0 ? 400 : $e->getCode());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 