<?php
require_once 'functions.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['quota'])) {
    echo json_encode(['success' => false, 'message' => '参数错误']);
    exit;
}

$result = setDefaultUserQuota($data['quota']);
echo json_encode($result); 