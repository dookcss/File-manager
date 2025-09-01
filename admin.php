<?php
require_once 'functions.php';
requireAdmin();

$message = '';

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
    <title>管理面板 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>管理面板</h2>
            <div>
                <a href="index.php" class="btn btn-outline-primary me-2">返回主页</a>
                <a href="?logout=1" class="btn btn-outline-danger">退出登录</a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">用户管理</h5>
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
                                <td>
                                    <?php if ($u['is_admin']): ?>
                                        <span class="badge bg-danger">管理员</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">普通用户</span>
                                    <?php endif; ?>
                                </td>
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
                                    <div class="btn-group">
                                        <a href="user_files.php?user=<?php echo urlencode($u['username']); ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-folder-open"></i> 文件
                                        </a>
                                        <?php if (!$u['is_admin']): ?>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteUser(<?php echo $u['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">系统信息</h5>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>总用户数：</strong> <?php echo count(getAllUsers()); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>系统版本：</strong> 1.0.0</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>PHP 版本：</strong> <?php echo PHP_VERSION; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">系统设置</h5>
                <form class="row g-3" id="defaultQuotaForm">
                    <div class="col-md-6">
                        <label class="form-label">新用户默认存储配额</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="quota_value" 
                                   value="<?php 
                                        $quota = getDefaultUserQuota();
                                        echo $quota >= 1024 * 1024 * 1024 ? 
                                            $quota / (1024 * 1024 * 1024) : 
                                            $quota / (1024 * 1024); 
                                   ?>" 
                                   min="1" step="1">
                            <select class="form-select" name="quota_unit" style="max-width: 100px;">
                                <option value="GB" <?php echo $quota >= 1024 * 1024 * 1024 ? 'selected' : ''; ?>>GB</option>
                                <option value="MB" <?php echo $quota < 1024 * 1024 * 1024 ? 'selected' : ''; ?>>MB</option>
                            </select>
                            <button type="submit" class="btn btn-primary">保存</button>
                        </div>
                        <div class="form-text">
                            建议使用 GB 单位设置较大配额，使用 MB 单位设置较小配额
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="mb-3">
            <a href="invite_codes.php" class="btn btn-primary me-2">
                <i class="fas fa-ticket-alt"></i> 邀请码管理
            </a>
            <a href="reset_password.php" class="btn btn-warning">
                <i class="fas fa-key"></i> 密码管理
            </a>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.quota-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            const quotaGB = this.querySelector('input[name="quota"]').value;
            const quotaBytes = quotaGB * 1024 * 1024 * 1024;
            
            try {
                const response = await fetch('update_quota.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        quota: quotaBytes
                    })
                });
                
                const result = await response.json();
                alert(result.message);
                
                if (result.success) {
                    location.reload();
                }
            } catch (error) {
                alert('操作失败：' + error.message);
            }
        });
    });

    async function deleteUser(userId) {
        if (!confirm('确定要删除此用户吗？此操作不可恢复！')) {
            return;
        }
        
        try {
            const response = await fetch('delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            });
            
            const result = await response.json();
            alert(result.message);
            
            if (result.success) {
                location.reload();
            }
        } catch (error) {
            alert('删除失败：' + error.message);
        }
    }

    document.getElementById('defaultQuotaForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const quotaValue = this.querySelector('input[name="quota_value"]').value;
        const quotaUnit = this.querySelector('select[name="quota_unit"]').value;
        
        const quotaBytes = quotaUnit === 'GB' ? 
            quotaValue * 1024 * 1024 * 1024 : 
            quotaValue * 1024 * 1024;
        
        try {
            const response = await fetch('update_default_quota.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    quota: quotaBytes
                })
            });
            
            const result = await response.json();
            alert(result.message);
            
            if (result.success) {
                location.reload();
            }
        } catch (error) {
            alert('操作失败：' + error.message);
        }
    });
    </script>
</body>
</html> 