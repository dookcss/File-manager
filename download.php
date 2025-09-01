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

$mime = mime_content_type($path);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($path));
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=0');

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = substr(stristr($_SERVER['HTTP_RANGE'], '='), 1);
    $size = filesize($path);
    $start = intval(stristr($range, '-', true));
    $end = intval(substr(stristr($range, '-'), 1));
    if (!$end) $end = $size - 1;
    
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    header('Content-Length: ' . ($end - $start + 1));
    
    $fp = fopen($path, 'rb');
    fseek($fp, $start);
    $chunk = 8192;
    while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
        if ($pos + $chunk > $end) {
            $chunk = $end - $pos + 1;
        }
        echo fread($fp, $chunk);
        flush();
    }
    fclose($fp);
} else {
    readfile($path);
}
exit; 