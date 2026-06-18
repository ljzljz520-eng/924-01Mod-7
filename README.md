# TemplateHub - 免费模板下载站点

> 基于 PHP + MySQL 的现代化免费模板下载站，支持图片上传、文件管理、后台 CRUD 操作。使用 Docker 一键部署，开箱即用。

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)

## ✨ 功能特点

### 前台功能
- 🎨 **现代化 UI** - 渐变背景、卡片设计、响应式布局
- 🔍 **搜索功能** - 支持按标题、标签搜索模板
- 🖼️ **图片预览** - 多图展示，点击查看大图
- 📄 **详情页面** - 完整展示模板信息、预览图集、下载链接
- 💾 **免费下载** - 一键下载模板资源

### 后台功能
- 🔐 **安全登录** - 密码加密存储，Session 管理
- ➕ **新增模板** - 填写标题、描述、文章、标签等信息
- ✏️ **编辑模板** - 修改模板所有信息
- 🗑️ **删除模板** - 快速删除不需要的模板
- 📤 **图片上传** - 支持多图上传、实时预览、单独删除
- 📦 **文件上传** - 支持上传 ZIP/RAR 等压缩包或填写外部下载链接

## 🛠️ 技术栈

- **前端**: HTML5, CSS3, JavaScript (原生)
- **后端**: PHP 8.0+
- **数据库**: MySQL 8.0
- **容器化**: Docker, Docker Compose
- **操作系统**: Ubuntu 22.04 (容器基础镜像)
- **包管理**: 阿里云 APT 镜像源

## 📋 环境要求

