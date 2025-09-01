<?php
require_once 'functions.php';
requireLogin();

$response = ['success' => false, 'message' => ''];

try {
    $fileName = $_POST['filename'] ?? '';
    $currentDir = $_POST['current_dir'] ?? '';
    
    if (empty($fileName)) {
        throw new Exception('文件名不能为空');
    }

    $fullPath = USER_SPACE_PATH . '/' . $_SESSION['username'];
    if ($currentDir) {
        $fullPath .= '/' . trim($currentDir, '/');
    }
    $fullPath .= '/' . $fileName;

    if (file_exists($fullPath)) {
        throw new Exception('文件已存在');
    }

    if (file_put_contents($fullPath, '') !== false) {
        $response['success'] = true;
        $response['message'] = '文件创建成功';
    } else {
        throw new Exception('文件创建失败');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 