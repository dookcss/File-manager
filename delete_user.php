<?php
require_once 'functions.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$userManager = new UserManager();
$result = $userManager->deleteUser($data['user_id']);

if ($result) {
    echo json_encode(['success' => true, 'message' => '用户删除成功']);
} else {
    echo json_encode(['success' => false, 'message' => '用户删除失败']);
} 