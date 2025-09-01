<?php
require_once 'functions.php';
requireLogin();

header('Content-Type: application/json');

try {
    if (empty($_FILES['file']['name'])) {
        throw new Exception('请选择要上传的文件');
    }

    $currentDir = $_POST['dir'] ?? '';
    $uploadDir = USER_SPACE_PATH . '/' . $_SESSION['username'];
    
    if (!file_exists($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create directory: $uploadDir");
            throw new Exception('创建用户目录失败，请联系管理员检查权限');
        }

        if (!@chmod($uploadDir, 0755)) {
            error_log("Failed to set permissions on directory: $uploadDir");
            throw new Exception('设置目录权限失败，请联系管理员检查服务器配置');
        }
    }

    if ($currentDir) {
        $uploadDir .= '/' . trim($currentDir, '/');
        $realUploadDir = realpath($uploadDir);
        $realUserDir = realpath(USER_SPACE_PATH . '/' . $_SESSION['username']);
        
        if (!$realUploadDir || !$realUserDir || strpos($realUploadDir, $realUserDir) !== 0) {
            throw new Exception('无效的目标目录');
        }
    }

    if (!file_exists($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create target directory: $uploadDir");
            throw new Exception('创建目标目录失败，请联系管理员检查权限');
        }

        if (!@chmod($uploadDir, 0755)) {
            error_log("Failed to set permissions on target directory: $uploadDir");
            throw new Exception('设置目标目录权限失败，请联系管理员检查服务器配置');
        }
    }


    if (!is_writable($uploadDir)) {
        error_log("Directory not writable: $uploadDir");
        throw new Exception('目标目录不可写，请联系管理员检查权限设置');
    }

    $fileName = $_FILES['file']['name'];
    $targetPath = $uploadDir . '/' . $fileName;
    $conflictAction = $_POST['conflict_action'] ?? 'ask';

    if (file_exists($targetPath)) {
        if (empty($conflictAction) || $conflictAction === 'ask') {
            echo json_encode([
                'success' => false,
                'conflict' => true,
                'filename' => $fileName,
                'directory' => $currentDir,
                'message' => '文件已存在，请选择处理方式'
            ]);
            exit;
        } elseif ($conflictAction === 'rename') {
            $pathInfo = pathinfo($fileName);
            $i = 1;
            do {
                $newName = $pathInfo['filename'] . "($i)";
                if (isset($pathInfo['extension']) && $pathInfo['extension'] !== '') {
                    $newName .= "." . $pathInfo['extension'];
                }
                $targetPath = $uploadDir . '/' . $newName;
                $i++;
            } while (file_exists($targetPath));
            $fileName = $newName;
        }

        elseif ($conflictAction === 'overwrite') {
            if (file_exists($targetPath) && !is_writable($targetPath)) {
                error_log("Cannot overwrite file: $targetPath");
                throw new Exception('无法覆盖已存在的文件，请检查权限');
            }

        } else {
            throw new Exception('无效的冲突处理方式');
        }
    }


    if ($_FILES['file']['size'] > getMaxUploadSize()) {
        throw new Exception('文件超过最大允许大小');
    }


    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件超过了php.ini中upload_max_filesize限制',
            UPLOAD_ERR_FORM_SIZE => '文件超过了HTML表单中MAX_FILE_SIZE限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];
        throw new Exception($errors[$_FILES['file']['error']] ?? '文件上传失败');
    }


    if (!@move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        error_log("Failed to move uploaded file to: $targetPath");
        throw new Exception('移动上传文件失败，请联系管理员检查目录权限');
    }


    if (!@chmod($targetPath, 0644)) {
        error_log("Failed to set permissions on file: $targetPath");
        error_log('警告：无法设置文件权限，但文件已成功上传');
    }

    $response = [
        'success' => true,
        'message' => '文件上传成功',
        'filename' => $fileName,
        'directory' => $currentDir
    ];
    
    if ($conflictAction === 'rename' && $fileName !== $_FILES['file']['name']) {
        $response['message'] = '文件已重命名并上传成功';
        $response['original_name'] = $_FILES['file']['name'];
    } elseif ($conflictAction === 'overwrite') {
        $response['message'] = '文件已覆盖上传成功';
        $response['overwritten'] = true;
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log('File upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 