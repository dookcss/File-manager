<?php
require_once 'functions.php';

$file = $_GET['file'] ?? '';
$shareCode = $_GET['share'] ?? '';


if ($shareCode) {
    $db = Database::getInstance();
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
        die('分享链接无效或已过期');
    }

    $userPath = USER_SPACE_PATH . '/' . $share['username'];
    $file = $share['file_path'];
} else {
    requireLogin();
    $userPath = getUserStoragePath();
}

$path = $userPath . '/' . $file;

if (!file_exists($path)) {
    die('文件不存在');
}

if (strpos(realpath($path), realpath($userPath)) !== 0) {
    die('无权访问此文件');
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($extension, VIDEO_EXTENSIONS)) {
    die('不支持的视频格式');
}

$filesize = formatFileSize(filesize($path));
$mime = mime_content_type($path);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>视频预览 - <?php echo htmlspecialchars($file); ?></title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/plyr/3.7.8/plyr.min.css" rel="stylesheet">
    <style>
        .video-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 10px;
        }
        
        .video-info {
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin-top: 10px !important;
            }
            
            .video-container {
                padding: 0;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            video {
                width: 100%;
                height: auto;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="video-container">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4"><?php echo htmlspecialchars($file); ?></h5>
                    
                    <?php if (in_array($extension, ['mp4', 'webm', 'ogv'])): ?>
                    <video id="player" playsinline controls
                           data-poster="<?php echo $extension === 'mp4' ? $path . '#t=0.5' : ''; ?>">
                        <source src="<?php echo $shareCode ? "download.php?file=" . urlencode($file) . "&share=" . urlencode($shareCode) : "download.php?file=" . urlencode($file); ?>" 
                                type="<?php echo $mime; ?>">
                    </video>
                    <?php else: ?>
                    <div class="alert alert-info">
                        此视频格式（<?php echo $extension; ?>）不支持在线播放，请下载后观看。
                    </div>
                    <?php endif; ?>
                    <div class="video-info">
                        <p><strong>文件名：</strong><?php echo htmlspecialchars($file); ?></p>
                        <p><strong>文件大小：</strong><?php echo $filesize; ?></p>
                        <p><strong>文件类型：</strong><?php echo $mime; ?></p>
                    </div>

                    <div class="mt-3">
                        <a href="<?php echo $shareCode ? "download.php?file=" . urlencode($file) . "&share=" . urlencode($shareCode) : "download.php?file=" . urlencode($file); ?>" 
                           class="btn btn-primary" download>
                            下载视频
                        </a>
                        <?php if (!$shareCode): ?>
                        <a href="index.php" class="btn btn-secondary">返回列表</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/plyr/3.7.8/plyr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const player = new Plyr('#player', {
                controls: [
                    'play-large', 'play', 'progress', 'current-time', 'mute', 
                    'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'
                ],
                settings: ['captions', 'quality', 'speed', 'loop'],
                quality: {
                    default: 576,
                    options: [4320, 2880, 2160, 1440, 1080, 720, 576, 480, 360, 240]
                }
            });
        });
    </script>
</body>
</html> 