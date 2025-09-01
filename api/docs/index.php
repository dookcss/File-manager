<?php
require_once '../../functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 文档 - 文件管理系统</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--dark-color);
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .doc-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .doc-section .header {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem;
            margin: -1rem -1rem 1rem -1rem;
        }

        .endpoint {
            border-left: 4px solid var(--secondary-color);
            padding: 0.8rem;
            margin: 0.8rem 0;
            background: #f8f9fa;
        }

        .method {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 0.5rem;
        }

        .method.get { background: #61affe; color: white; }
        .method.post { background: #49cc90; color: white; }
        .method.delete { background: #f93e3e; color: white; }

        .param-table {
            font-size: 0.9rem;
            margin: 0.8rem 0;
        }

        .param-table td {
            padding: 0.5rem;
        }

        .param-table ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        .param-table ul ul {
            font-size: 0.85rem;
            color: #666;
        }

        .response-example {
            background: #272822;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }

        .code-block {
            position: relative;
        }

        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem 0.5rem;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .copy-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        @media (max-width: 768px) {
            .container {
                padding: 8px;
            }
            
            .endpoint {
                padding: 0.6rem;
                margin: 0.6rem 0;
            }
            
            .param-table {
                font-size: 0.8rem;
            }
            
            pre {
                font-size: 0.75rem;
                padding: 0.6rem;
            }
            
            .doc-section .card-body {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-book"></i> API 文档
            </a>
            <div>
                <a href="../../index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> 返回主页
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="doc-section card">
            <div class="card-body">
                <div class="header">
                    <h2><i class="fas fa-server"></i> API 概述</h2>
                </div>
                <p>本API提供了完整的文件管理功能，包括：</p>
                <ul>
                    <li>文件上传与下载</li>
                    <li>文件在线编辑</li>
                    <li>文件夹操作</li>
                    <li>文件列表获取</li>
                    <li>文件搜索</li>
                    <li>文件分享</li>
                </ul>
                <div class="alert alert-warning">
                    <h5>使用限制：</h5>
                    <ul>
                        <li>单个文件上传大小限制：<?php echo formatFileSize(MAX_UPLOAD_SIZE); ?></li>
                        <li>总存储空间限制：<?php echo formatFileSize(getUserQuota()); ?></li>
                        <li>API请求频率限制：每分钟100次</li>
                        <li>在线编辑文件大小限制：6KB ，大于6KB的文件请采用本地修改后覆盖上传</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="doc-section card">
            <div class="card-body">
                <div class="header">
                    <h2><i class="fas fa-lock"></i> 认证方式</h2>
                </div>
                <p>所有 API 请求都需要在请求头中包含 API Key 进行身份验证。</p>
                <div class="alert alert-info">
                    <h5>认证说明：</h5>
                    <ul>
                        <li>在请求头中添加 <code>X-API-KEY</code> 字段</li>
                        <li>API Key 可在个人设置页面生成或查看</li>
                        <li>API Key 泄露后可以在个人设置页面重新生成</li>
                        <li>API Key 与用户账号权限一致</li>
                        <li>每个用户只能访问自己的文件</li>
                    </ul>
                </div>
                <div class="code-block">
                    <h5>请求头示例：</h5>
                    <pre><code class="language-http">GET /api/?action=list HTTP/1.1
Host: your-domain.com
X-API-KEY: your_api_key_here
Accept: application/json</code></pre>
                    <button class="copy-btn" onclick="copyCode(this)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div class="alert alert-danger mt-3">
                    <h5>错误响应：</h5>
                    <p>当API Key无效或未提供时，将返回以下错误：</p>
                    <pre><code class="language-json">{
    "success": false,
    "message": "缺少API密钥或API密钥无效",
    "error_code": "AUTH_ERROR"
}</code></pre>
                </div>
            </div>
        </div>

        <div class="doc-section card">
            <div class="card-body">
                <div class="header">
                    <h2><i class="fas fa-file-upload"></i> 文件上传</h2>
                </div>
                <div class="endpoint">
                    <h3>
                        <span class="method post">POST</span>
                        上传文件
                    </h3>
                    <code>/api/?action=upload</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>File</td>
                                    <td>是</td>
                                    <td>要上传的文件，最大支持 <?php echo formatFileSize(MAX_UPLOAD_SIZE); ?></td>
                                </tr>
                                <tr>
                                    <td>directory</td>
                                    <td>String</td>
                                    <td>否</td>
                                    <td>
                                        上传目标目录的路径<br>
                                        - 默认为根目录<br>
                                        - 目录必须存在<br>
                                        - 示例：<code>documents/2024</code>
                                    </td>
                                </tr>
                                <tr>
                                    <td>conflict_action</td>
                                    <td>String</td>
                                    <td>否</td>
                                    <td>
                                        文件冲突处理方式：<br>
                                        <ul class="mb-0">
                                            <li><strong>ask</strong>：遇到同名文件时返回冲突信息（默认行为）
                                                <ul>
                                                    <li>不会修改已存在的文件</li>
                                                    <li>返回409状态码和冲突信息</li>
                                                    <li>前端可以根据返回的信息显示选项让用户选择</li>
                                                </ul>
                                            </li>
                                            <li><strong>rename</strong>：自动重命名文件
                                                <ul>
                                                    <li>在文件名后添加序号，如：example(1).txt</li>
                                                    <li>序号从1开始递增，直到找到可用的文件名</li>
                                                    <li>返回200状态码和新的文件名</li>
                                                </ul>
                                            </li>
                                            <li><strong>overwrite</strong>：覆盖已存在的文件
                                                <ul>
                                                    <li>删除原有文件并上传新文件</li>
                                                    <li>原文件无法恢复，请谨慎使用</li>
                                                    <li>返回200状态码和覆盖成功信息</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>上传成功</td>
                                        <td>
                                            <pre><code class="language-json">{
    "success": true,
    "message": "文件上传成功",
    "file": {
        "name": "example.txt",
        "size": 1024,
        "path": "documents/example.txt",
        "url": "/files/documents/example.txt",
        "type": "text/plain",
        "created": "2024-03-12 15:30:00"
    }
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>409</td>
                                        <td>文件冲突</td>
                                        <td>
                                            <pre><code class="language-json">{
    "success": false,
    "message": "文件已存在",
    "error_code": "FILE_EXISTS",
    "conflict": true,
    "file": {
        "name": "example.txt",
        "directory": "documents",
        "size": 2048,
        "modified": "2024-03-10 12:00:00"
    }
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>413</td>
                                        <td>文件过大</td>
                                        <td>
                                            <pre><code class="language-json">{
    "success": false,
    "message": "文件大小超过限制",
    "error_code": "FILE_TOO_LARGE",
    "max_size": 104857600
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5>代码示例：</h5>
                        <div class="code-block">
                            <h6>Python:</h6>
                            <pre><code class="language-python">import requests

def upload_file(file_path, api_key, directory=None, conflict_action='ask'):
    url = 'https://your-domain.com/api/?action=upload'
    headers = {'X-API-KEY': api_key}
    
    # 准备文件和其他参数
    files = {'file': open(file_path, 'rb')}
    data = {}
    if directory:
        data['directory'] = directory
    if conflict_action:
        data['conflict_action'] = conflict_action
    
    # 发送请求
    response = requests.post(url, headers=headers, files=files, data=data)
    
    # 处理响应
    if response.status_code == 409:  # 文件冲突
        conflict_info = response.json()
        print(f"文件已存在: {conflict_info['file']['name']}")
        # 这里可以让用户选择处理方式
        action = input("请选择处理方式 (rename/overwrite/cancel): ")
        if action in ['rename', 'overwrite']:
            # 重新上传，指定处理方式
            data['conflict_action'] = action
            response = requests.post(url, headers=headers, files=files, data=data)
    
    return response.json()</code></pre>
                        </div>
                        <div class="code-block mt-3">
                            <h6>PHP:</h6>
                            <pre><code class="language-php">function uploadFile($filePath, $apiKey, $directory = null, $conflictAction = 'ask') {
    $url = 'https://your-domain.com/api/?action=upload';
    
    $curl = curl_init();
    $postFields = array(
        'file' => new CURLFile($filePath),
        'conflict_action' => $conflictAction
    );
    
    if ($directory) {
        $postFields['directory'] = $directory;
    }
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'X-API-KEY: ' . $apiKey
        )
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 409) {  // 文件冲突
        echo "文件已存在: " . $result['file']['name'] . "\n";
        // 这里可以让用户选择处理方式
        $action = readline("请选择处理方式 (rename/overwrite/cancel): ");
        if (in_array($action, ['rename', 'overwrite'])) {
            // 重新上传，指定处理方式
            return uploadFile($filePath, $apiKey, $directory, $action);
        }
    }
    
    return $result;
}</code></pre>
                        </div>
                        <div class="code-block mt-3">
                            <h6>cURL:</h6>
                            <pre><code class="language-bash"># 上传文件（默认处理方式）
curl -X POST "https://your-domain.com/api/?action=upload" \
     -H "X-API-KEY: your_api_key_here" \
     -F "file=@/path/to/file.txt"

# 上传到指定目录并自动重命名
curl -X POST "https://your-domain.com/api/?action=upload" \
     -H "X-API-KEY: your_api_key_here" \
     -F "file=@/path/to/file.txt" \
     -F "directory=documents/2024" \
     -F "conflict_action=rename"

# 上传并覆盖已存在的文件
curl -X POST "https://your-domain.com/api/?action=upload" \
     -H "X-API-KEY: your_api_key_here" \
     -F "file=@/path/to/file.txt" \
     -F "conflict_action=overwrite"</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-section card">
            <div class="card-body">
                <div class="header">
                    <h2><i class="fas fa-edit"></i> 文件编辑</h2>
                </div>
                <div class="endpoint">
                    <h3>
                        <span class="method post">POST</span>
                        编辑文件
                    </h3>
                    <code>/api/?action=edit_file</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>String</td>
                                    <td>是</td>
                                    <td>要编辑的文件路径</td>
                                </tr>
                                <tr>
                                    <td>content</td>
                                    <td>String</td>
                                    <td>是</td>
                                    <td>文件的新内容</td>
                                </tr>
                                <tr>
                                    <td>version</td>
                                    <td>String</td>
                                    <td>是</td>
                                    <td>
                                        文件的当前版本（MD5哈希）<br>
                                        用于检测文件是否被其他用户修改
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>编辑成功</td>
                                        <td>
                                            <pre><code class="language-json">{
    "success": true,
    "message": "文件保存成功",
    "file": {
        "name": "example.txt",
        "path": "documents/example.txt",
        "size": 1024,
        "version": "d41d8cd98f00b204e9800998ecf8427e",
        "modified": "2024-03-12 15:30:00"
    }
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>409</td>
                                        <td>版本冲突</td>
                                        <td>
                                            <pre><code class="language-json">{
    "success": false,
    "message": "文件已被其他用户修改",
    "error_code": "VERSION_CONFLICT",
    "file": {
        "current_version": "e4da3b7fbbce2345d7772b0674a318d5",
        "modified": "2024-03-12 15:25:00",
        "modified_by": "other_user"
    }
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5>代码示例：</h5>
                        <div class="code-block">
                            <h6>Python:</h6>
                            <pre><code class="language-python">import requests

def edit_file(file_path, content, version, api_key):
    url = 'https://your-domain.com/api/?action=edit_file'
    headers = {
        'X-API-KEY': api_key,
        'Content-Type': 'application/x-www-form-urlencoded'
    }
    
    data = {
        'file': file_path,
        'content': content,
        'version': version
    }
    
    response = requests.post(url, headers=headers, data=data)
    
    if response.status_code == 409:
        # 处理版本冲突
        conflict_info = response.json()
        print(f"文件已被修改，当前版本: {conflict_info['file']['current_version']}")
        # 这里可以让用户选择如何处理冲突
        return conflict_info
    
    return response.json()</code></pre>
                        </div>
                        <div class="code-block mt-3">
                            <h6>PHP:</h6>
                            <pre><code class="language-php">function editFile($filePath, $content, $version, $apiKey) {
    $url = 'https://your-domain.com/api/?action=edit_file';
    
    $curl = curl_init();
    $postFields = array(
        'file' => $filePath,
        'content' => $content,
        'version' => $version
    );
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'X-API-KEY: ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded'
        )
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 409) {
        // 处理版本冲突
        echo "文件已被修改，当前版本: " . $result['file']['current_version'] . "\n";
        // 这里可以让用户选择如何处理冲突
        return $result;
    }
    
    return $result;
}</code></pre>
                        </div>
                        <div class="code-block mt-3">
                            <h6>cURL:</h6>
                            <pre><code class="language-bash"># 编辑文件
curl -X POST "https://your-domain.com/api/?action=edit_file" \
     -H "X-API-KEY: your_api_key_here" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "file=documents/example.txt" \
     -d "content=Hello World" \
     -d "version=d41d8cd98f00b204e9800998ecf8427e"</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-section card">
            <div class="card-body">
                <div class="header">
                    <h2><i class="fas fa-server"></i> 接口列表</h2>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method get">GET</span>
                        下载文件
                    </h3>
                    <code>/api/?action=download&file={filepath}</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>String</td>
                                    <td>是</td>
                                    <td>要下载的文件路径（相对于用户根目录）<br>
                                        例如：<code>documents/example.txt</code>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>下载成功</td>
                                        <td>文件内容（二进制数据）</td>
                                    </tr>
                                    <tr>
                                        <td>404</td>
                                        <td>文件不存在</td>
                                        <td>
<pre><code class="language-json">{
    "error": "File not found"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>403</td>
                                        <td>无权访问</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Access denied"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method delete">DELETE</span>
                        删除文件
                    </h3>
                    <code>/api/?action=delete&file={filepath}</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>String</td>
                                    <td>是</td>
                                    <td>要删除的文件路径（相对于用户根目录）<br>
                                        例如：<code>documents/example.txt</code>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="alert alert-info">
                            <strong>注意：</strong> 为了兼容性，此接口同时支持 DELETE 和 POST 方法
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>删除成功</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "message": "文件删除成功"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>404</td>
                                        <td>文件不存在</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "文件不存在"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>403</td>
                                        <td>无权访问</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Access denied"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method get">GET</span>
                        获取文件列表
                    </h3>
                    <code>/api/?action=list&path={directory_path}</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>path</td>
                                    <td>String</td>
                                    <td>否</td>
                                    <td>
                                        要列出的目录路径（相对于用户根目录）<br>
                                        <ul>
                                            <li>不传参数：列出根目录内容</li>
                                            <li>单层目录：<code>documents</code></li>
                                            <li>多层目录：<code>documents/images</code></li>
                                        </ul>
                                        <div class="alert alert-warning mt-2">
                                            <strong>注意：</strong>
                                            <ul class="mb-0">
                                                <li>路径使用正斜杠（/）作为分隔符</li>
                                                <li>不允许使用 .. 进行目录遍历</li>
                                                <li>只能访问自己的文件目录</li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>获取成功</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "path": "documents",
    "files": [
        {
            "name": "example.txt",
            "path": "documents/example.txt",
            "size": 1024,
            "type": "file",
            "modified": "2024-03-20 10:30:00"
        },
        {
            "name": "images",
            "path": "documents/images",
            "size": 0,
            "type": "dir",
            "modified": "2024-03-20 10:00:00"
        }
    ]
}</code></pre>
                                            <div class="alert alert-info mt-2">
                                                <strong>返回字段说明：</strong>
                                                <ul class="mb-0">
                                                    <li><code>success</code>: 是否成功</li>
                                                    <li><code>path</code>: 当前目录路径</li>
                                                    <li><code>files</code>: 文件列表
                                                        <ul>
                                                            <li><code>name</code>: 文件/文件夹名称</li>
                                                            <li><code>path</code>: 相对路径</li>
                                                            <li><code>type</code>: 类型（"file"或"dir"）</li>
                                                            <li><code>size</code>: 文件大小（字节）</li>
                                                            <li><code>modified</code>: 最后修改时间</li>
                                                        </ul>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>404</td>
                                        <td>目录不存在</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "目录不存在"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>403</td>
                                        <td>无权访问</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Access denied"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>405</td>
                                        <td>请求方法不允许</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Method not allowed"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5>使用示例：</h5>
                        <div class="code-block">
                            <pre><code class="language-python">import requests

# 设置 API Key
api_key = 'your_api_key_here'
headers = {'X-API-KEY': api_key}

# 1. 获取根目录列表
response = requests.get('http://your-domain/api/?action=list', headers=headers)
print("根目录内容:", response.json())

# 2. 获取指定目录列表
response = requests.get('http://your-domain/api/?action=list&path=documents', headers=headers)
print("documents目录内容:", response.json())

# 3. 获取子目录列表
response = requests.get('http://your-domain/api/?action=list&path=documents/images', headers=headers)
print("images子目录内容:", response.json())</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method post">POST</span>
                        创建分享链接
                    </h3>
                    <code>/api/?action=share</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>String</td>
                                    <td>是</td>
                                    <td>要分享的文件路径（相对于用户根目录）<br>
                                        例如：<code>documents/example.txt</code>
                                    </td>
                                </tr>
                                <tr>
                                    <td>duration</td>
                                    <td>String</td>
                                    <td>否</td>
                                    <td>
                                        分享有效期：<br>
                                        <ul>
                                            <li>数字：1-365之间的整数，表示有效天数</li>
                                            <li><code>permanent</code>：永久有效</li>
                                            <li>默认值：7（表示7天）</li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>创建成功</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "message": "分享创建成功",
    "share_code": "abcdef1234567890",
    "share_url": "http://your-domain/s.php?c=abcdef1234567890",
    "expires_at": "2024-03-27 10:30:00"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>200</td>
                                        <td>永久分享已存在</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "message": "已存在永久分享",
    "share_code": "abcdef1234567890",
    "share_url": "http://your-domain/s.php?c=abcdef1234567890",
    "expires_at": null
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>400</td>
                                        <td>参数错误</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "分享时长必须是1-365天的数字，或permanent表示永久"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>404</td>
                                        <td>文件不存在</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "文件不存在"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5>使用示例：</h5>
                        <div class="code-block">
                            <pre><code class="language-python">import requests

# 设置 API Key
api_key = 'your_api_key_here'
headers = {'X-API-KEY': api_key}

# 1. 创建7天有效期的分享（默认）
response = requests.post('http://your-domain/api/?action=share',
                        headers=headers,
                        data={'file': 'documents/example.txt'})
print("临时分享创建结果:", response.json())

# 2. 创建30天有效期的分享
response = requests.post('http://your-domain/api/?action=share',
                        headers=headers,
                        data={
                            'file': 'documents/example.txt',
                            'duration': '30'
                        })
print("30天分享创建结果:", response.json())

# 3. 创建永久分享
response = requests.post('http://your-domain/api/?action=share',
                        headers=headers,
                        data={
                            'file': 'documents/example.txt',
                            'duration': 'permanent'
                        })
print("永久分享创建结果:", response.json())</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method get">GET</span>
                        查询分享链接
                    </h3>
                    <code>/api/?action=list_shares</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>String</td>
                                    <td>否</td>
                                    <td>文件路径，用于查询特定文件的分享链接<br>
                                        例如：<code>documents/example.txt</code>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>请求成功</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "total": 2,
    "shares": [
        {
            "file_name": "example.txt",
            "file_path": "documents/example.txt",
            "share_code": "abcdef1234567890",
            "share_url": "http://your-domain/s.php?c=abcdef1234567890",
            "created_at": "2024-01-06 10:30:00",
            "expires_at": "2024-02-06 10:30:00",
            "status": "2024-02-06 10:30:00",
            "is_permanent": false
        },
        {
            "file_name": "image.jpg",
            "file_path": "photos/image.jpg",
            "share_code": "xyz9876543210",
            "share_url": "http://your-domain/s.php?c=xyz9876543210",
            "created_at": "2024-01-05 15:20:00",
            "expires_at": null,
            "status": "永久有效",
            "is_permanent": true
        }
    ]
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>401</td>
                                        <td>未授权</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Invalid API Key"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>405</td>
                                        <td>请求方法不允许</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Method not allowed"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>500</td>
                                        <td>服务器内部错误</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "错误信息"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>使用示例：</h5>
                        <div class="code-block">
                            <pre><code class="language-python">import requests

# 设置 API Key
api_key = 'your_api_key_here'
headers = {'X-API-KEY': api_key}

# 1. 获取所有分享链接
response = requests.get('http://your-domain/api/?action=list_shares', 
                       headers=headers)
print("所有分享链接:", response.json())

# 2. 获取特定文件的分享链接
file_path = 'documents/example.txt'
response = requests.get('http://your-domain/api/?action=list_shares&file=' + file_path,
                       headers=headers)
print("特定文件的分享链接:", response.json())</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method post">POST</span>
                        停止分享链接
                    </h3>
                    <code>/api/?action=stop_share</code>
                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>file</td>
                                    <td>String</td>
                                    <td>否*</td>
                                    <td>要停止分享的文件路径（相对于用户根目录）<br>
                                        例如：<code>documents/example.txt</code><br>
                                        <small class="text-muted">* file 和 share_code 至少需要提供一个</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>share_code</td>
                                    <td>String</td>
                                    <td>否*</td>
                                    <td>要停止的分享码<br>
                                        例如：<code>abcdef1234567890</code><br>
                                        <small class="text-muted">* file 和 share_code 至少需要提供一个</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>停止成功</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "message": "分享已停止",
    "total_deleted": 1,
    "deleted_shares": [
        {
            "file_name": "example.txt",
            "file_path": "documents/example.txt",
            "share_code": "abcdef1234567890",
            "share_url": "http://your-domain/s.php?c=abcdef1234567890",
            "created_at": "2024-01-06 10:30:00",
            "expires_at": "2024-02-06 10:30:00"
        }
    ]
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>400</td>
                                        <td>参数错误</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "文件路径或分享码至少需要提供一个"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>404</td>
                                        <td>分享不存在</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "未找到匹配的分享记录"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>405</td>
                                        <td>请求方法不允许</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Method not allowed"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>使用示例：</h5>
                        <div class="code-block">
                            <pre><code class="language-python">import requests

# 设置 API Key
api_key = 'your_api_key_here'
headers = {'X-API-KEY': api_key}

# 1. 通过文件路径停止分享
response = requests.post('http://your-domain/api/?action=stop_share',
                        headers=headers,
                        data={'file': 'documents/example.txt'})
print("停止文件分享结果:", response.json())

# 2. 通过分享码停止分享
response = requests.post('http://your-domain/api/?action=stop_share',
                        headers=headers,
                        data={'share_code': 'abcdef1234567890'})
print("停止特定分享码结果:", response.json())</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="endpoint">
                    <h3>
                        <span class="method post">POST</span>
                        创建邀请码
                    </h3>
                    <code>/api/?action=create_invite_codes</code>
                    <div class="description">
                        <p>生成指定数量的邀请码。此接口仅限管理员使用。每个邀请码有效期为7天。</p>
                    </div>

                    <div class="mt-3">
                        <h5>请求参数：</h5>
                        <table class="table param-table">
                            <thead>
                                <tr>
                                    <th width="15%">参数名</th>
                                    <th width="15%">类型</th>
                                    <th width="10%">必填</th>
                                    <th width="60%">说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>count</td>
                                    <td>Integer</td>
                                    <td>否</td>
                                    <td>要生成的邀请码数量<br>
                                        取值范围：1-100<br>
                                        默认值：1
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <h5>响应说明：</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="15%">状态码</th>
                                        <th width="25%">说明</th>
                                        <th width="60%">响应示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>200</td>
                                        <td>创建成功</td>
                                        <td>
<pre><code class="language-json">{
    "success": true,
    "message": "邀请码生成成功",
    "total": 2,
    "invite_codes": [
        {
            "code": "5A7B9C1D3E4F2A8B7C6D5E4F3A2B1C0D",
            "created_at": "2024-01-06 10:30:00"
        },
        {
            "code": "1F2E3D4C5B6A7988776655443322110F",
            "created_at": "2024-01-06 10:30:00"
        }
    ]
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>400</td>
                                        <td>参数错误</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "生成数量必须在1-100之间"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>401</td>
                                        <td>未授权</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Invalid API Key"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>403</td>
                                        <td>权限不足</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "只有管理员可以生成邀请码"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>405</td>
                                        <td>请求方法不允许</td>
                                        <td>
<pre><code class="language-json">{
    "error": "Method not allowed"
}</code></pre>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>500</td>
                                        <td>服务器错误</td>
                                        <td>
<pre><code class="language-json">{
    "success": false,
    "message": "错误信息"
}</code></pre>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>使用示例：</h5>
                        <div class="code-block">
                            <pre><code class="language-python">import requests

# 设置 API Key（需要管理员权限）
api_key = 'your_api_key_here'
headers = {'X-API-KEY': api_key}

# 1. 生成一个邀请码（默认数量）
response = requests.post('http://your-domain/api/?action=create_invite_codes',
                        headers=headers)
print("生成单个邀请码:", response.json())

# 2. 生成多个邀请码
response = requests.post('http://your-domain/api/?action=create_invite_codes',
                        headers=headers,
                        data={'count': 5})
print("生成5个邀请码:", response.json())</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h5>注意事项：</h5>
                        <ul>
                            <li>此接口仅限管理员使用</li>
                            <li>生成的邀请码为32位大写字母和数字的组合（0-9和A-F）</li>
                            <li>每个邀请码都是唯一的</li>
                            <li>邀请码有效期为7天</li>
                            <li>使用事务确保数据一致性，如果生成过程中出错会自动回滚</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-section card">
            <div class="card-body">
                <div class="header">
                    <h2><i class="fas fa-code"></i> 示例代码</h2>
                </div>

                <ul class="nav nav-tabs" id="codeTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="python-tab" data-bs-toggle="tab" href="#python">Python</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="php-tab" data-bs-toggle="tab" href="#php">PHP</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="curl-tab" data-bs-toggle="tab" href="#curl">cURL</a>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="python">
                        <div class="code-block">
                            <pre><code class="language-python">
import requests

# 设置 API Key 和服务器地址
api_key = 'your_api_key_here'
server_url = 'http://your-domain'
headers = {'X-API-KEY': api_key}

# 1. 上传文件（默认使用ask模式处理冲突）
def upload_file(file_path, conflict_action=None):
    with open(file_path, 'rb') as f:
        files = {'file': (file_path, f)}
        data = {}
        if conflict_action:
            data['conflict_action'] = conflict_action
        
        response = requests.post(f"{server_url}/api/?action=upload",
                               headers=headers,
                               files=files,
                               data=data)
        return response.json()

# 2. 处理文件冲突
def handle_upload_conflict(file_path):
    # 第一次尝试上传
    result = upload_file(file_path)
    
    # 检查是否发生冲突
    if not result['success'] and result.get('conflict'):
        print(f"文件 {result['filename']} 已存在")
        print("请选择处理方式：")
        print("1. 重命名")
        print("2. 覆盖")
        print("3. 取消")
        
        choice = input("请输入选择（1-3）：")
        
        if choice == '1':
            # 使用重命名模式重新上传
            result = upload_file(file_path, 'rename')
            if result['success']:
                print(f"文件已重命名为：{result['filename']}")
        elif choice == '2':
            # 使用覆盖模式重新上传
            result = upload_file(file_path, 'overwrite')
            if result['success']:
                print("文件已覆盖上传")
        else:
            print("已取消上传")
            return
    
    return result

# 使用示例
try:
    result = handle_upload_conflict('example.txt')
    if result and result['success']:
        print("上传成功！")
except Exception as e:
    print(f"上传失败：{str(e)}")

# 3. 获取文件列表
response = requests.get(f"{server_url}/api/?action=list",
                       headers=headers)
print("文件列表:", response.json())</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="php">
                        <div class="code-block">
                            <pre><code class="language-php">
// 设置 API Key
$api_key = 'your_api_key_here';
$server_url = 'http://your-domain';
$headers = ['X-API-KEY: ' . $api_key];

// 1. 上传文件函数
function upload_file($file_path, $conflict_action = null) {
    global $server_url, $headers;
    
    $ch = curl_init($server_url . '/api/?action=upload');
    $file = new CURLFile($file_path);
    $post_data = ['file' => $file];
    
    if ($conflict_action) {
        $post_data['conflict_action'] = $conflict_action;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);
    
    return $result;
}

// 2. 处理文件冲突
function handle_upload_conflict($file_path) {
    // 第一次尝试上传
    $result = upload_file($file_path);
    
    // 检查是否发生冲突
    if (!$result['success'] && isset($result['conflict'])) {
        echo "文件 {$result['filename']} 已存在\n";
        echo "请选择处理方式：\n";
        echo "1. 重命名\n";
        echo "2. 覆盖\n";
        echo "3. 取消\n";
        
        $choice = readline("请输入选择（1-3）：");
        
        if ($choice == '1') {
            // 使用重命名模式重新上传
            $result = upload_file($file_path, 'rename');
            if ($result['success']) {
                echo "文件已重命名为：{$result['filename']}\n";
            }
        } elseif ($choice == '2') {
            // 使用覆盖模式重新上传
            $result = upload_file($file_path, 'overwrite');
            if ($result['success']) {
                echo "文件已覆盖上传\n";
            }
        } else {
            echo "已取消上传\n";
            return null;
        }
    }
    
    return $result;
}

// 使用示例
try {
    $result = handle_upload_conflict('example.txt');
    if ($result && $result['success']) {
        echo "上传成功！\n";
    }
} catch (Exception $e) {
    echo "上传失败：" . $e->getMessage() . "\n";
}

// 3. 获取文件列表
$ch = curl_init($server_url . '/api/?action=list');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true
]);
$response = curl_exec($ch);
echo "文件列表: " . $response . "\n";
curl_close($ch);</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="curl">
                        <div class="code-block">
                            <pre><code class="language-bash">
# 设置 API Key
API_KEY="your_api_key_here"
SERVER_URL="http://your-domain"

# 1. 上传文件（默认使用ask模式）
curl -X POST "${SERVER_URL}/api/?action=upload" \
     -H "X-API-KEY: ${API_KEY}" \
     -F "file=@example.txt"

# 2. 上传文件（自动重命名）
curl -X POST "${SERVER_URL}/api/?action=upload" \
     -H "X-API-KEY: ${API_KEY}" \
     -F "file=@example.txt" \
     -F "conflict_action=rename"

# 3. 上传文件（覆盖已存在的文件）
curl -X POST "${SERVER_URL}/api/?action=upload" \
     -H "X-API-KEY: ${API_KEY}" \
     -F "file=@example.txt" \
     -F "conflict_action=overwrite"

# 4. 获取文件列表
curl -X GET "${SERVER_URL}/api/?action=list" \
     -H "X-API-KEY: ${API_KEY}"</code></pre>
                            <button class="copy-btn" onclick="copyCode(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <script>
        function copyCode(button) {
            // 获取代码块内容
            const codeBlock = button.closest('.code-block');
            const codeElement = codeBlock.querySelector('code');
            const textToCopy = codeElement.innerText || codeElement.textContent;

            // 创建临时文本区域
            const textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            textarea.style.position = 'fixed';  // 防止页面滚动
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            try {
                // 选择并复制文本
                textarea.select();
                document.execCommand('copy');
                
                // 更新按钮图标显示复制成功
                button.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            } catch (err) {
                console.error('复制失败:', err);
                button.innerHTML = '<i class="fas fa-times"></i>';
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            } finally {
                // 移除临时文本区域
                document.body.removeChild(textarea);
            }
        }

        // 初始化代码高亮
        document.addEventListener('DOMContentLoaded', (event) => {
            Prism.highlightAll();
        });
    </script>
</body>
</html> 