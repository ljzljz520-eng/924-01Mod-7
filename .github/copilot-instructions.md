# AI Coding Agent Instructions - TemplateHub 项目

## 项目概览

这是一个基于 **PHP 8.1 + MySQL 8.0** 的免费模板下载网站，使用 **Docker** 容器化部署。项目采用传统 LAMP 架构，**无需构建工具**，代码修改后立即生效。

**核心特性:**
- 前台：模板列表、搜索、详情页、多图预览
- 后台：管理员登录、模板 CRUD、多图上传、文件上传
- 部署：Docker Compose 一键启动（Web + DB 双容器）
- 镜像：Ubuntu 22.04 基础镜像，使用阿里云 APT 源

## 关键架构决策

### 1. 无构建系统 - 零编译工作流
```
❌ 没有 npm/webpack/composer build
✅ 直接编辑 .php/.css/.html 文件
✅ 代码通过 Docker 卷挂载实时同步
✅ 刷新浏览器即可看到更改
```

**影响:** 修改任何代码无需重启容器，直接刷新浏览器即可。

### 2. Docker 优先开发模式
```bash
# 所有操作都在容器内进行
docker-compose up -d       # 启动服务
docker exec -it php-template-web bash  # 调试 Web 容器
docker exec -it php-template-db mysql -uroot -prootpass templates_db  # 调试数据库
```

**影响:** 不要建议在主机安装 PHP/MySQL，所有开发/调试都在容器内执行。

### 3. 自定义 MySQL 初始化流程
```bash
docker/mysql/entrypoint.sh  # 自定义初始化脚本（不使用官方镜像）
docker/mysql/init.sql       # 表结构和种子数据
```

**关键模式:**
- 自定义 `entrypoint.sh` 脚本处理数据库初始化
- 使用 `==>` 前缀标记日志输出便于调试
- 初始化时启动临时 MySQL 服务器，完成后关闭
- 通过环境变量注入配置（MYSQL_DATABASE, MYSQL_USER 等）

### 4. Apache php_value 配置覆盖
```apache
# docker/apache/vhost.conf
<VirtualHost *:80>
    php_value upload_max_filesize 100M
    php_value post_max_size 100M
    php_value memory_limit 256M
    php_value max_execution_time 300
</VirtualHost>
```

**重要:** 修改 PHP 上传限制时，**必须使用 Apache vhost.conf 的 php_value 指令**，不要尝试修改 php.ini（容器内 sed 修改不生效）。

### 5. 文件上传双轨制
```
src/public/uploads/
├── images/    # 预览图片（多图上传，JSON 数组存储）
└── files/     # 下载文件（ZIP/RAR 压缩包）
```

**数据库存储模式:**
- `preview_images` 字段：TEXT 类型，存储 JSON 数组 `["url1", "url2"]`
- 使用 `json_encode()` 和 `json_decode()` 处理
- 前端上传时使用 DataTransfer API 累加文件

### 6. Session 认证系统
```php
// 无 JWT/Token，使用传统 Session
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];

// 密码哈希存储
password_hash($password, PASSWORD_BCRYPT);
password_verify($input, $hash);

// 默认管理员自动创建
ensure_default_admin();  // 从环境变量读取 ADMIN_DEFAULT_USER/PASS
```

## 代码模式和最佳实践

### 数据库操作模式

#### ✅ 正确：使用 PDO 准备语句
```php
function upsert_template(array $data, ?int $id = null): void {
    $pdo = db();  // 使用全局单例
    $images = array_values(array_filter($data['preview_images']));
    $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);

    if ($id === null) {
        // 插入：不包含 :id 参数
        $stmt = $pdo->prepare('INSERT INTO templates (title, description, preview_images) VALUES (:t, :d, :p)');
        $stmt->execute([':t' => $data['title'], ':d' => $data['description'], ':p' => $imagesJson]);
    } else {
        // 更新：包含 :id 参数
        $stmt = $pdo->prepare('UPDATE templates SET title=:t, description=:d, preview_images=:p WHERE id=:id');
        $stmt->execute([':t' => $data['title'], ':d' => $data['description'], ':p' => $imagesJson, ':id' => $id]);
    }
}
```

#### ❌ 错误：混淆 INSERT/UPDATE 参数
```php
// ❌ UPDATE 语句缺少 :id 参数会报错
$stmt = $pdo->prepare('UPDATE templates SET title=:t WHERE id=:id');
$stmt->execute([':t' => $data['title']]);  // 缺少 :id
```

**规则:** INSERT 和 UPDATE 必须分开写，参数数组必须完整匹配 SQL 占位符。

### 文件上传模式

#### 多图上传（前端 DataTransfer API）
```javascript
let newImageFiles = [];  // 全局文件数组

function addNewImages(input) {
    Array.from(input.files).forEach(file => {
        newImageFiles.push(file);  // 累加新文件
    });
}

function removeNewImage(btn) {
    const index = parseInt(btn.closest('.preview-item').getAttribute('data-index'));
    newImageFiles[index] = null;  // 标记删除，不改变索引
}

// 表单提交时过滤有效文件
const validFiles = newImageFiles.filter(f => f !== null);
validFiles.forEach(file => {
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    input.files = dataTransfer.files;  // 创建真实文件输入
});
```

