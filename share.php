<?php
require_once 'functions.php';
requireLogin();

$db = Database::getInstance();
$message = '';
$error = '';


if (isset($_POST['duration'])) {
    $filePath = $_POST['file_path'] ?? '';
    $duration = $_POST['duration'] ?? '7';
    
    try {

        $fullPath = USER_SPACE_PATH . '/' . $_SESSION['username'] . '/' . $filePath;
        if (!file_exists($fullPath)) {
            throw new Exception('文件不存在');
        }


        if ($duration === 'permanent') {

            $shareCode = md5($_SESSION['username'] . '/' . $filePath);
        } else {

            $shareCode = bin2hex(random_bytes(16));
        }
        

        $expiresAt = null;
        if ($duration !== 'permanent') {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
        }

        if ($duration === 'permanent') {
            $stmt = $db->getConnection()->prepare(
                "SELECT id FROM shares 
                 WHERE user_id = ? AND file_path = ? AND expires_at IS NULL"
            );
            $stmt->execute([$_SESSION['user_id'], $filePath]);
            if ($stmt->fetch()) {
                throw new Exception('此文件已存在永久分享');
            }
        }
        

        $stmt = $db->getConnection()->prepare(
            "INSERT INTO shares (user_id, file_path, share_code, expires_at) 
             VALUES (?, ?, ?, ?)"
        );
        
        if ($stmt->execute([$_SESSION['user_id'], $filePath, $shareCode, $expiresAt])) {
            $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                       $_SERVER['HTTP_HOST'] . 
                       dirname($_SERVER['PHP_SELF']) . 
                       "/s.php?c=" . $shareCode;
            $message = "分享创建成功！分享链接：<br><a href='$shareUrl'>$shareUrl</a>";
        } else {
            throw new Exception('创建分享失败');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}


if (isset($_POST['cancel_share'])) {
    $shareId = $_POST['share_id'] ?? 0;
    $stmt = $db->getConnection()->prepare(
        "DELETE FROM shares WHERE id = ? AND user_id = ?"
    );
    if ($stmt->execute([$shareId, $_SESSION['user_id']])) {
        $message = '分享已取消';
    } else {
        $error = '取消分享失败';
    }
}

$stmt = $db->getConnection()->prepare(
    "SELECT s.*, 
            CASE 
                WHEN s.expires_at IS NULL THEN '永久有效'
                WHEN datetime('now') > s.expires_at THEN '已过期'
                ELSE s.expires_at 
            END as status
     FROM shares s 
     WHERE s.user_id = ? 
     ORDER BY s.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$shares = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件分享管理 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="mb-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回主页
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">我的分享</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>分享链接</th>
                                <th>创建时间</th>
                                <th>有效期至</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shares as $share): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(basename($share['file_path'])); ?></td>
                                <td>
                                    <?php 
                                    $shareUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                                               $_SERVER['HTTP_HOST'] . 
                                               dirname($_SERVER['PHP_SELF']) . 
                                               "/s.php?c=" . $share['share_code'];
                                    ?>
                                    <a href="<?php echo $shareUrl; ?>" target="_blank"><?php echo $shareUrl; ?></a>
                                </td>
                                <td><?php echo $share['created_at']; ?></td>
                                <td><?php echo $share['status']; ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="share_id" value="<?php echo $share['id']; ?>">
                                        <button type="submit" name="cancel_share" class="btn btn-danger btn-sm"
                                                onclick="return confirm('确定要取消这个分享吗？')">
                                            取消分享
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html> 