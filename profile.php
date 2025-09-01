<?php
require_once 'functions.php';
requireLogin();

$message = '';
$user = getCurrentUser();


if (isset($_POST['generate_api_key'])) {
    $db = Database::getInstance()->getConnection();
    $newApiKey = generateApiKey();
    
    $stmt = $db->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    if ($stmt->execute([$newApiKey, $_SESSION['user_id']])) {
        $message = 'API Key 生成成功';
        $user['api_key'] = $newApiKey;
    } else {
        $message = 'API Key 生成失败';
    }
}

if (isset($_GET['logout'])) {
    logout();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人设置 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin-top: 10px !important;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .input-group {
                margin-bottom: 10px;
            }
            
            .progress {
                height: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>个人设置</h2>
            <div>
                <a href="index.php" class="btn btn-outline-primary me-2">返回主页</a>
                <a href="?logout=1" class="btn btn-outline-danger">退出登录</a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- 用户信息 -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">基本信息</h5>
                <div class="mb-3">
                    <p><strong>用户名：</strong><?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>邮箱：</strong><?php echo htmlspecialchars($user['email'] ?? '未设置'); ?></p>
                    <p><strong>注册时间：</strong><?php echo $user['created_at']; ?></p>
                    <p><strong>最后登录：</strong><?php echo $user['last_login'] ?? '从未登录'; ?></p>
                </div>
            </div>
        </div>

        <!-- API密钥管理 -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">API 密钥管理</h5>
                <?php if ($user['api_key']): ?>
                    <div class="mb-3">
                        <label class="form-label">当前 API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['api_key']); ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey(this)">复制</button>
                        </div>
                    </div>
                <?php endif; ?>
                <form method="post" onsubmit="return confirm('生成新的 API Key 将使旧的失效，确定要继续吗？')">
                    <button type="submit" name="generate_api_key" class="btn btn-primary">
                        <?php echo $user['api_key'] ? '重新生成 API Key' : '生成 API Key'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- 存储空间信息 -->
        <div class="card mb-4">
            <div class="card-body">
                <?php
                $used = getUserStorageUsed();
                $total = getUserQuota();
                $usedPercent = ($used / $total) * 100;
                ?>
                <h5 class="card-title">存储空间</h5>
                <div class="progress mb-3">
                    <div class="progress-bar <?php echo $usedPercent > 90 ? 'bg-danger' : ($usedPercent > 70 ? 'bg-warning' : 'bg-success'); ?>" 
                         role="progressbar" 
                         style="width: <?php echo $usedPercent; ?>%" 
                         aria-valuenow="<?php echo $usedPercent; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?php echo round($usedPercent, 1); ?>%
                    </div>
                </div>
                <p class="mb-0">
                    已使用：<?php echo formatFileSize($used); ?> / 
                    总容量：<?php echo formatFileSize($total); ?>
                </p>
            </div>
        </div>

        <!-- 如果是管理员，添加管理员功能区 -->
        <?php if (isAdmin()): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">管理员功能</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>角色</th>
                                <th>存储使用</th>
                                <th>存储限制</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (getAllUsers() as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email'] ?? '未设置'); ?></td>
                                <td><?php echo $u['is_admin'] ? '管理员' : '普通用户'; ?></td>
                                <td><?php echo formatFileSize($u['storage_used']); ?></td>
                                <td>
                                    <form class="quota-form" data-user-id="<?php echo $u['id']; ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" 
                                                   name="quota" 
                                                   value="<?php echo $u['storage_quota'] / (1024 * 1024 * 1024); ?>"
                                                   min="1" step="1">
                                            <span class="input-group-text">GB</span>
                                            <button type="submit" class="btn btn-primary">保存</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <a href="user_files.php?user=<?php echo urlencode($u['username']); ?>" 
                                       class="btn btn-sm btn-info">
                                        查看文件
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 账号管理 -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">账号管理</h5>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">
                    注销账号
                </button>
            </div>
        </div>
    </div>

    <!-- 添加确认对话框 -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认注销账号</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>注销账号将删除所有文件且无法恢复，确定要继续吗？</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" onclick="deleteAccount()">确认注销</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyApiKey(button) {
        var input = button.parentElement.querySelector('input');
        input.select();
        document.execCommand('copy');
        button.textContent = '已复制';
        setTimeout(() => button.textContent = '复制', 2000);
    }

    function confirmDeleteAccount() {
        new bootstrap.Modal(document.getElementById('deleteAccountModal')).show();
    }

    async function deleteAccount() {
        try {
            const response = await fetch('delete_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: <?php echo $_SESSION['user_id']; ?>
                })
            });

            const result = await response.json();
            
            if (result.success) {
                alert('账号注销成功');
                window.location.href = 'login.php';
            } else {
                alert('注销失败：' + result.message);
            }
        } catch (error) {
            alert('操作失败：' + error.message);
        }
    }
    </script>
    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html> 