**关键点:**
- 使用 `DataTransfer API` 累加文件，不使用数组输入 `<input multiple>`
- 删除文件时设为 `null` 保持索引不变
- 提交前过滤掉 `null` 值

#### 后端文件处理
```php
// 处理多文件上传
if (!empty($_FILES['new_images']['name'][0])) {
    foreach ($_FILES['new_images']['name'] as $key => $name) {
        if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $_FILES['new_images']['name'][$key],
                'tmp_name' => $_FILES['new_images']['tmp_name'][$key],
                'error' => $_FILES['new_images']['error'][$key],
                'size' => $_FILES['new_images']['size'][$key],
            ];
            $uploaded = handle_file_upload($file, 'images');
            if ($uploaded) {
                $preview_images[] = $uploaded;
            }
        }
    }
}
```

### 表单数据保留模式（编辑模式）
```php
// ✅ 正确：保留已有数据
$download_url = trim($_POST['download_url'] ?? '');
if (!empty($_FILES['download_file']['name'])) {
    $uploaded = handle_file_upload($_FILES['download_file'], 'files');
    if ($uploaded) {
        $download_url = $uploaded;  // 新上传覆盖
    }
}
// 如果没有新上传且没有填写 URL，保留原有值
if (empty($download_url) && $template) {
    $download_url = $template['download_url'];
}
```

### XSS 防护模式
```php
// includes/helpers.php
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// 所有输出都使用 e() 函数
<h1><?php echo e($template['title']); ?></h1>
<input value="<?php echo e($template['tags']); ?>">
```

**规则:** 任何用户输入的内容输出时都必须使用 `e()` 转义。

## 目录结构约定

```
src/
├── config.php              # 环境变量配置（DB_HOST, ADMIN_DEFAULT_USER 等）
├── includes/               # 共享函数库
│   ├── bootstrap.php       # 应用初始化（session_start, PDO 单例）
│   ├── auth.php            # 认证函数（login, logout, require_login）
│   ├── template_repo.php   # 数据库 CRUD
│   ├── upload.php          # 文件上传处理
│   └── helpers.php         # XSS 防护 e() 函数
├── public/                 # Web 根目录（DocumentRoot）
│   ├── index.php           # 前台首页
│   ├── detail.php          # 详情页
│   ├── assets/styles.css   # 全局样式
│   └── uploads/            # 上传文件存储
└── admin/                  # 后台管理（通过 Apache Alias 挂载）
    ├── login.php           # 登录页
    ├── dashboard.php       # 后台首页（模板列表）
    ├── edit.php            # 新增/编辑模板
    ├── delete.php          # 删除模板
    └── logout.php          # 退出登录
```

**规则:**
- `public/` 是 Web 根目录，所有前台页面放这里
- `admin/` 通过 Apache Alias 挂载到 `/admin` 路径
- `includes/` 不可直接访问，仅通过 `require_once` 引入

## 常见任务工作流

### 添加新功能
1. 确定功能位置（前台 public/ 还是后台 admin/）
2. 如需数据库修改，编辑 `docker/mysql/init.sql`
3. 在 `includes/` 中添加业务逻辑函数
4. 创建对应 `.php` 页面文件
5. **无需重启容器**，直接刷新浏览器测试

### 修改数据库结构
```bash
# 1. 编辑 init.sql
vim docker/mysql/init.sql

# 2. 重建数据库（会清除所有数据）
docker-compose down -v
docker-compose up -d --build

# 3. 等待 20 秒初始化完成
```

### 修改 PHP 配置
```apache
# 编辑 docker/apache/vhost.conf
<VirtualHost *:80>
    php_value upload_max_filesize 200M  # 修改上传限制
    php_value post_max_size 200M
</VirtualHost>
```

```bash
# 重启容器应用配置
docker-compose restart web
```

### 调试数据库问题
```bash
# 查看 MySQL 初始化日志
docker-compose logs db | grep "==>"

# 连接数据库
docker exec -it php-template-db mysql -uroot -prootpass templates_db

# 查询数据
mysql> SELECT * FROM templates;
mysql> SHOW TABLES;
```

### 调试 PHP 错误
```bash
# 查看 Apache 错误日志
docker exec php-template-web tail -f /var/log/apache2/error.log

# 进入容器执行 PHP
docker exec -it php-template-web bash
php -v
php -r "phpinfo();" | grep upload
```

## 环境变量配置

所有配置通过 `docker-compose.yml` 注入：

```yaml
environment:
  - DB_HOST=db                    # 数据库主机（容器名）
  - DB_NAME=templates_db          # 数据库名
  - DB_USER=app_user              # 应用数据库用户
  - DB_PASS=app_pass              # 应用数据库密码
  - ADMIN_DEFAULT_USER=admin      # 默认管理员账号
  - ADMIN_DEFAULT_PASS=admin123   # 默认管理员密码
```

