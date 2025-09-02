<?php
require_once 'functions.php';
requireLogin();


if (isset($_GET['logout'])) {
    logout();
    header('Location: login.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $result = uploadFile($_FILES['file']);
        $message = $result['message'];
    } elseif (isset($_POST['new_folder'])) {
        $result = createFolder($_POST['new_folder']);
        $message = $result['message'];
    }
}


$currentDir = isset($_GET['dir']) ? trim($_GET['dir'], '/') : '';


if (isset($_GET['delete'])) {
    $path = $currentDir ? ($currentDir . '/' . $_GET['delete']) : $_GET['delete'];
    if (is_dir(getUserStoragePath() . '/' . $path)) {
        $result = deleteFolder($path);
    } else {
        $result = deleteFile($path);
    }
    $message = $result['message'];
}


$keyword = $_GET['search'] ?? '';
$files = $keyword ? searchFiles($keyword) : getFileList(null, $currentDir);


$breadcrumbs = [];
if ($currentDir) {
    $parts = explode('/', $currentDir);
    $path = '';
    foreach ($parts as $part) {
        $path = $path ? ($path . '/' . $part) : $part;
        $breadcrumbs[] = [
            'name' => $part,
            'path' => $path
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
    <style>

        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin-top: 10px !important;
            }
            

            .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                width: 100%;
            }
            

            .table-responsive {
                font-size: 14px;
            }
            

            .table-mobile-hide {
                display: none;
            }
            

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 12px;
                margin-bottom: 5px;
            }
            
            .breadcrumb {
                font-size: 14px;
                white-space: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding: 0.5rem 0;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .search-form .col-md-2 {
                margin-top: 10px;
            }
            
            .file-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .file-name {
                word-break: break-all;
                font-size: 14px;
            }
            
            .form-control {
                margin-bottom: 10px;
            }
            
            .progress {
                height: 15px;
            }

            .user-menu {
                left: 10px;
                bottom: 10px;
            }
        }
        .modal-footer .btn {
            width: auto;
        }
        #conflictModal .modal-body {
            word-break: break-all;
        }
        .CodeMirror {
            height: 500px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        #editModal .modal-dialog {
            max-width: 90%;
            margin: 1.75rem auto;
        }
        #editModal .modal-content {
            height: 90vh;
        }
        #editModal .modal-body {
            display: flex;
            flex-direction: column;
            height: calc(90vh - 120px);
        }
        #editModal .CodeMirror {
            flex: 1;
            height: 100%;
        }

        .user-menu {
            position: fixed;
            left: 20px;
            bottom: 20px;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .user-menu:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .user-menu .dropdown-menu {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div class="d-flex align-items-center">
                <h2 class="h3 mb-2 mb-md-0 me-3">文件管理系统</h2>
                <a href="https://github.com/your-username/your-repo" target="_blank" class="btn btn-outline-dark btn-sm" title="查看项目源码">
                    <i class="fab fa-github"></i>
                </a>
            </div>
            <div class="action-buttons">
                <?php if (isAdmin()): ?>
                <a href="users.php" class="btn btn-outline-primary btn-sm me-2 mb-2 mb-md-0">用户管理</a>
                <a href="admin.php" class="btn btn-outline-danger btn-sm me-2 mb-2 mb-md-0">管理面板</a>
                <?php endif; ?>
                <a href="profile.php" class="btn btn-outline-primary btn-sm me-2 mb-2 mb-md-0">个人设置</a>
                <a href="api/docs/" class="btn btn-outline-info btn-sm me-2 mb-2 mb-md-0">API文档</a>
                <a href="?logout=1" class="btn btn-outline-danger btn-sm mb-2 mb-md-0">退出登录</a>
            </div>
        </div>

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

        <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12 col-md-6 mb-3 mb-md-0">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">上传文件</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" class="form-control" name="file">
                                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($currentDir); ?>">
                                <input type="hidden" name="conflict_action" id="conflictAction" value="">
                            </div>
                            <button type="submit" class="btn btn-primary">上传</button>
                            <div class="form-text">
                                最大文件大小: <?php echo formatFileSize(getMaxUploadSize()); ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">搜索文件</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="搜索文件..." 
                                       value="<?php echo htmlspecialchars($keyword); ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">搜索</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$keyword): ?>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="?dir=">根目录</a>
                </li>
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <li class="breadcrumb-item <?php echo ($i === count($breadcrumbs) - 1) ? 'active' : ''; ?>">
                    <?php if ($i === count($breadcrumbs) - 1): ?>
                        <?php echo htmlspecialchars($crumb['name']); ?>
                    <?php else: ?>
                        <a href="?dir=<?php echo urlencode($crumb['path']); ?>">
                            <?php echo htmlspecialchars($crumb['name']); ?>
                        </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="share.php" class="btn btn-info me-2">
                    <i class="fas fa-share-alt"></i> 已分享文件
                </a>
                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createFolderModal">
                    <i class="fas fa-folder-plus"></i> 创建文件夹
                </button>
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createFileModal">
                    <i class="fas fa-file-plus"></i> 创建文件
                </button>
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#downloadRemoteModal">
                    <i class="fas fa-cloud-download-alt"></i> 下载远程文件
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">文件列表</h5>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%">名称</th>
                                <th style="width: 15%">大小</th>
                                <th style="width: 15%">类型</th>
                                <th style="width: 15%">修改时间</th>
                                <th style="width: 15%">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($currentDir && !$keyword): ?>
                            <tr>
                                <td colspan="5">
                                    <a href="?dir=<?php echo urlencode(dirname($currentDir)); ?>" class="text-decoration-none">
                                        <i class="fas fa-level-up-alt me-2"></i> 返回上级目录
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($file['type'] === 'dir'): ?>
                                            <a href="?dir=<?php echo urlencode($currentDir . '/' . $file['name']); ?>" 
                                               class="text-decoration-none text-dark">
                                                <i class="fas fa-folder text-warning me-2"></i>
                                                <?php echo htmlspecialchars($file['name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-dark">
                                                <i class="fas fa-file text-primary me-2"></i>
                                                <?php echo htmlspecialchars($file['name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="align-middle"><?php echo formatFileSize($file['size']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($file['type']); ?></td>
                                <td class="align-middle"><?php echo $file['modified']; ?></td>
                                <td class="align-middle">
                                    <div class="btn-group">
                                        <?php if ($file['type'] !== 'dir'): ?>
                                            <a href="download.php?file=<?php echo urlencode($file['path']); ?>" 
                                               class="btn btn-sm btn-success" title="下载">
                                               <i class="fas fa-download"></i>
                                            </a>
                                            <?php 
                                            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                            $editableExtensions = [
                                                'txt', 'md', 'log', 'ini', 'conf', 'env',
                                                'html', 'htm', 'css', 'js', 'jsx', 'ts', 'tsx', 'vue', 'json', 'xml',
                                                'php', 'py', 'java', 'rb', 'go', 'rs', 'scala', 'kt', 'kts',
                                                'c', 'cpp', 'h', 'hpp', 'cs',
                                                'yaml', 'yml', 'toml', 'properties',
                                                'sh', 'bash', 'zsh', 'fish',
                                                'sql', 'r', 'pl', 'swift', 'lua', 'tcl', 'ps1', 'psm1'
                                            ];
                                            $isEditable = in_array($extension, $editableExtensions);
                                            $isTooLarge = $file['size'] > 6 * 1024; // 6KB限制
                                            if ($isEditable):
                                            ?>
                                                <button type="button" class="btn btn-sm <?php echo $isTooLarge ? 'btn-secondary' : 'btn-info'; ?>" 
                                                        onclick="<?php echo $isTooLarge ? 'alert(\'文件大小超过6KB限制，不支持在线编辑。请下载后编辑。\')' : 'openFileEditor(\'' . htmlspecialchars($file['path']) . '\')'; ?>"
                                                        title="<?php echo $isTooLarge ? '文件过大，不支持在线编辑' : '编辑'; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($previewUrl = getPreviewUrl($file)): ?>
                                                <a href="<?php echo $previewUrl; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   target="_blank" title="预览">
                                                   <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-success" title="分享"
                                                    onclick="showShareOptions('<?php echo htmlspecialchars($file['path']); ?>')">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="?dir=<?php echo urlencode($currentDir); ?>&delete=<?php echo urlencode($file['name']); ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('确定要删除吗？')" title="删除">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="conflictModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">文件已存在</h5>
                    <button type="button" class="btn-close" onclick="cancelUpload()"></button>
                </div>
                <div class="modal-body">
                    <p>文件 "<span id="conflictFileName"></span>" 已存在，请选择处理方式：</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cancelUpload()">取消</button>
                    <button type="button" class="btn btn-info" onclick="handleFileConflict('rename')">重命名</button>
                    <button type="button" class="btn btn-warning" onclick="handleFileConflict('overwrite')">覆盖</button>
                </div>
            </div>
        </div>
    </div>

    <div class="dropdown user-menu">
        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-start">
            <li>
                <a class="dropdown-item" href="change_password.php">
                    <i class="fas fa-key"></i> 修改密码
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li>
                <a class="dropdown-item" href="admin.php">
                    <i class="fas fa-cog"></i> 管理面板
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a class="dropdown-item" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> 退出登录
                </a>
            </li>
        </ul>
    </div>

    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">分享文件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="shareForm" method="post" action="share.php">
                        <input type="hidden" name="file_path" id="shareFilePath">
                        <div class="list-group">
                            <button type="submit" name="duration" value="7" class="list-group-item list-group-item-action">
                                分享7天
                            </button>
                            <button type="submit" name="duration" value="15" class="list-group-item list-group-item-action">
                                分享15天
                            </button>
                            <button type="submit" name="duration" value="30" class="list-group-item list-group-item-action">
                                分享30天
                            </button>
                            <button type="submit" name="duration" value="permanent" class="list-group-item list-group-item-action">
                                永久分享
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createFolderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">创建文件夹</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createFolderForm">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">文件夹名称</label>
                            <input type="text" class="form-control" id="folderName" name="foldername" required>
                            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($currentDir); ?>">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">创建</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createFileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">创建文件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createFileForm">
                        <div class="mb-3">
                            <label for="fileName" class="form-label">文件名称</label>
                            <input type="text" class="form-control" id="fileName" name="filename" required>
                            <input type="hidden" name="current_dir" value="<?php echo htmlspecialchars($currentDir); ?>">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">创建</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="downloadRemoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">下载远程文件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                                            <form id="downloadRemoteForm">
                            <div class="mb-3">
                                <label for="remoteUrl" class="form-label">文件URL</label>
                                <input type="url" class="form-control" id="remoteUrl" name="url" required 
                                       placeholder="请输入文件下载地址">
                            </div>
                            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($currentDir); ?>">
                            <div id="downloadProgress" class="d-none">
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="download-info small text-muted">
                                    <div>下载进度：<span class="progress-text">0%</span></div>
                                    <div>下载速度：<span class="speed-text">0 KB/s</span></div>
                                    <div>已下载/总大小：<span class="size-text">0 KB / 0 KB</span></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                <button type="submit" class="btn btn-primary">开始下载</button>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑文件：<span id="editFileName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeEditor()"></button>
                </div>
                <div class="modal-body">
                    <div id="editor-container" style="height: 100%;">
                        <textarea id="fileContent" name="content"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditor()">取消</button>
                    <button type="button" class="btn btn-primary" onclick="saveFile()">保存</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    $currentUser = getCurrentUser();
    $apiKey = $currentUser ? $currentUser['api_key'] : '';
    ?>
    <input type="hidden" id="apiKey" value="<?php echo htmlspecialchars($apiKey); ?>">

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    let lastUploadInfo = null;
    let conflictModal = null;

    let editor = null;
    let currentEditFile = null;
    let currentFileVersion = null;
    let editModal = null;

    document.addEventListener('DOMContentLoaded', function() {
        conflictModal = new bootstrap.Modal(document.getElementById('conflictModal'));
        editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const editorTextarea = document.getElementById('fileContent');
        if (editorTextarea) {
            editor = CodeMirror.fromTextArea(editorTextarea, {
                lineNumbers: true,
                mode: 'text/plain',
                theme: 'monokai',
                lineWrapping: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                indentWithTabs: true,
                foldGutter: true,
                gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                extraKeys: {
                    "Ctrl-S": function(cm) {
                        saveFile();
                        return false;
                    },
                    "Cmd-S": function(cm) {
                        saveFile();
                        return false;
                    }
                }
            });
            
            setTimeout(() => {
                if (editor) {
                    editor.refresh();
                }
            }, 100);
        }

        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', handleUpload);
        }
    });

    async function handleUpload(e) {
        e.preventDefault();
        
        const form = e.target;
        const fileInput = form.querySelector('input[type="file"]');
        const conflictActionInput = form.querySelector('#conflictAction');
        
        if (!fileInput.files.length) {
            alert('请选择要上传的文件');
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch('upload_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Upload response:', result);
            
            if (!result.success) {
                if (result.conflict) {
                    lastUploadInfo = {
                        file: fileInput.files[0],
                        directory: formData.get('dir') || '',
                        filename: result.filename
                    };
                    
                    document.getElementById('conflictFileName').textContent = result.filename;
                    conflictModal.show();
                    return;
                }
                throw new Error(result.message);
            }
            
            showUploadResult(result);
            location.reload();
            
        } catch (error) {
            console.error('Upload error:', error);
            alert('上传失败：' + error.message);
        }
    }

    async function handleFileConflict(action) {
        if (!lastUploadInfo || !lastUploadInfo.file) {
            alert('上传状态已失效，请重新上传');
            if (conflictModal) {
                conflictModal.hide();
            }
            return;
        }

        try {
            const formData = new FormData();
            formData.append('file', lastUploadInfo.file);
            formData.append('dir', lastUploadInfo.directory);
            formData.append('conflict_action', action);
            
            console.log('Conflict handling:', action);
            
            const response = await fetch('upload_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Conflict response:', result);
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            if (conflictModal) {
                conflictModal.hide();
            }
            
            showUploadResult(result);
            
            location.reload();
            
        } catch (error) {
            console.error('Conflict handling error:', error);
            alert('处理冲突失败：' + error.message);
        } finally {
            lastUploadInfo = null;
        }
    }

    function showUploadResult(result) {
        let message = result.message;
        if (result.original_name) {
            message += `\n原文件名：${result.original_name}`;
            message += `\n新文件名：${result.filename}`;
        }
        alert(message);
    }

    function cancelUpload() {
        if (conflictModal) {
            conflictModal.hide();
        }
        lastUploadInfo = null;
    }

    function showShareOptions(filePath) {
        document.getElementById('shareFilePath').value = filePath;
        new bootstrap.Modal(document.getElementById('shareModal')).show();
    }

    document.getElementById('createFolderForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('create_folder.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            // 关闭模态框
            bootstrap.Modal.getInstance(document.getElementById('createFolderModal')).hide();
            
            // 显示结果提示
            const alert = document.createElement('div');
            alert.className = `alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show`;
            alert.innerHTML = `
                ${result.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertAdjacentElement('afterbegin', alert);
            
            if (result.success) {
                this.reset();
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    document.getElementById('createFileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('create_file.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            bootstrap.Modal.getInstance(document.getElementById('createFileModal')).hide();
            const alert = document.createElement('div');
            alert.className = `alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show`;
            alert.innerHTML = `
                ${result.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertAdjacentElement('afterbegin', alert);
            
            if (result.success) {
                this.reset();
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    document.getElementById('downloadRemoteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = this;
        const url = form.querySelector('input[name="url"]').value;
        const progressBar = form.querySelector('.progress-bar');
        const progressText = form.querySelector('.progress-text');
        const speedText = form.querySelector('.speed-text');
        const sizeText = form.querySelector('.size-text');
        const downloadProgress = form.querySelector('#downloadProgress');
        const submitButton = form.querySelector('button[type="submit"]');
        downloadProgress.classList.remove('d-none');
        submitButton.disabled = true;
        
        try {
            const response = await fetch('download_remote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'url=' + encodeURIComponent(url)
            });
            
            const reader = response.body.getReader();
            
            while (true) {
                const {value, done} = await reader.read();
                if (done) break;
                const lines = new TextDecoder().decode(value).split('\n');
                for (const line of lines) {
                    if (!line) continue;
                    try {
                        const data = JSON.parse(line);
                        if (data.progress !== undefined) {
                            progressBar.style.width = data.progress + '%';
                            progressText.textContent = data.progress + '%';
                            speedText.textContent = data.speed;
                            sizeText.textContent = data.downloaded + ' / ' + data.total;
                        }
                    } catch (e) {
                        console.error('Progress parse error:', e);
                    }
                }
            }
            location.reload();
            
        } catch (error) {
            console.error('Download error:', error);
            alert('下载失败：' + error.message);
        } finally {
            submitButton.disabled = false;
        }
    });
    async function openFileEditor(filePath) {
        try {
            if (!editor) {
                throw new Error('编辑器未正确初始化');
            }

            const apiKey = document.getElementById('apiKey').value;
            console.log('Opening file:', filePath);
            
            const response = await fetch(`api/?action=get_file&file=${encodeURIComponent(filePath)}`, {
                headers: {
                    'X-API-Key': apiKey
                }
            });
            
            if (!response.ok) {
                throw new Error('获取文件内容失败: ' + response.status);
            }
            
            const data = await response.json();
            console.log('File data:', data);
            
            if (!data.success) {
                throw new Error(data.message || '获取文件内容失败');
            }
            const extension = filePath.split('.').pop().toLowerCase();
            const modeMap = {
                'html': 'htmlmixed',
                'htm': 'htmlmixed',
                'css': 'css',
                'scss': 'css',
                'sass': 'css',
                'less': 'css',
                'js': 'javascript',
                'jsx': 'jsx',
                'ts': 'javascript',
                'tsx': 'jsx',
                'vue': 'vue',
                'json': 'javascript',
                'xml': 'xml',
                'svg': 'xml',
                'wxml': 'xml',
                'xaml': 'xml',
                'php': 'php',
                'py': 'python',
                'pyc': 'python',
                'pyw': 'python',
                'java': 'text/x-java',
                'jsp': 'application/x-jsp',
                'asp': 'application/x-aspx',
                'aspx': 'application/x-aspx',
                'rb': 'ruby',
                'erb': 'ruby',
                'go': 'go',
                'rs': 'rust',
                'scala': 'text/x-scala',
                'kt': 'text/x-kotlin',
                'kts': 'text/x-kotlin',
                'dart': 'dart',
                'groovy': 'groovy',
                'c': 'text/x-csrc',
                'cpp': 'text/x-c++src',
                'cc': 'text/x-c++src',
                'h': 'text/x-csrc',
                'hpp': 'text/x-c++src',
                'hh': 'text/x-c++src',
                'cs': 'text/x-csharp',
                'm': 'text/x-objectivec',
                'mm': 'text/x-objectivec',
                'swift': 'swift',
                'yaml': 'yaml',
                'yml': 'yaml',
                'toml': 'toml',
                'properties': 'properties',
                'ini': 'properties',
                'conf': 'properties',
                'config': 'properties',
                'env': 'properties',
                'htaccess': 'apache',
                'nginx': 'nginx',
                'dockerfile': 'dockerfile',
                'docker-compose.yml': 'yaml',
                'sh': 'shell',
                'bash': 'shell',
                'zsh': 'shell',
                'fish': 'shell',
                'bat': 'batch',
                'cmd': 'batch',
                'sql': 'sql',
                'mysql': 'sql',
                'pgsql': 'sql',
                'plsql': 'sql',
                'mongodb': 'javascript',
                'redis': 'redis',
                'r': 'r',
                'pl': 'perl',
                'pm': 'perl',
                'swift': 'swift',
                'lua': 'lua',
                'tcl': 'tcl',
                'ps1': 'powershell',
                'psm1': 'powershell',
                'vb': 'vb',
                'vbs': 'vbscript',
                'f': 'fortran',
                'f90': 'fortran',
                'lisp': 'commonlisp',
                'cl': 'commonlisp',
                'hs': 'haskell',
                'lhs': 'haskell',
                'erl': 'erlang',
                'ex': 'elixir',
                'exs': 'elixir',
                'md': 'markdown',
                'markdown': 'markdown',
                'textile': 'textile',
                'rst': 'rst',
                'asciidoc': 'asciidoc',
                'adoc': 'asciidoc',
                'tex': 'stex',
                'latex': 'stex',
                'txt': 'text/plain',
                'log': 'text/plain',
                'csv': 'text/plain',
                'tsv': 'text/plain'
            };
            
            const mode = modeMap[extension] || 'text/plain';
            console.log('Setting mode:', mode);
            editor.setOption('mode', mode);
            
            editor.setValue(data.content);
            currentEditFile = filePath;
            currentFileVersion = data.version;
            
            if (data.content.length > 6 * 1024) {
                alert('警告：此文件大小超过6KB限制，在线编辑功能可能受限。建议下载后编辑。');
            }
            
            document.getElementById('editFileName').textContent = filePath;
            editModal.show();
            
            setTimeout(() => {
                editor.refresh();
                editor.focus();
            }, 100);
            
        } catch (error) {
            console.error('Error opening file:', error);
            alert('打开文件失败：' + error.message);
        }
    }

    async function saveFile() {
        if (!currentEditFile || !currentFileVersion) {
            alert('没有打开的文件');
            return;
        }
        
        try {
            const apiKey = document.getElementById('apiKey').value;
            if (!apiKey) {
                throw new Error('缺少API密钥，请刷新页面后重试');
            }

            const content = editor.getValue();
            
            if (content.length > 6 * 1024) {
                alert('文件内容超过6KB限制，不支持在线编辑。请下载文件后在本地编辑。');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', currentEditFile);
            formData.append('content', content);
            formData.append('version', currentFileVersion);
            
            console.log('Saving file:', currentEditFile);
            console.log('Content length:', content.length);
            
            const timestamp = new Date().getTime();
            const response = await fetch(`api/edit_file.php?t=${timestamp}`, {
                method: 'POST',
                headers: {
                    'X-API-Key': apiKey,
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                },
                body: formData,
                credentials: 'same-origin'
            });
            
            console.log('Response headers:', Object.fromEntries([...response.headers]));
            console.log('Response status:', response.status);
            
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Raw response:', responseText);
                throw new Error(`服务器返回了无效的响应 (${response.status}): ${responseText.substring(0, 200)}`);
            }
            
            if (!data.success) {
                if (response.status === 409) {
                    if (confirm('文件已被其他用户修改。是否刷新获取最新内容？')) {
                        await openFileEditor(currentEditFile);
                    }
                } else {
                    throw new Error(data.message || '保存失败');
                }
                return;
            }
            
            currentFileVersion = data.file.version;
            alert('保存成功');
            
            location.reload();
            
        } catch (error) {
            console.error('Save error:', error);
            alert('保存失败：' + error.message);
        }
    }

    function closeEditor() {
        if (editor.isClean()) {
            editModal.hide();
            currentEditFile = null;
            currentFileVersion = null;
            return;
        }
        
        if (confirm('文件已修改，是否保存？')) {
            saveFile().then(() => {
                editModal.hide();
                currentEditFile = null;
                currentFileVersion = null;
            });
        } else {
            editModal.hide();
            currentEditFile = null;
            currentFileVersion = null;
        }
    }
    </script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/markdown/markdown.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/yaml/yaml.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/shell/shell.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/properties/properties.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/mode/powershell/powershell.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/addon/comment/comment.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/addon/fold/foldcode.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.2/addon/fold/foldgutter.min.js"></script>
</body>
</html> 