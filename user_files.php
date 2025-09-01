<?php
require_once 'functions.php';
requireAdmin();

$username = $_GET['user'] ?? '';
if (!$username) {
    header('Location: profile.php');
    exit;
}


$userPath = USER_SPACE_PATH . '/' . $username;
$files = is_dir($userPath) ? getFileList($userPath) : [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?> 的文件 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo htmlspecialchars($username); ?> 的文件</h2>
            <a href="profile.php" class="btn btn-primary">返回</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>大小</th>
                                <th>类型</th>
                                <th>修改时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['name']); ?></td>
                                <td><?php echo formatFileSize($file['size']); ?></td>
                                <td><?php echo $file['type']; ?></td>
                                <td><?php echo $file['modified']; ?></td>
                                <td>
                                    <a href="download.php?file=<?php echo urlencode($username . '/' . $file['name']); ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php if ($previewUrl = getPreviewUrl($file)): ?>
                                    <a href="<?php echo $previewUrl; ?>" 
                                       class="btn btn-sm btn-info" 
                                       target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 