# 文件管理系统 (File Manager)

一个基于PHP开发的现代化Web文件管理系统，具有完整的用户管理、文件存储、分享和API功能。

## 🌟 主要特性

### 📁 文件管理
- **文件上传/下载**：支持拖拽上传，断点续传
- **在线预览**：支持图片、视频、文档等多种格式预览
- **在线编辑**：支持文本文件在线编辑，实时保存
- **文件分享**：临时和永久分享链接
- **文件夹操作**：创建、删除、重命名文件夹
- **文件搜索**：支持文件名搜索功能

### 👥 用户管理
- **用户注册/登录**：支持邀请码注册
- **权限管理**：管理员和普通用户权限分离
- **存储配额**：可配置的用户存储空间限制
- **个人设置**：密码修改、API密钥管理
- **用户统计**：存储使用情况统计

### 🔌 API接口
- **RESTful API**：完整的文件操作API
- **API认证**：基于API Key的安全认证
- **文件上传API**：支持冲突处理（重命名/覆盖）
- **文件编辑API**：支持版本冲突检测
- **分享管理API**：创建、查询、停止分享
- **批量操作**：支持批量文件操作

### 🛡️ 安全特性
- **文件路径验证**：防止目录遍历攻击
- **文件类型限制**：可配置的文件类型白名单
- **存储配额控制**：防止存储空间滥用
- **会话安全**：安全的会话管理配置
- **XSS防护**：输入输出转义
- **CSRF防护**：跨站请求伪造防护

## 🚀 快速开始

### 环境要求
- **PHP**: 7.4 或更高版本
- **数据库**: SQLite 3
- **Web服务器**: Apache/Nginx
- **磁盘空间**: 至少100MB可用空间
- **扩展要求**: PDO, SQLite3, GD, cURL

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
# Linux/Mac
chmod 755 uploads/
chmod 644 database.db

# Windows (如果使用Apache)
# 确保uploads目录可写
```

4. **访问系统**
在浏览器中访问 `http://your-domain/`

5. **完成安装**
按照安装向导完成系统初始化

### 默认管理员账户
- **用户名**: `admin`
- **密码**: `admin123`
- **注意**: 首次登录后请立即修改密码

## 📖 使用指南

### 用户注册
1. 访问注册页面 `/register.php`
2. 输入邀请码（由管理员生成）
3. 填写用户信息（用户名、密码、邮箱）
4. 完成注册并登录

### 文件上传
1. 在主页面点击"上传文件"按钮
2. 选择文件或拖拽到上传区域
3. 选择冲突处理方式：
   - **重命名**: 自动添加序号
   - **覆盖**: 替换已存在的文件
   - **跳过**: 保留原文件
4. 等待上传完成

### 文件分享
1. 在文件列表中选择要分享的文件
2. 点击"分享"按钮
3. 选择分享有效期：
   - **临时分享**: 1-365天
   - **永久分享**: 无时间限制
4. 复制分享链接并分享给他人

### API使用
1. 在个人设置页面生成API Key
2. 在请求头中添加认证信息：
   ```
   X-API-KEY: your_api_key_here
   ```
3. 调用相应的API接口

## 🔧 配置说明

### 系统配置 (config.php)
```php
// 文件上传大小限制 (500MB)
define('MAX_UPLOAD_SIZE', 500 * 1024 * 1024);

// 用户存储配额 (1GB)
define('USER_SPACE_QUOTA', 1 * 1024 * 1024 * 1024);

// 管理员存储配额 (5GB)
define('ADMIN_SPACE_QUOTA', 5 * 1024 * 1024 * 1024);

// 系统总配额 (10GB)
define('SYSTEM_TOTAL_QUOTA', 10 * 1024 * 1024 * 1024);

// 时区设置
date_default_timezone_set('Asia/Shanghai');
```

### 数据库配置
系统使用SQLite数据库，数据库文件位于项目根目录的 `database.db`

### 文件类型配置
```php
// 支持预览的文件类型
define('PREVIEW_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
    'txt', 'md', 'php', 'js', 'css', 'html',
    'pdf', 'doc', 'docx', 'xls', 'xlsx'
]);

// 支持视频播放的文件类型
define('VIDEO_EXTENSIONS', [
    'mp4', 'webm', 'ogv', 'avi', 'mkv', 'mov'
]);
```

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

#### 查询分享
```http
GET /api/?action=list_shares
X-API-KEY: your_api_key
```

