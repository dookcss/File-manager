<?php
require_once 'functions.php';
requireAdmin();

$db = Database::getInstance();
$message = '';

if (isset($_POST['generate'])) {
    $expiresIn = intval($_POST['expires_in'] ?? 7);
    if ($expiresIn < 1 || $expiresIn > 365) {
        $message = '有效期必须在1-365天之间';
    } else {
        $code = $db->createInviteCode($_SESSION['user_id'], $expiresIn);
        if ($code) {
            $message = "邀请码生成成功：$code";
        } else {
            $message = '邀请码生成失败';
        }
    }
}

if (isset($_POST['delete'])) {
    $codeId = $_POST['code_id'] ?? 0;
    if ($db->deleteInviteCode($codeId, $_SESSION['user_id'])) {
        $message = '邀请码删除成功';
    } else {
        $message = '邀请码删除失败';
    }
}

$inviteCodes = $db->getInviteCodes($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邀请码管理 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="mb-4">
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回管理页面
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">生成邀请码</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-auto">
                        <label class="form-label">有效期（天）</label>
                        <input type="number" name="expires_in" class="form-control" 
                               value="7" min="1" max="365" required>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="generate" class="btn btn-primary d-block">
                            生成邀请码
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">邀请码列表</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>邀请码</th>
                                <th>创建时间</th>
                                <th>过期时间</th>
                                <th>使用状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inviteCodes as $code): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($code['code']); ?></td>
                                <td><?php echo $code['created_at']; ?></td>
                                <td><?php echo $code['expires_at']; ?></td>
                                <td>
                                    <?php if ($code['used_by']): ?>
                                        已使用 (<?php echo htmlspecialchars($code['used_by_username']); ?>)
                                    <?php else: ?>
                                        <?php if (strtotime($code['expires_at']) < time()): ?>
                                            已过期
                                        <?php else: ?>
                                            未使用
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$code['used_by'] && strtotime($code['expires_at']) > time()): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="code_id" value="<?php echo $code['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm"
                                                onclick="return confirm('确定要删除这个邀请码吗？')">
                                            删除
                                        </button>
                                    </form>
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

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html> 