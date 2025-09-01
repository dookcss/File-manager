<?php
require_once 'functions.php';

$db = Database::getInstance();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $inviteCode = $_POST['invite_code'] ?? '';

    if (!$username || !$password || !$confirm_password || !$email || !$inviteCode) {
        $error = '所有字段都必须填写';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } elseif (strlen($password) < 6) {
        $error = '密码长度必须至少为6个字符';
    } else {
        try {
            $validCode = $db->validateInviteCode($inviteCode);
            if (!$validCode) {
                throw new Exception('邀请码无效或已过期');
            }

            $userManager = new UserManager();
            $quota = getDefaultUserQuota();
            $userId = $userManager->createUser($username, $password, $email, false, $quota);
            
            if (!$userId) {
                throw new Exception('用户创建失败');
            }

            if (!$db->useInviteCode($inviteCode, $userId)) {
                $userManager->deleteUser($userId);
                throw new Exception('邀请码使用失败');
            }

            $success = '注册成功！请登录';
            header('Location: login.php');
            exit;
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = '用户名已存在，请选择其他用户名';
            } else {
                $error = '注册失败：' . $e->getMessage();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">注册账号</h3>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                            <br>
                            <a href="login.php" class="alert-link">点击这里登录</a>
                        </div>
                        <?php else: ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名 *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">密码 *</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="6">
                                <div class="form-text">密码长度至少6个字符</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认密码 *</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">邮箱</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="invite_code" class="form-label">邀请码</label>
                                <input type="text" class="form-control" id="invite_code" name="invite_code"
                                       value="<?php echo htmlspecialchars($_POST['invite_code'] ?? ''); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">注册</button>
                            <div class="text-center">
                                已有账号？<a href="login.php">立即登录</a>
                            </div>
                        </form>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 