**读取方式:**
```php
// src/config.php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME') ?: 'templates_db',
    ],
    'admin' => [
        'username' => getenv('ADMIN_DEFAULT_USER') ?: 'admin',
        'password' => getenv('ADMIN_DEFAULT_PASS') ?: 'changeme',
    ],
];
```

## 故障排查指南

### 问题 1: 访问 localhost:8080 显示 500 错误
**原因:** 数据库未初始化完成或连接失败

**解决:**
```bash
# 检查容器状态
docker-compose ps

# 查看数据库日志
docker-compose logs db | tail -30

# 手动初始化数据库
docker exec php-template-db mysql -uroot -prootpass -e "
CREATE DATABASE IF NOT EXISTS templates_db;
CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED BY 'app_pass';
GRANT ALL PRIVILEGES ON templates_db.* TO 'app_user'@'%';
FLUSH PRIVILEGES;"
docker exec -i php-template-db mysql -uroot -prootpass templates_db < docker/mysql/init.sql
```

### 问题 2: 文件上传失败或超过大小限制
**原因:** PHP upload_max_filesize 和 post_max_size 限制

**解决:**
```apache
# 编辑 docker/apache/vhost.conf
php_value upload_max_filesize 200M
php_value post_max_size 200M
```

```bash
# 重启容器
docker-compose restart web
```

**验证:**
```bash
docker exec php-template-web php -r "echo ini_get('upload_max_filesize');"
```

### 问题 3: SQL 执行报错 "Parameter count mismatch"
**原因:** PDO 占位符与 execute() 参数不匹配

**解决:**
```php
// ❌ 错误：UPDATE 缺少 :id 参数
$stmt = $pdo->prepare('UPDATE templates SET title=:t WHERE id=:id');
$stmt->execute([':t' => $title]);  // 缺少 :id

// ✅ 正确：参数完整匹配
$stmt->execute([':t' => $title, ':id' => $id]);
```

### 问题 4: 多图上传后保存无效
**原因:** 前端文件未正确传递到表单

**检查点:**
1. 确认使用 `DataTransfer API` 构造文件对象
2. 过滤掉 `null` 值：`newImageFiles.filter(f => f !== null)`
3. 表单 enctype 必须为 `multipart/form-data`
4. 后端检查 `$_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK`

## 开发工具集成

### VS Code 任务配置
```json
// .vscode/tasks.json
{
  "tasks": [
    {
      "label": "docker: up",
      "type": "shell",
      "command": "docker-compose up -d --build"
    },
    {
      "label": "docker: down",
      "type": "shell",
      "command": "docker-compose down"
    }
  ]
}
```

**使用:** `Ctrl+Shift+P` → `Tasks: Run Task` → 选择任务

### 推荐扩展
- PHP Intelephense（PHP 智能提示）
- Docker（容器管理）
- MySQL（数据库管理）

## 代码审查清单

修改代码前检查：
- [ ] 所有用户输入都使用 `e()` 转义输出
- [ ] PDO 使用准备语句，参数数组完整
- [ ] INSERT/UPDATE 逻辑分开，不混用参数
- [ ] 文件上传检查 `UPLOAD_ERR_OK` 和文件类型
- [ ] 修改数据库后重建容器 `docker-compose down -v && up`
- [ ] 修改 Apache 配置后重启 `docker-compose restart web`
- [ ] 测试时检查容器日志 `docker-compose logs -f`

## AI Agent 工作准则

1. **无构建流程** - 永远不要建议运行 npm install/build/composer install
2. **Docker 优先** - 所有命令都使用 `docker exec` 在容器内执行
3. **实时同步** - 修改代码后直接刷新浏览器，不要重启容器
4. **环境变量** - 配置通过 docker-compose.yml 环境变量注入
5. **日志调试** - 出错时先查看容器日志 `docker-compose logs`
6. **数据库操作** - 使用 PDO 准备语句，INSERT/UPDATE 分开处理
7. **文件上传** - 使用 DataTransfer API，检查 php_value 配置
8. **安全防护** - 所有输出使用 e() 转义，密码使用 password_hash

## 快速命令参考

```bash
# 启动项目
docker-compose up -d --build

# 停止项目
docker-compose down

# 完全重建（清除数据）
docker-compose down -v && docker-compose up -d --build

# 查看日志
docker-compose logs -f web
docker-compose logs -f db

# 进入容器
docker exec -it php-template-web bash
docker exec -it php-template-db bash

# 数据库操作
docker exec php-template-db mysql -uroot -prootpass templates_db
docker exec php-template-db mysql -uroot -prootpass templates_db -e "SELECT * FROM templates;"

# 重启单个服务
docker-compose restart web
docker-compose restart db

# 检查 PHP 配置
docker exec php-template-web php -i | grep upload

# 修改文件权限
docker exec php-template-web chown -R www-data:www-data /var/www/html/public/uploads
```

---

**记住:** 这是一个零构建、Docker 优先的传统 LAMP 项目。所有操作都在容器内进行，代码修改立即生效。
