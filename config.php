<?php
define('ROOT_PATH', __DIR__);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024);

define('USER_SPACE_PATH', ROOT_PATH . '/uploads');
define('ADMIN_SPACE_QUOTA', 5 * 1024 * 1024 * 1024);
define('USER_SPACE_QUOTA', 1 * 1024 * 1024 * 1024);

define('DB_FILE', ROOT_PATH . '/database.db');

define('PREVIEW_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif', 'heic', 'raw', 'psd',
    
    'txt', 'csv', 'md', 'markdown', 'php', 'js', 'css', 'html', 'htm', 'xml', 'json', 'yaml', 'yml',
    'ini', 'log', 'conf', 'config', 'sh', 'bash', 'sql', 'properties', 'env', 'gitignore',
    'c', 'cpp', 'h', 'hpp', 'java', 'py', 'rb', 'go', 'rust', 'ts', 'tsx', 'jsx', 'vue',
    'gradle', 'groovy', 'perl', 'php4', 'php5', 'phtml', 'swift', 'r', 'scala', 'kotlin', 'lua',
    
    'pdf', 'rtf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
    
    'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma', 'opus',
    
    'mp4', 'webm', 'ogv', 'm4v', 'mov', 'avi', 'mkv', 'flv', '3gp'
]);

define('VIDEO_EXTENSIONS', [
    'mp4', 'webm', 'ogv',
    'avi', 'mkv', 'mov', 'wmv',
    'flv', '3gp', 'm4v', 'mpeg',
    'ts', 'vob', 'dat', 'rm', 'rmvb'
]);

define('VIDEO_PLAYER_CONFIG', [
    'width' => '100%',
    'height' => 'auto',
    'controls' => true,
    'autoplay' => false,
    'preload' => 'metadata'
]);

date_default_timezone_set('Asia/Shanghai');

error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

define('SYSTEM_TOTAL_QUOTA', 10 * 1024 * 1024 * 1024);
define('DEFAULT_USER_QUOTA', 1 * 1024 * 1024 * 1024); 