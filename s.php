<?php
require_once 'functions.php';

$db = Database::getInstance();
$error = '';
$fileInfo = null;

$shareCode = $_GET['c'] ?? '';

if ($shareCode) {
    try {

        $stmt = $db->getConnection()->prepare(
            "SELECT s.*, u.username 
             FROM shares s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.share_code = ? 
             AND (s.expires_at IS NULL OR datetime('now') <= s.expires_at)"
        );
        $stmt->execute([$shareCode]);
        $share = $stmt->fetch();

        if (!$share) {
            throw new Exception('分享链接无效或已过期');
        }

        $filePath = USER_SPACE_PATH . '/' . $share['username'] . '/' . $share['file_path'];
        if (!file_exists($filePath)) {
            throw new Exception('文件不存在');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = mime_content_type($filePath);

        if (in_array($extension, ['txt', 'csv', 'md', 'php', 'js', 'css', 'html', 'htm', 'xml', 'json', 'yaml', 'yml', 'ini', 'log', 'conf', 'config', 'lua'])) {

            header('Content-Type: text/plain; charset=utf-8');
            readfile($filePath);
            exit;
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'])) {

            header("Content-Type: $mime");
            readfile($filePath);
            exit;
        } elseif ($extension === 'pdf') {
            header('Content-Type: application/pdf');
            readfile($filePath);
            exit;
        } elseif (in_array($extension, VIDEO_EXTENSIONS)) {
            header("Location: video.php?file=" . urlencode($share['file_path']) . "&share=" . urlencode($shareCode));
            exit;
        }

        $fileInfo = [
            'name' => basename($share['file_path']),
            'size' => filesize($filePath),
            'type' => $mime,
            'path' => $share['file_path']
        ];

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看分享 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($fileInfo): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo htmlspecialchars($fileInfo['name']); ?></h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <p><strong>文件类型：</strong><?php echo $fileInfo['type']; ?></p>
                    <p><strong>文件大小：</strong><?php echo formatFileSize($fileInfo['size']); ?></p>
                </div>
                <div class="alert alert-info">
                    此类型的文件不支持在线预览，请下载后查看
                </div>
                <a href="download.php?file=<?php echo urlencode($fileInfo['path']); ?>&share=<?php echo urlencode($shareCode); ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> 下载文件
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 