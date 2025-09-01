<?php
require_once '../functions.php';
header('Content-Type: application/json');

$headers = getallheaders();
$apiKey = null;

foreach ($headers as $name => $value) {
    if (strtolower($name) === 'x-api-key') {
        $apiKey = $value;
        break;
    }
}

if (!$apiKey) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
}

if (!$apiKey) {
    error_log('API Key not found in headers: ' . print_r($headers, true));
    error_log('$_SERVER: ' . print_r($_SERVER, true));
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'message' => '缺少API密钥'
    ]));
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, username, is_admin FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'message' => '无效的API密钥'
        ]));
    }
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => '服务器错误'
    ]));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }
        
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            die(json_encode(['error' => 'No file uploaded']));
        }
        
        if ($_FILES['file']['size'] > MAX_UPLOAD_SIZE) {
            http_response_code(413);
            die(json_encode([
                'success' => false,
                'message' => '文件大小超过限制',
                'max_size' => formatFileSize(MAX_UPLOAD_SIZE)
            ]));
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, storage_quota FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $apiUser = $stmt->fetch();
        
        if (!$apiUser) {
            http_response_code(401);
            die(json_encode([
                'success' => false,
                'message' => '无效的API密钥'
            ]));
        }

        $currentUsed = getUserStorageUsed($apiUser['username']);
        $quota = $apiUser['storage_quota'];
        if (($currentUsed + $_FILES['file']['size']) > $quota) {
            http_response_code(507);
            die(json_encode([
                'success' => false,
                'message' => '存储空间不足',
                'used' => formatFileSize($currentUsed),
                'quota' => formatFileSize($quota),
                'required' => formatFileSize($_FILES['file']['size'])
            ]));
        }
        
        $conflictAction = $_POST['conflict_action'] ?? 'skip';
        if (!in_array($conflictAction, ['skip', 'rename', 'overwrite'])) {
            $conflictAction = 'skip';
        }
        
        $userPath = USER_SPACE_PATH . '/' . $apiUser['username'];
        if (!file_exists($userPath)) {
            mkdir($userPath, 0755, true);
        }
        
        $file = $_FILES['file'];
        $filename = basename($file['name']);
        $targetPath = $userPath . '/' . $filename;
        
        if (file_exists($targetPath)) {
            if ($conflictAction === 'skip') {
                http_response_code(409);
                die(json_encode([
                    'success' => false,
                    'message' => '文件已存在',
                    'conflict' => true,
                    'filename' => $filename,
                    'suggestions' => [
                        '使用 conflict_action=rename 自动重命名',
                        '使用 conflict_action=overwrite 覆盖已有文件',
                        '更改文件名后重试'
                    ]
                ]));
            } elseif ($conflictAction === 'rename') {
                $i = 1;
                $pathInfo = pathinfo($filename);
                do {
                    $newName = $pathInfo['filename'] . "($i)." . $pathInfo['extension'];
                    $targetPath = $userPath . '/' . $newName;
                    $i++;
                } while (file_exists($targetPath));
                $filename = $newName;
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            echo json_encode([
                'success' => true,
                'message' => '文件上传成功',
                'filename' => $filename
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '文件上传失败'
            ]);
        }
        break;

    case 'download':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }
        
        if (!isset($_GET['file'])) {
            http_response_code(400);
            die(json_encode(['error' => 'File parameter is required']));
        }
        
        $filePath = $_GET['file'];
        $filePath = str_replace(['..', '\\'], '', $filePath);
        $fullPath = USER_SPACE_PATH . '/' . $user['username'] . '/' . $filePath;
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            die(json_encode(['error' => 'File not found']));
        }
        
        if (strpos(realpath($fullPath), realpath(USER_SPACE_PATH . '/' . $user['username'])) !== 0) {
            http_response_code(403);
            die(json_encode(['error' => 'Access denied']));
        }
        
        header('Content-Type: ' . mime_content_type($fullPath));
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        
        readfile($fullPath);
        exit;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }

        if (!isset($_GET['file'])) {
            http_response_code(400);
            die(json_encode(['error' => 'File parameter is required']));
        }

        $filePath = $_GET['file'];
        $filePath = str_replace(['..', '\\'], '', $filePath);
        $fullPath = USER_SPACE_PATH . '/' . $user['username'] . '/' . $filePath;

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            die(json_encode([
                'success' => false,
                'message' => '文件不存在'
            ]));
        }

        if (strpos(realpath($fullPath), realpath(USER_SPACE_PATH . '/' . $user['username'])) !== 0) {
            http_response_code(403);
            die(json_encode(['error' => 'Access denied']));
        }

        if (unlink($fullPath)) {
            echo json_encode([
                'success' => true,
                'message' => '文件删除成功'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '文件删除失败'
            ]);
        }
        break;

    case 'list':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }

        $path = $_GET['path'] ?? '';
        $path = str_replace(['..', '\\'], '', $path);
        $userPath = USER_SPACE_PATH . '/' . $user['username'] . ($path ? '/' . $path : '');
        
        if (!file_exists($userPath) || !is_dir($userPath)) {
            http_response_code(404);
            die(json_encode([
                'success' => false,
                'message' => '目录不存在'
            ]));
        }

        if (strpos(realpath($userPath), realpath(USER_SPACE_PATH . '/' . $user['username'])) !== 0) {
            http_response_code(403);
            die(json_encode(['error' => 'Access denied']));
        }

        $files = [];
        $items = scandir($userPath);
        
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                $itemPath = $userPath . '/' . $item;
                $relativePath = $path ? ($path . '/' . $item) : $item;
                
                    $files[] = [
                    'name' => $item,
                    'path' => $relativePath,
                    'size' => filesize($itemPath),
                    'type' => is_dir($itemPath) ? 'dir' : 'file',
                    'modified' => date('Y-m-d H:i:s', filemtime($itemPath))
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'path' => $path,
            'files' => $files
        ]);
        break;

    case 'share':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }

        $filePath = $_POST['file'] ?? '';
        $duration = $_POST['duration'] ?? '7';

        if (!$filePath) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'message' => '文件路径不能为空'
            ]));
        }

        if ($duration !== 'permanent' && (!is_numeric($duration) || $duration < 1 || $duration > 365)) {
            http_response_code(400);
            die(json_encode([
                'success' => false,
                'message' => '分享时长必须是1-365天的数字，或permanent表示永久'
            ]));
        }

        try {
            $fullPath = USER_SPACE_PATH . '/' . $user['username'] . '/' . $filePath;
            if (!file_exists($fullPath) || !is_file($fullPath)) {
                throw new Exception('文件不存在');
            }

            if ($duration === 'permanent') {
                $shareCode = md5($user['username'] . '/' . $filePath);

                $stmt = $db->getConnection()->prepare(
                    "SELECT id, share_code FROM shares 
                     WHERE user_id = ? AND file_path = ? AND expires_at IS NULL"
                );
                $stmt->execute([$user['id'], $filePath]);
                $existingShare = $stmt->fetch();
                
                if ($existingShare) {
                    $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                               $_SERVER['HTTP_HOST'] . 
                               dirname(dirname($_SERVER['PHP_SELF'])) . 
                               "/s.php?c=" . $existingShare['share_code'];
                    
                    echo json_encode([
                        'success' => true,
                        'message' => '已存在永久分享',
                        'share_code' => $existingShare['share_code'],
                        'share_url' => $shareUrl,
                        'expires_at' => null
                    ]);
                    exit;
                }
            } else {
                $shareCode = bin2hex(random_bytes(16));
            }

            $expiresAt = null;
            if ($duration !== 'permanent') {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
            }

            $stmt = $db->getConnection()->prepare(
                "INSERT INTO shares (user_id, file_path, share_code, expires_at) 
                 VALUES (?, ?, ?, ?)"
            );

            if ($stmt->execute([$user['id'], $filePath, $shareCode, $expiresAt])) {
                $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                           $_SERVER['HTTP_HOST'] . 
                           dirname(dirname($_SERVER['PHP_SELF'])) . 
                           "/s.php?c=" . $shareCode;

                echo json_encode([
                    'success' => true,
                    'message' => '分享创建成功',
                    'share_code' => $shareCode,
                    'share_url' => $shareUrl,
                    'expires_at' => $expiresAt
                ]);
            } else {
                throw new Exception('创建分享失败');
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'list_shares':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }

        try {
            $file = $_GET['file'] ?? '';
            
            $sql = "SELECT s.*, 
                          CASE 
                              WHEN s.expires_at IS NULL THEN '永久有效'
                              WHEN datetime('now') > s.expires_at THEN '已过期'
                              ELSE s.expires_at 
                          END as status
                   FROM shares s 
                   WHERE s.user_id = ?";
            $params = [$user['id']];
            
            if ($file) {
                $sql .= " AND s.file_path = ?";
                $params[] = $file;
            }
            
            $sql .= " ORDER BY s.created_at DESC";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute($params);
            $shares = $stmt->fetchAll();
            
            $result = [];
            foreach ($shares as $share) {
                $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                           $_SERVER['HTTP_HOST'] . 
                           dirname(dirname($_SERVER['PHP_SELF'])) . 
                           "/s.php?c=" . $share['share_code'];
                
                $result[] = [
                    'file_name' => basename($share['file_path']),
                    'file_path' => $share['file_path'],
                    'share_code' => $share['share_code'],
                    'share_url' => $shareUrl,
                    'created_at' => $share['created_at'],
                    'expires_at' => $share['expires_at'],
                    'status' => $share['status'],
                    'is_permanent' => $share['expires_at'] === null
                ];
            }
            
            echo json_encode([
                'success' => true,
                'total' => count($result),
                'shares' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'stop_share':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }

        try {
            $filePath = $_POST['file'] ?? '';
            $shareCode = $_POST['share_code'] ?? '';

            if (!$filePath && !$shareCode) {
                http_response_code(400);
                die(json_encode([
                    'success' => false,
                    'message' => '文件路径或分享码至少需要提供一个'
                ]));
            }

            $sql = "SELECT * FROM shares WHERE user_id = ?";
            $params = [$user['id']];
            
            if ($filePath) {
                $sql .= " AND file_path = ?";
                $params[] = $filePath;
            }
            
            if ($shareCode) {
                $sql .= " AND share_code = ?";
                $params[] = $shareCode;
            }
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute($params);
            $shares = $stmt->fetchAll();
            
            if (empty($shares)) {
                http_response_code(404);
                die(json_encode([
                    'success' => false,
                    'message' => '未找到匹配的分享记录'
                ]));
            }

            $deleteSql = "DELETE FROM shares WHERE user_id = ?";
            $deleteParams = [$user['id']];
            
            if ($filePath) {
                $deleteSql .= " AND file_path = ?";
                $deleteParams[] = $filePath;
            }
            
            if ($shareCode) {
                $deleteSql .= " AND share_code = ?";
                $deleteParams[] = $shareCode;
            }
            
            $stmt = $db->getConnection()->prepare($deleteSql);
            if ($stmt->execute($deleteParams)) {
                $deletedShares = array_map(function($share) {
                    $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                               $_SERVER['HTTP_HOST'] . 
                               dirname(dirname($_SERVER['PHP_SELF'])) . 
                               "/s.php?c=" . $share['share_code'];
                    
                    return [
                        'file_name' => basename($share['file_path']),
                        'file_path' => $share['file_path'],
                        'share_code' => $share['share_code'],
                        'share_url' => $shareUrl,
                        'created_at' => $share['created_at'],
                        'expires_at' => $share['expires_at']
                    ];
                }, $shares);

                echo json_encode([
                    'success' => true,
                    'message' => '分享已停止',
                    'total_deleted' => count($deletedShares),
                    'deleted_shares' => $deletedShares
                ]);
            } else {
                throw new Exception('删除分享记录失败');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'create_invite_codes':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => 'Method not allowed']));
        }

        try {
            $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
            
            if ($count < 1 || $count > 100) {
                http_response_code(400);
                die(json_encode([
                    'success' => false,
                    'message' => '生成数量必须在1-100之间'
                ]));
            }

            if (!$user['is_admin']) {
                error_log("User {$user['username']} (ID: {$user['id']}) attempted to create invite codes without admin privileges");
                http_response_code(403);
                die(json_encode([
                    'success' => false,
                    'message' => '只有管理员可以生成邀请码'
                ]));
            }

            $inviteCodes = [];
            try {
                $checkTable = $db->getConnection()->query("SELECT 1 FROM invite_codes LIMIT 1");
                if (!$checkTable) {
                    throw new Exception("invite_codes表不存在");
                }
            } catch (Exception $e) {
                error_log("Database error checking invite_codes table: " . $e->getMessage());
                throw new Exception("数据库错误：" . $e->getMessage());
            }

            $stmt = $db->getConnection()->prepare(
                "INSERT INTO invite_codes (code, created_by, created_at, expires_at) 
                 VALUES (?, ?, datetime('now'), datetime('now', '+7 days'))"
            );

            $db->getConnection()->beginTransaction();

            try {
                for ($i = 0; $i < $count; $i++) {
                    do {
                        $code = strtoupper(bin2hex(random_bytes(16)));
                        
                        $checkStmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM invite_codes WHERE code = ?");
                        $checkStmt->execute([$code]);
                        $exists = $checkStmt->fetchColumn() > 0;
                    } while ($exists);

                    try {
                        if (!$stmt->execute([$code, $user['id']])) {
                            error_log("Failed to insert invite code: " . json_encode($stmt->errorInfo()));
                            throw new Exception("插入邀请码失败");
                        }
                        
                        $inviteCodes[] = [
                            'code' => $code,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    } catch (Exception $e) {
                        error_log("Error inserting invite code: " . $e->getMessage());
                        throw $e;
                    }
                }

                $db->getConnection()->commit();

                echo json_encode([
                    'success' => true,
                    'message' => '邀请码生成成功',
                    'total' => count($inviteCodes),
                    'invite_codes' => $inviteCodes
                ]);

            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                error_log("Transaction error: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Create invite codes error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'get_file':
        try {
            if (empty($_GET['file'])) {
                throw new Exception('缺少文件路径参数');
            }
            
            $filePath = $_GET['file'];
            $fileData = getFileContent($filePath, $apiKey);
            
            echo json_encode([
                'success' => true,
                'content' => $fileData['content'],
                'version' => $fileData['version']
            ]);
            exit;
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown action']);
        break;
} 