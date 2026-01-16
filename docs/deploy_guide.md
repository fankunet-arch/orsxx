# ORS 部署指南

## 系统概述

ORS (Opening Roadmap System) 是一个开业路线图系统，用于管理新店开业过程中的任务、采购、供应商等信息。

## 目录结构

```
/ (PROJECT_ROOT)
├── app/
│   └── ors/
│       ├── api/                 # JSON API（供两套入口调用）
│       ├── config_ors/
│       │   └── env_ors.php      # 环境配置文件
│       ├── lib/                 # 核心库
│       ├── views/               # 视图模板（预留）
│       └── bootstrap.php        # 初始化文件
├── dc_html/
│   └── ors/
│       ├── api/                 # API 桥接文件
│       ├── css/                 # 手机端样式
│       ├── js/                  # 手机端脚本
│       ├── views/               # 手机端视图
│       ├── index.php            # 入口#1：手机 Capture
│       └── ap/
│           ├── css/             # 控制室样式
│           ├── js/              # 控制室脚本
│           ├── views/           # 控制室视图
│           └── index.php        # 入口#2：控制室 Organize
└── docs/
    ├── db_schema.sql            # 数据库建表脚本
    ├── deploy_guide.md          # 本部署指南
    ├── acceptance_checklist.md  # 验收清单
    └── env_ors.example.php      # 示例配置文件
```

## 两个入口 URL

| 入口 | URL | 用途 | 目标用户 |
|------|-----|------|---------|
| 手机端 Capture | `http://<域名>/ors/` | 现场快速录入 | staff/admin |
| 控制室 Organize | `http://<域名>/ors/ap` | 晚间整理管理 | admin only |

## 部署步骤

### 1. 环境要求

- PHP 7.4+ (推荐 8.0+)
- MySQL 5.7+ / MariaDB 10.3+
- Nginx / Apache
- PDO MySQL 扩展

### 2. 数据库配置

```bash
# 创建数据库
mysql -u root -p
CREATE DATABASE ors_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ors_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ors_db.* TO 'ors_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 导入数据库结构
mysql -u ors_user -p ors_db < docs/db_schema.sql
```

### 3. 配置文件

复制示例配置文件并修改：

```bash
cp docs/env_ors.example.php app/ors/config_ors/env_ors.php
```

编辑 `app/ors/config_ors/env_ors.php`：

```php
define('ORS_DB_HOST', 'localhost');
define('ORS_DB_PORT', '3306');
define('ORS_DB_NAME', 'ors_db');
define('ORS_DB_USER', 'ors_user');
define('ORS_DB_PASS', 'your_password');
define('ORS_DEBUG', false);  // 生产环境设为 false
```

### 4. Nginx 配置示例

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html;  # 指向项目根目录

    index index.php index.html;

    # 手机端入口
    location /ors/ {
        alias /var/www/html/dc_html/ors/;
        try_files $uri $uri/ /dc_html/ors/index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    # 控制室入口
    location /ors/ap {
        alias /var/www/html/dc_html/ors/ap/;
        try_files $uri $uri/ /dc_html/ors/ap/index.php?$query_string;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    # 禁止直接访问 app 目录（安全隔离）
    location /app/ {
        deny all;
        return 403;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Apache 配置示例

在项目根目录创建 `.htaccess`：

```apache
# 禁止直接访问 app 目录
<Directory "app">
    Order deny,allow
    Deny from all
</Directory>

# 重写规则
RewriteEngine On
RewriteBase /

# ORS 手机端
RewriteRule ^ors/?$ /dc_html/ors/index.php [L]
RewriteRule ^ors/(.*)$ /dc_html/ors/$1 [L]

# ORS 控制室
RewriteRule ^ors/ap/?$ /dc_html/ors/ap/index.php [L]
RewriteRule ^ors/ap/(.*)$ /dc_html/ors/ap/$1 [L]
```

### 6. 目录权限

```bash
# 确保 Web 服务器可读
chown -R www-data:www-data /var/www/html/
chmod -R 755 /var/www/html/

# 确保配置文件安全
chmod 640 app/ors/config_ors/env_ors.php
```

### 7. 默认账号

系统初始化后包含两个测试账号：

| 用户名 | 密码 | 角色 | 权限 |
|--------|------|------|------|
| admin | password | admin | 控制室全功能 |
| staff | password | staff | 仅手机端 Capture |

**重要：上线前请务必修改默认密码！**

修改密码的 SQL：

```sql
-- 生成新密码哈希（PHP）
-- echo password_hash('your_new_password', PASSWORD_DEFAULT);

UPDATE ors_user SET password_hash = '$2y$10$...' WHERE username = 'admin';
UPDATE ors_user SET password_hash = '$2y$10$...' WHERE username = 'staff';
```

## 安全注意事项

1. **核心代码隔离**：`app/ors/` 目录必须禁止 URL 直接访问
2. **配置文件保护**：`env_ors.php` 不可通过 URL 访问
3. **调试模式**：生产环境必须设置 `ORS_DEBUG = false`
4. **HTTPS**：建议启用 HTTPS
5. **密码修改**：上线前修改默认账号密码

## 验证部署

1. 访问 `/ors/` 应显示手机端登录页
2. 访问 `/ors/ap` 应显示控制室登录页
3. 访问 `/app/ors/bootstrap.php` 应返回 403/404
4. 使用默认账号可以正常登录

## 常见问题

### Q: 页面空白或 500 错误
A: 检查 PHP 错误日志，确认数据库连接配置正确

### Q: 登录后跳转异常
A: 检查 session 配置，确保 cookie 路径正确

### Q: 样式/脚本加载失败
A: 检查 Nginx/Apache 静态文件配置

## 联系支持

如有问题，请参考验收清单进行自查，或联系开发团队。
