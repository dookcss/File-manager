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
if (!in_array($extension, PREVIEW_EXTENSIONS)) {
    die('不支持预览此类型文件');
}

$mime = mime_content_type($path);

if (in_array($extension, [
    'txt', 'csv', 'md', 'markdown', 'php', 'js', 'css', 'html', 'htm', 'xml', 'json', 'yaml', 'yml',
    'ini', 'log', 'conf', 'config', 'sh', 'bash', 'sql', 'properties', 'env', 'gitignore',
    'c', 'cpp', 'h', 'hpp', 'java', 'py', 'rb', 'go', 'rust', 'ts', 'tsx', 'jsx', 'vue',
    'gradle', 'groovy', 'perl', 'php4', 'php5', 'phtml', 'swift', 'r', 'scala', 'kotlin', 'lua'
])) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>代码预览 - <?php echo htmlspecialchars(basename($file)); ?></title>
        <link href="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
        <link href="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css" rel="stylesheet">
        <style>
            body {
                margin: 0;
                padding: 20px;
                background-color: #1e1e1e;
                color: #d4d4d4;
            }
            pre {
                margin: 0;
                padding: 15px;
                border-radius: 5px;
                background: #2d2d2d;
                font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
                font-size: 14px;
                line-height: 1.5;
                tab-size: 4;
                white-space: pre-wrap;
                word-break: break-all;
                word-wrap: break-word;
                position: relative;
            }
            .file-info {
                margin-bottom: 15px;
                color: #888;
                font-size: 14px;
            }
            .download-btn {
                position: absolute;
                top: 10px;
                right: 10px;
                padding: 5px 10px;
                background: #0366d6;
                color: white;
                text-decoration: none;
                border-radius: 3px;
                font-size: 12px;
            }
            .download-btn:hover {
                background: #0256b9;
            }
        </style>
    </head>
    <body>
        <div class="file-info">
            文件名：<?php echo htmlspecialchars(basename($file)); ?> 
            (<?php echo formatFileSize(filesize($path)); ?>)
        </div>
        <pre><code class="language-<?php echo $extension; ?>"><?php echo htmlspecialchars(file_get_contents($path)); ?></code></pre>
        <a href="download.php?file=<?php echo urlencode($file); ?><?php echo $shareCode ? '&share=' . urlencode($shareCode) : ''; ?>" class="download-btn" download>下载文件</a>
        
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/prism.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-java.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-c.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-cpp.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-go.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-ruby.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-rust.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-typescript.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-jsx.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
        <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-lua.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif'])) {
    header("Content-Type: $mime");
    readfile($path);
    exit;
}

if ($extension === 'pdf') {
    header('Content-Type: application/pdf');
    readfile($path);
    exit;
}

if (in_array($extension, ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus'])) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>音频预览 - <?php echo htmlspecialchars(basename($file)); ?></title>
        <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                margin: 0;
                padding: 20px;
                background-color: #f8f9fa;
            }
            .audio-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .file-info {
                margin-bottom: 20px;
                color: #666;
            }
            audio {
                width: 100%;
                margin: 20px 0;
            }
            .btn-back {
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <a href="javascript:history.back()" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
            <div class="audio-container">
                <div class="file-info">
                    <h5><?php echo htmlspecialchars(basename($file)); ?></h5>
                    <p>文件大小：<?php echo formatFileSize(filesize($path)); ?></p>
                </div>
                <audio controls>
                    <source src="download.php?file=<?php echo urlencode($file); ?><?php echo $shareCode ? '&share=' . urlencode($shareCode) : ''; ?>" type="<?php echo $mime; ?>">
                    您的浏览器不支持音频播放，请<a href="download.php?file=<?php echo urlencode($file); ?><?php echo $shareCode ? '&share=' . urlencode($shareCode) : ''; ?>">下载</a>后播放
                </audio>
                <div class="text-end">
                    <a href="download.php?file=<?php echo urlencode($file); ?><?php echo $shareCode ? '&share=' . urlencode($shareCode) : ''; ?>" class="btn btn-primary" download>
                        <i class="fas fa-download"></i> 下载文件
                    </a>
                </div>
            </div>
        </div>
        <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </body>
    </html>
    <?php
    exit;
}


if (in_array($extension, VIDEO_EXTENSIONS)) {
    header("Location: video.php?file=" . urlencode($file) . ($shareCode ? "&share=" . urlencode($shareCode) : ""));
    exit;
}


if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'])) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>文档预览 - <?php echo htmlspecialchars(basename($file)); ?></title>
        <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                margin: 0;
                padding: 20px;
                background-color: #f8f9fa;
            }
            .doc-container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .preview-frame {
                width: 100%;
                height: 800px;
                border: none;
                margin: 20px 0;
            }
            .btn-back {
                margin-bottom: 20px;
            }
            .preview-error {
                padding: 20px;
                background: #fff3cd;
                border: 1px solid #ffeeba;
                border-radius: 4px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <a href="javascript:history.back()" class="btn btn-secondary btn-back">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
            <div class="doc-container">
                <div class="file-info">
                    <h5><?php echo htmlspecialchars(basename($file)); ?></h5>
                    <p>文件大小：<?php echo formatFileSize(filesize($path)); ?></p>
                </div>
                <?php
                $fileUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                           $_SERVER['HTTP_HOST'] . 
                           dirname($_SERVER['PHP_SELF']) . 
                           '/download.php?file=' . urlencode($file) .
                           ($shareCode ? '&share=' . urlencode($shareCode) : '');
                ?>
                <iframe src="https://docs.google.com/viewer?url=<?php echo urlencode($fileUrl); ?>&embedded=true" 
                        class="preview-frame"
                        onload="checkPreviewLoad(this)"></iframe>
                <div class="preview-error d-none">
                    <p>预览加载失败，您可以：</p>
                    <ul>
                        <li>使用Microsoft Office在线预览：
                            <a href="https://view.officeapps.live.com/op/view.aspx?src=<?php echo urlencode($fileUrl); ?>" 
                               target="_blank">点击这里</a>
                        </li>
                        <li>下载文件后在本地查看：
                            <a href="download.php?file=<?php echo urlencode($file); ?><?php echo $shareCode ? '&share=' . urlencode($shareCode) : ''; ?>" class="btn btn-sm btn-primary" download>
                                <i class="fas fa-download"></i> 下载文件
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <script>
        function checkPreviewLoad(iframe) {
            try {
                // 检查iframe是否成功加载了预览内容
                if (iframe.contentDocument.body.innerHTML.includes('error')) {
                    document.querySelector('.preview-error').classList.remove('d-none');
                }
            } catch (e) {
                // 如果出现跨域错误，等待一段时间后检查iframe大小
                setTimeout(() => {
                    if (iframe.clientHeight < 100) {
                        document.querySelector('.preview-error').classList.remove('d-none');
                    }
                }, 3000);
            }
        }
        </script>
    </body>
    </html>
    <?php
    exit;
}


header("Content-Type: $mime");
readfile($path);
exit; 