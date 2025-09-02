<?php
require_once 'functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => '请求方法不正确']));
}

$url = $_POST['url'] ?? '';
if (empty($url)) {
    die(json_encode(['success' => false, 'message' => 'URL不能为空']));
}

try {
    $decodedUrl = urldecode($url);
    $filename = basename(parse_url($decodedUrl, PHP_URL_PATH));
    
    $filename = urldecode($filename);
    
    if (empty($filename) || $filename === '/' || $filename === '.') {
        $filename = 'downloaded_' . date('YmdHis');
    }
    
    $filename = preg_replace('/[^\p{L}\p{N}\s\-_.]/u', '', $filename);
    
    $userPath = getUserStoragePath();

    $subdir = $_POST['dir'] ?? '';
    $subdir = trim($subdir, '/');
    $subdir = str_replace(['..', '\\'], '', $subdir);

    $targetDir = $userPath . ($subdir ? '/' . $subdir : '');

    if (!file_exists($targetDir)) {
        if (!@mkdir($targetDir, 0755, true)) {
            throw new Exception('创建目标目录失败，请检查权限');
        }
        @chmod($targetDir, 0755);
    }

    $realUser = realpath($userPath);
    $realTargetDir = realpath($targetDir);
    if ($realTargetDir === false || strpos($realTargetDir, $realUser) !== 0) {
        throw new Exception('无效的目标目录');
    }

    $targetPath = $targetDir . '/' . $filename;

    if (file_exists($targetPath)) {
        $pathInfo = pathinfo($filename);
        $i = 1;
        do {
            $newName = $pathInfo['filename'] . '_' . $i;
            if (!empty($pathInfo['extension'])) {
                $newName .= '.' . $pathInfo['extension'];
            }
            $targetPath = $targetDir . '/' . $newName;
            $i++;
        } while (file_exists($targetPath));
        $filename = $newName;
    }

    $ch = curl_init($url);
    $fp = fopen($targetPath, 'w+');

    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($ch, $downloadSize, $downloaded) {
        if ($downloadSize > 0) {
            $progress = round(($downloaded / $downloadSize) * 100, 2);
            $speed = formatFileSize($downloaded / (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])) . '/s';
            file_put_contents('php://output', json_encode([
                'progress' => $progress,
                'speed' => $speed,
                'downloaded' => formatFileSize($downloaded),
                'total' => formatFileSize($downloadSize)
            ]) . "\n");
            ob_flush();
            flush();
        }
    });

    $success = curl_exec($ch);
    $error = curl_error($ch);
    
    curl_close($ch);
    fclose($fp);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => '文件下载成功',
            'filename' => $filename
        ]);
    } else {
        unlink($targetPath);
        throw new Exception('下载失败：' . $error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 