<?php
require_once 'functions.php';
requireAdmin();

$db = Database::getInstance();
$userManager = new UserManager();
$message = '';
$error = '';
$userData = null;
$newPassword = null;

// 处理查询请求
if (isset($_POST['query'])) {
    $inviteCode = $_POST['invite_code'] ?? '';
    $userData = $db->getUserByInviteCode($inviteCode);
    if (!$userData) {
        $error = '未找到使用此邀请码注册的用户';
    }
}


if (isset($_POST['reset']) && isset($_POST['user_id'])) {
    try {
        $userId = intval($_POST['user_id']);
    
        if (!isAdmin()) {
            throw new Exception('没有权限执行此操作');
        }
        
        $newPassword = $userManager->resetPassword($userId);
        if ($newPassword) {
            $message = "密码重置成功！新密码为：{$newPassword}";
            error_log("管理员 {$_SESSION['username']} 重置了用户 ID:{$userId} 的密码");
        } else {
            throw new Exception('密码重置失败');
        }
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
    <title>密码管理 - 文件管理系统</title>
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
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">查询用户信息</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-auto">
                        <label class="form-label">邀请码</label>
                        <input type="text" name="invite_code" class="form-control" required>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="query" class="btn btn-primary d-block">
                            查询用户
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($userData): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">用户信息</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>用户名</th>
                            <td><?php echo htmlspecialchars($userData['username']); ?></td>
                        </tr>
                        <tr>
                            <th>邮箱</th>
                            <td><?php echo htmlspecialchars($userData['email']); ?></td>
                        </tr>
                        <tr>
                            <th>注册时间</th>
                            <td><?php echo $userData['created_at']; ?></td>
                        </tr>
                        <tr>
                            <th>最后登录</th>
                            <td><?php echo $userData['last_login'] ?? '从未登录'; ?></td>
                        </tr>
                        <tr>
                            <th>操作</th>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $userData['id']; ?>">
                                    <button type="submit" name="reset" class="btn btn-warning"
                                            onclick="return confirm('确定要重置该用户的密码吗？')">
                                        重置密码
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html> 