- [Docker](https://www.docker.com/get-started) 20.10+
- [Docker Compose](https://docs.docker.com/compose/install/) 2.0+
- 端口 8080 和 3307 未被占用

> **Windows 用户**: 推荐使用 [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/)
> **Mac 用户**: 推荐使用 [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop/)
> **Linux 用户**: 参考 [官方安装文档](https://docs.docker.com/engine/install/)

## 🚀 快速开始

### 1️⃣ 克隆项目

```bash
git clone <你的仓库地址>
cd 924
```

### 2️⃣ 启动服务

```bash
docker-compose up -d --build
```

首次启动需要拉取镜像和构建容器，大约需要 2-5 分钟。

### 3️⃣ 等待初始化

等待 15-20 秒让 MySQL 完成数据库初始化。

### 4️⃣ 访问网站

- **前台首页**: http://localhost:8080
- **后台管理**: http://localhost:8080/admin/login.php
- **默认账号**: `admin`
- **默认密码**: `admin123`

## 📖 使用指南

### 前台操作

1. **浏览模板**: 访问首页即可查看所有模板
2. **搜索模板**: 在搜索框输入关键词，点击"搜索模板"
3. **查看详情**: 点击模板卡片的"进入详情"按钮
4. **下载模板**: 在详情页或卡片上点击"免费下载"

### 后台操作

#### 登录后台
1. 访问 http://localhost:8080/admin/login.php
2. 输入账号 `admin` 和密码 `admin123`
3. 点击"登录"

#### 新增模板
1. 点击右上角"新增模板"
2. 填写模板信息：
   - **标题**（必填）
   - **简要描述**
   - **正文/文章**
   - **预览图片**: 点击"选择图片添加"上传图片（可多次添加）
   - **下载资源**: 上传文件或填写下载链接
   - **标签**: 用逗号分隔，如 `企业,响应式`
3. 点击"保存"

#### 编辑模板
1. 在列表页点击模板的"编辑"按钮
2. 修改需要更改的信息
3. 点击"保存"

#### 删除模板
1. 在列表页点击模板的"删除"按钮
2. 确认删除

#### 上传图片技巧
- 支持 JPG、PNG、GIF、WEBP 格式
- 单个文件最大 50MB
- 可多次点击"选择图片添加"按钮累加图片
- 每张图片都可以单独删除
- 已有图片也可以删除

## 📁 目录结构

```
924/
├── docker/                      # Docker 配置文件
│   ├── apache/
│   │   └── vhost.conf          # Apache 虚拟主机配置
│   └── mysql/
│       ├── entrypoint.sh       # MySQL 初始化脚本
│       ├── init.sql            # 数据库表结构和种子数据
│       └── mysql.cnf           # MySQL 配置
├── src/                         # 应用源代码
│   ├── admin/                  # 后台管理页面
│   │   ├── dashboard.php       # 后台首页（模板列表）
│   │   ├── delete.php          # 删除模板
│   │   ├── edit.php            # 新增/编辑模板
│   │   ├── login.php           # 登录页面
│   │   └── logout.php          # 退出登录
│   ├── includes/               # 公共函数库
│   │   ├── auth.php            # 认证相关
│   │   ├── bootstrap.php       # 应用初始化
│   │   ├── helpers.php         # 辅助函数
│   │   ├── template_repo.php   # 模板数据操作
│   │   └── upload.php          # 文件上传处理
│   ├── public/                 # Web 根目录
│   │   ├── assets/
│   │   │   └── styles.css      # 全局样式
│   │   ├── uploads/            # 上传文件存储目录
│   │   │   ├── images/         # 预览图片
│   │   │   └── files/          # 下载文件
│   │   ├── detail.php          # 模板详情页
│   │   └── index.php           # 前台首页
│   └── config.php              # 配置文件
├── .github/
│   └── copilot-instructions.md # GitHub Copilot 指令
├── .vscode/
│   └── tasks.json              # VS Code 任务配置
├── docker-compose.yml          # Docker Compose 配置
├── Dockerfile.web              # PHP/Apache 容器构建文件
├── Dockerfile.db               # MySQL 容器构建文件
└── README.md                   # 本文件
```

## ⚙️ 配置说明

### 修改默认账号密码

编辑 `docker-compose.yml` 文件：

```yaml
environment:
  - ADMIN_DEFAULT_USER=admin        # 修改为你的账号
  - ADMIN_DEFAULT_PASS=admin123     # 修改为你的密码
```

修改后需要重启容器：

```bash
docker-compose down
docker-compose up -d
```

### 修改端口

编辑 `docker-compose.yml` 文件：

```yaml
ports:
  - "8080:80"      # 改为 "你的端口:80"
```

例如改为 9000 端口：

```yaml
ports:
  - "9000:80"
```

### 数据库配置

默认数据库配置在 `docker-compose.yml` 中：

```yaml
environment:
  - DB_HOST=db
  - DB_NAME=templates_db
  - DB_USER=app_user
  - DB_PASS=app_pass
  - DB_PORT=3306
```

一般情况下不需要修改。

## 🔧 常用命令

### 查看容器状态
```bash
docker-compose ps
```

### 查看日志
```bash
# 查看 Web 服务日志
docker-compose logs -f web

# 查看数据库日志
docker-compose logs -f db
```

### 停止服务
```bash
docker-compose down
```

### 重启服务
```bash
docker-compose restart
```

### 完全重建（清除数据）
```bash
docker-compose down -v
docker-compose up -d --build
```

### 进入容器调试
```bash
# 进入 Web 容器
docker exec -it php-template-web bash

# 进入数据库容器
docker exec -it php-template-db bash
```

### 数据库操作
```bash
# 连接数据库
docker exec -it php-template-db mysql -uroot -prootpass templates_db

# 查看所有模板
docker exec php-template-db mysql -uapp_user -papp_pass templates_db -e "SELECT * FROM templates;"
```

## ❓ 常见问题

### Q1: 访问 localhost:8080 显示 500 错误？

**A**: 可能是数据库未初始化完成。

解决方法：
1. 等待 20 秒后再次访问
2. 检查容器日志：`docker-compose logs db`
3. 手动初始化数据库：
```bash
docker exec php-template-db mysql -uroot -prootpass -e "CREATE DATABASE IF NOT EXISTS templates_db; CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED BY 'app_pass'; GRANT ALL PRIVILEGES ON templates_db.* TO 'app_user'@'%'; FLUSH PRIVILEGES;"
docker exec php-template-db mysql -uroot -prootpass templates_db < docker/mysql/init.sql
docker-compose restart web
```

### Q2: 无法上传文件？

**A**: 检查上传目录权限。

```bash
docker exec php-template-web chown -R www-data:www-data /var/www/html/public/uploads
```

### Q3: 如何修改上传文件大小限制？

**A**: 编辑 `Dockerfile.web`，在 RUN 指令后添加：

```dockerfile
RUN echo "upload_max_filesize = 100M" >> /etc/php/8.0/apache2/php.ini \
    && echo "post_max_size = 100M" >> /etc/php/8.0/apache2/php.ini
```

然后重新构建：
```bash
docker-compose up -d --build
```

### Q4: 忘记管理员密码怎么办？

**A**: 删除管理员账户后重新登录会自动创建：

```bash
docker exec php-template-db mysql -uroot -prootpass templates_db -e "DELETE FROM admins;"
```

然后访问登录页使用默认密码登录。

### Q5: 如何备份数据？

**A**: 导出数据库：

```bash
docker exec php-template-db mysqldump -uroot -prootpass templates_db > backup.sql
```

恢复数据：

```bash
docker exec -i php-template-db mysql -uroot -prootpass templates_db < backup.sql
```

### Q6: 容器启动失败？

**A**: 检查端口是否被占用：

Windows:
```powershell
netstat -ano | findstr ":8080"
netstat -ano | findstr ":3307"
```

Linux/Mac:
```bash
lsof -i :8080
lsof -i :3307
```

如果被占用，修改 `docker-compose.yml` 中的端口号。

## 🔒 安全建议

1. **生产环境务必修改默认密码**
2. 不要暴露 MySQL 端口到公网（移除 `ports: - "3307:3306"`）
3. 定期更新依赖和镜像
4. 使用 HTTPS（配置反向代理如 Nginx）
5. 限制文件上传类型和大小
6. 定期备份数据库

## 📝 开发说明

### 本地开发

代码实时同步，修改后无需重启容器：

```bash
# src/ 目录已挂载到容器，直接编辑即可看到效果
```

### 添加新功能

1. 修改 `src/` 目录下的 PHP 文件
2. 需要修改数据库结构时：
   - 编辑 `docker/mysql/init.sql`
   - 重建数据库：`docker-compose down -v && docker-compose up -d --build`

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

MIT License

## 📮 联系方式

如有问题或建议，欢迎联系。

---

**祝您使用愉快！** 🎉
