<?php
require_once 'functions.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

if ($userId != $_SESSION['user_id'] && !isAdmin()) {
    echo json_encode(['success' => false, 'message' => '无权限执行此操作']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
    }

    $db->beginTransaction();

    $userPath = USER_SPACE_PATH . '/' . $user['username'];
    if (file_exists($userPath)) {
        if (!deleteDirectory($userPath)) {
            throw new Exception('删除用户文件失败');
        }
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    if (!$stmt->execute([$userId])) {
        throw new Exception('删除用户记录失败');
    }

    $db->commit();

    if ($userId == $_SESSION['user_id']) {
        logout();
    }

    echo json_encode(['success' => true, 'message' => '账号注销成功']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('注销账号失败：' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '注销失败：' . $e->getMessage()]);
} 