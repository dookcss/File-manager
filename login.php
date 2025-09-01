<?php
require_once 'functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d6efd20 0%, #0dcaf020 100%);
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .system-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
        }
        .btn-primary {
            padding: 12px;
            border-radius: 10px;
            font-weight: 500;
        }
        .features {
            margin-top: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-8 col-lg-6">
                    <div class="card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-file-archive system-icon"></i>
                                <h3 class="card-title">文件管理系统</h3>
                                <p class="text-muted">安全可靠的文件存储与管理平台</p>
                            </div>
                            
                            <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user text-primary"></i> 用户名
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="请输入用户名" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock text-primary"></i> 密码
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="请输入密码" required>
                                    <div class="text-end mt-1">
                                        <a href="forgot-password.php" class="text-decoration-none small">忘记密码？</a>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> 登录
                                </button>
                                
                                <div class="text-center mt-3">
                                    还没有账号？<a href="register.php" class="text-decoration-none">立即注册</a>
                                </div>

                                <div class="features">
                                    <div class="row text-center text-muted">
                                        <div class="col-4">
                                            <i class="fas fa-shield-alt"></i>
                                            <div>安全存储</div>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-sync"></i>
                                            <div>实时同步</div>
                                        </div>
                                        <div class="col-4">
                                            <i class="fas fa-share-alt"></i>
                                            <div>便捷分享</div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 