#### 停止分享
```http
POST /api/?action=stop_share
Content-Type: application/x-www-form-urlencoded
X-API-KEY: your_api_key

Parameters:
- file: 文件路径（可选）
- share_code: 分享码（可选）
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
├── profile.php           # 用户个人设置
├── share.php             # 文件分享页面
├── preview.php           # 文件预览页面
├── video.php             # 视频播放页面
├── download.php          # 文件下载处理
├── upload_handler.php    # 文件上传处理
├── install.php           # 系统安装向导
├── init_db.php           # 数据库初始化
├── invite_codes.php      # 邀请码管理
├── users.php             # 用户管理
├── user_files.php        # 用户文件管理
├── change_password.php   # 密码修改
├── reset_password.php    # 密码重置
├── delete_account.php    # 账号注销
├── delete_user.php       # 用户删除
├── create_file.php       # 文件创建
├── create_folder.php     # 文件夹创建
├── download_remote.php   # 远程文件下载
├── update_quota.php      # 配额更新
├── update_default_quota.php # 默认配额设置
├── s.php                 # 分享链接处理
├── .gitignore           # Git忽略文件
└── README.md            # 项目说明文档
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
1. **添加新功能**：在 `functions.php` 中添加新的功能函数
2. **扩展API**：在 `api/index.php` 中添加新的API接口
3. **数据库扩展**：在数据库中添加新的表结构
4. **前端扩展**：在前端页面中添加新的功能模块
5. **安全增强**：添加新的安全验证机制

## 🔒 安全建议

### 生产环境部署
1. **修改默认密码**：安装后立即修改管理员密码
2. **配置HTTPS**：使用SSL证书保护数据传输
3. **文件权限**：正确设置文件和目录权限
4. **定期备份**：定期备份数据库和用户文件
5. **日志监控**：监控系统日志，及时发现异常
6. **防火墙配置**：配置Web服务器防火墙规则
7. **定期更新**：及时更新系统和依赖包

### 安全配置
```php
// 禁用错误显示
ini_set('display_errors', 0);

// 启用安全会话
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // 仅HTTPS

// 设置会话超时
ini_set('session.gc_maxlifetime', 3600); // 1小时
```

### 文件安全
- 限制上传文件类型
- 验证文件内容
- 扫描恶意文件
- 设置文件大小限制

## 📄 许可证

本项目采用 Apache License 2.0 许可证。

## 🤝 贡献指南

1. Fork 本项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

### 开发规范
- 遵循PSR-4自动加载规范
- 使用有意义的变量和函数名
- 添加适当的注释和文档
- 编写单元测试
- 确保代码安全性

## 📞 支持与反馈

如果您在使用过程中遇到问题或有改进建议，请：

1. 查看 [Issues](https://github.com/dookcss/File-manager/issues) 页面
2. 创建新的 Issue 描述问题
3. 提供详细的错误信息和复现步骤
4. 包含系统环境信息（PHP版本、操作系统等）

## 🔄 更新日志

### v1.0.0 (2024-01-06)
- ✨ 初始版本发布
- 🎉 完整的文件管理功能
- 🔐 用户认证和权限管理
- 📡 RESTful API接口
- 🎨 现代化Web界面
- 📱 响应式设计，支持移动端
- 🔒 多层安全防护机制
- 📊 存储配额管理系统
- 🔗 文件分享功能
- 📝 在线文件编辑
- 🎬 多媒体文件预览
- 📋 完整的API文档

### 已知问题
- ⚠️ 找回密码功能待完善
- ⚠️ 部分高级功能需要进一步测试
- ⚠️ 移动端体验需要优化
- ⚠️ 大文件上传性能需要优化
- ⚠️ 文件版本控制功能缺失

### 计划功能
由于个人精力有限暂无更新计划，若有需求可自行二次开发

### 二次开发建议
如果您需要扩展功能，可以考虑以下方向：

#### 🔧 功能扩展
- **文件版本控制**：实现文件历史版本管理
- **文件加密**：支持文件加密存储和传输
- **批量操作**：优化批量文件操作体验
- **文件同步**：支持多设备文件同步
- **多语言支持**：添加国际化支持
- **插件系统**：实现可扩展的插件架构

#### 🎨 界面优化
- **主题系统**：支持多种界面主题
- **拖拽上传**：优化拖拽上传体验
- **文件预览**：增强文件预览功能
- **移动端适配**：优化移动端操作体验
- **快捷键支持**：添加键盘快捷键

#### 🔒 安全增强
- **文件扫描**：集成病毒扫描功能
- **访问控制**：细粒度权限控制
- **审计日志**：完整的操作日志记录
- **数据备份**：自动备份和恢复功能
- **SSL证书管理**：自动SSL证书配置

#### 📊 性能优化
- **CDN集成**：支持CDN加速
- **缓存系统**：实现智能缓存机制
- **数据库优化**：支持MySQL/PostgreSQL
- **异步处理**：大文件异步上传处理
- **负载均衡**：支持多服务器部署

#### 🔌 API增强
- **WebSocket支持**：实时文件操作通知
- **GraphQL接口**：提供GraphQL API
- **OAuth认证**：支持第三方登录
- **Webhook支持**：文件操作事件通知
- **API限流**：智能API访问控制

### 贡献代码
欢迎提交Pull Request来改进项目：

1. **代码规范**：遵循PSR-4和PSR-12规范
2. **测试覆盖**：新功能需要包含单元测试
3. **文档更新**：更新相关文档和API说明
4. **安全审查**：确保代码安全性
5. **性能测试**：验证性能影响

### 社区支持
- 📧 **邮箱支持**：如有问题可发送邮件
- 💬 **讨论区**：在GitHub Discussions中交流
- 🐛 **问题反馈**：通过Issues报告问题
- 💡 **功能建议**：提交功能改进建议

---

**感谢使用文件管理系统！** 🎉

如有问题或建议，欢迎提交 Issue 或 Pull Request。