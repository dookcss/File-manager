<?php
require_once 'functions.php';
requireLogin();

$response = ['success' => false, 'message' => ''];

try {
    $folderName = $_POST['foldername'] ?? '';
    $currentDir = $_POST['current_dir'] ?? '';
    
    if (empty($folderName)) {
        throw new Exception('文件夹名不能为空');
    }

    $fullPath = USER_SPACE_PATH . '/' . $_SESSION['username'];
    if ($currentDir) {
        $fullPath .= '/' . trim($currentDir, '/');
    }
    $fullPath .= '/' . $folderName;

    if (file_exists($fullPath)) {
        throw new Exception('文件夹已存在');
    }

    if (mkdir($fullPath, 0755, true)) {
        $response['success'] = true;
        $response['message'] = '文件夹创建成功';
    } else {
        throw new Exception('文件夹创建失败');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 