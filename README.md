# 文件管理系统 (File Manager)

一个基于PHP开发的现代化Web文件管理系统，具有完整的用户管理、文件存储、分享和API功能。

## 🌟 主要特性

### 📁 文件管理
- **文件上传/下载**：支持拖拽上传，断点续传
- **在线预览**：支持图片、视频、文档等多种格式预览
- **在线编辑**：支持文本文件在线编辑，实时保存
- **文件分享**：临时和永久分享链接
- **文件夹操作**：创建、删除、重命名文件夹

### 👥 用户管理
- **用户注册/登录**：支持邀请码注册
- **权限管理**：管理员和普通用户权限分离
- **存储配额**：可配置的用户存储空间限制
- **个人设置**：密码修改、API密钥管理

### 🔌 API接口
- **RESTful API**：完整的文件操作API
- **API认证**：基于API Key的安全认证
- **文件上传API**：支持冲突处理（重命名/覆盖）
- **文件编辑API**：支持版本冲突检测
- **分享管理API**：创建、查询、停止分享

### 🛡️ 安全特性
- **文件路径验证**：防止目录遍历攻击
- **文件类型限制**：可配置的文件类型白名单
- **存储配额控制**：防止存储空间滥用
- **会话安全**：安全的会话管理配置

## 🚀 快速开始

### 环境要求
- PHP 7.4+
- SQLite 3
- Web服务器（Apache/Nginx）
- 至少100MB可用磁盘空间

### 安装步骤

1. **下载代码**
```bash
git clone https://github.com/dookcss/File-manager.git
cd File-manager
```

2. **配置Web服务器**
将项目文件放置在Web服务器可访问的目录中

3. **设置权限**
```bash
chmod 755 uploads/
chmod 644 database.db
```

4. **访问安装页面**
在浏览器中访问 `http://your-domain/install.php`

5. **完成安装**
按照安装向导完成系统初始化

### 默认管理员账户
- **用户名**：admin
- **密码**：admin123
- **注意**：首次登录后请立即修改密码

## 📖 使用指南

### 用户注册
1. 访问注册页面
2. 输入邀请码（由管理员生成）
3. 填写用户信息
4. 完成注册

### 文件上传
1. 在主页面点击"上传文件"
2. 选择文件或拖拽到上传区域
3. 选择冲突处理方式（重命名/覆盖/跳过）
4. 等待上传完成

### 文件分享
1. 在文件列表中选择要分享的文件
2. 点击"分享"按钮
3. 选择分享有效期（1-365天或永久）
4. 复制分享链接

### API使用
1. 在个人设置页面生成API Key
2. 在请求头中添加 `X-API-KEY: your_api_key`
3. 调用相应的API接口

## 🔧 配置说明

### 系统配置 (config.php)
```php
// 文件上传大小限制
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024); // 500MB

// 用户存储配额
define('USER_SPACE_QUOTA', 1 * 1024 * 1024 * 1024); // 1GB

// 管理员存储配额
define('ADMIN_SPACE_QUOTA', 5 * 1024 * 1024 * 1024); // 5GB
```

### 数据库配置
系统使用SQLite数据库，数据库文件位于项目根目录的 `database.db`

## 📚 API文档

### 认证方式
所有API请求需要在请求头中包含API Key：
```
X-API-KEY: your_api_key_here
```

### 主要接口

#### 文件上传
```http
POST /api/?action=upload
Content-Type: multipart/form-data
X-API-KEY: your_api_key

Parameters:
- file: 要上传的文件
- directory: 上传目录（可选）
- conflict_action: 冲突处理方式（ask/rename/overwrite）
```

#### 文件列表
```http
GET /api/?action=list&path=directory
X-API-KEY: your_api_key
```

#### 文件下载
```http
GET /api/?action=download&file=filepath
X-API-KEY: your_api_key
```

#### 文件编辑
```http
POST /api/?action=edit_file
Content-Type: application/x-www-form-urlencoded
X-API-KEY: your_api_key

Parameters:
- file: 文件路径
- content: 文件内容
- version: 文件版本（MD5）
```

#### 创建分享
```http
POST /api/?action=share
Content-Type: application/x-www-form-urlencoded
X-API-KEY: your_api_key

Parameters:
- file: 文件路径
- duration: 有效期（天数或permanent）
```

详细API文档请访问：`/api/docs/`

## 🛠️ 开发指南

### 项目结构
```
File-manager/
├── api/                    # API接口
│   ├── docs/              # API文档
│   ├── edit_file.php      # 文件编辑API
│   └── index.php          # 主要API接口
├── uploads/               # 用户文件存储目录
├── config.php            # 系统配置文件
├── functions.php         # 核心功能函数
├── database.php          # 数据库操作类
├── index.php             # 主页面
├── login.php             # 登录页面
├── register.php          # 注册页面
├── admin.php             # 管理员面板
└── ...                   # 其他功能文件
```

### 核心类说明

#### Database类
单例模式的数据库连接管理类
```php
$db = Database::getInstance()->getConnection();
```

#### UserManager类
用户管理功能类
```php
$userManager = new UserManager();
$user = $userManager->verifyUser($username, $password);
```

### 扩展开发
1. 在 `functions.php` 中添加新的功能函数
2. 在 `api/index.php` 中添加新的API接口
3. 在数据库中添加新的表结构
4. 在前端页面中添加新的功能模块

## 🔒 安全建议

### 生产环境部署
1. **修改默认密码**：安装后立即修改管理员密码
2. **配置HTTPS**：使用SSL证书保护数据传输
3. **文件权限**：正确设置文件和目录权限
4. **定期备份**：定期备份数据库和用户文件
5. **日志监控**：监控系统日志，及时发现异常

### 安全配置
```php
// 禁用错误显示
ini_set('display_errors', 0);

// 启用安全会话
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
```

## 📄 许可证

本项目采用 Apache License 2.0 许可证。

## 🤝 贡献指南

1. Fork 本项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

## 📞 支持与反馈

如果您在使用过程中遇到问题或有改进建议，请：

1. 查看 [Issues](https://github.com/dookcss/File-manager/issues) 页面
2. 创建新的 Issue 描述问题
3. 提供详细的错误信息和复现步骤

## 🔄 更新日志

### v1.0.0 (2024-01-06)
- ✨ 初始版本发布
- 🎉 完整的文件管理功能
- 🔐 用户认证和权限管理
- 📡 RESTful API接口
- 🎨 现代化Web界面
- 📱 响应式设计，支持移动端

---

**感谢使用文件管理系统！** 🎉