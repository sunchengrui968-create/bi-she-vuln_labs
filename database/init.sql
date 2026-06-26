-- ============================================================
-- 毕业设计项目：基于常见漏洞的靶场平台与自动化渗透工具的设计与实现
-- 文件用途：数据库初始化脚本
-- 运行环境：phpStudy / MySQL 5.7+ 或 MySQL 8.0+
--
-- 重要说明：
-- 1. 本脚本仅用于本地授权靶场环境，数据库中的测试数据用于论文演示和漏洞练习。
-- 2. 后续漏洞页面会故意保留不安全写法，用来复现 SQL 注入、XSS、命令注入等问题。
-- 3. 请不要把本项目部署到公网或生产环境。
-- ============================================================

CREATE DATABASE IF NOT EXISTS `vuln_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `vuln_db`;

-- 为了方便反复调试和论文演示，初始化时先删除旧表。
-- 如果你已经在表中保存了实验数据，请先备份再执行本脚本。
DROP TABLE IF EXISTS `challenge_progress`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- users：用户/书籍查询演示表
-- 后续 SQL 注入模块会直接拼接 id 参数查询此表，例如：
-- SELECT * FROM users WHERE id = $_GET['id']
-- 这种写法是不安全的，但在靶场中用于展示漏洞成因。
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户编号，SQL 注入模块会用它作为查询参数',
    `username` VARCHAR(60) NOT NULL COMMENT '登录名或查询名',
    `nickname` VARCHAR(80) NOT NULL COMMENT '页面展示昵称',
    `email` VARCHAR(120) NOT NULL COMMENT '测试邮箱',
    `role` VARCHAR(30) NOT NULL DEFAULT 'student' COMMENT '角色：student / teacher / admin',
    `password` VARCHAR(120) NOT NULL COMMENT '本地靶场演示明文密码',
    `password_hash` CHAR(64) NOT NULL COMMENT 'password 字段的 SHA-256 哈希',
    `profile` TEXT NULL COMMENT '简介字段，SQL 注入练习可通过联合查询观察回显效果',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SQL 注入演示用户表';

INSERT INTO `users`
    (`username`, `nickname`, `email`, `role`, `password`, `password_hash`, `profile`)
VALUES
    ('admin', '系统管理员', 'admin@vuln-lab.local', 'admin', 'Admin@Lab2026!', SHA2('Admin@Lab2026!', 256),
     '管理员测试账号，用于观察普通查询与注入查询的回显差异。'),
    ('alice', '安全课程学生 Alice', 'alice@vuln-lab.local', 'student', 'Alice#Sqlmap2026', SHA2('Alice#Sqlmap2026', 256),
     '喜欢 Web 安全基础实验，正在学习 SQL 注入与 XSS。'),
    ('bob', '网络实验学生 Bob', 'bob@vuln-lab.local', 'student', 'Bob#Union2026', SHA2('Bob#Union2026', 256),
     '负责命令注入实验记录，常用本地 Ping 测试页面。'),
    ('teacher', '指导老师', 'teacher@vuln-lab.local', 'teacher', 'Teacher#Demo2026', SHA2('Teacher#Demo2026', 256),
     '用于毕业设计答辩展示的指导账号。');

-- ------------------------------------------------------------
-- messages：留言板表
-- 后续存储型 XSS 模块会把 name 和 content 原样写入数据库，
-- 并在读取时直接 echo 输出，用来演示未转义输出带来的风险。
-- ------------------------------------------------------------
CREATE TABLE `messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '留言编号',
    `name` VARCHAR(80) NOT NULL COMMENT '留言人名称，靶场中故意不做严格过滤',
    `content` TEXT NOT NULL COMMENT '留言内容，存储型 XSS 模块会原样保存',
    `ip_address` VARCHAR(45) NULL COMMENT '记录实验来源 IP，便于论文截图说明',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '留言时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='存储型 XSS 留言表';

INSERT INTO `messages` (`name`, `content`, `ip_address`) VALUES
    ('系统提示', '欢迎来到本地漏洞靶场。这里的留言板后续将用于演示存储型 XSS。', '127.0.0.1'),
    ('Alice', '第一条普通留言，用于对比恶意脚本留言的显示效果。', '127.0.0.1');

-- ------------------------------------------------------------
-- challenge_progress：漏洞模块通关状态表
-- 主页 Dashboard 读取此表展示模块状态。
-- 后续各漏洞页面在验证 Flag 后，可把 status 更新为 passed。
-- ------------------------------------------------------------
CREATE TABLE `challenge_progress` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录编号',
    `slug` VARCHAR(50) NOT NULL COMMENT '漏洞模块唯一标识',
    `title` VARCHAR(80) NOT NULL COMMENT '漏洞模块标题',
    `target_file` VARCHAR(120) NOT NULL COMMENT '模块页面文件名',
    `status` ENUM('incomplete', 'passed') NOT NULL DEFAULT 'incomplete' COMMENT '通关状态',
    `flag` VARCHAR(120) NOT NULL COMMENT '本地靶场 Flag，用于论文和通关演示',
    `description` VARCHAR(255) NOT NULL COMMENT '模块简介',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '主页展示排序',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_challenge_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='漏洞模块通关状态表';

INSERT INTO `challenge_progress`
    (`slug`, `title`, `target_file`, `status`, `flag`, `description`, `sort_order`)
VALUES
    ('sqli', 'SQL 注入', 'sqli.php', 'incomplete', 'FLAG{SQLI_UNION_QUERY_SUCCESS}',
     '通过数字型参数拼接 SQL，观察联合查询或报错注入回显。', 10),
    ('command', '命令注入', 'command.php', 'incomplete', 'FLAG{COMMAND_INJECTION_LOCAL_EXEC}',
     '通过 Ping 测试功能观察系统命令拼接造成的命令执行风险。', 20),
    ('stored_xss', '存储型 XSS', 'stored_xss.php', 'incomplete', 'FLAG{STORED_XSS_MESSAGE_BOARD}',
     '通过留言板原样存储和输出内容，理解持久化脚本注入。', 30),
    ('reflected_xss', '反射型 XSS', 'reflected_xss.php', 'incomplete', 'FLAG{REFLECTED_XSS_SEARCH_ECHO}',
     '通过搜索关键词直接回显，理解一次性反射脚本注入。', 40),
    ('upload', '文件上传', 'upload.php', 'incomplete', 'FLAG{FILE_UPLOAD_WEBSHELL_LAB}',
     '通过头像上传功能观察缺少后缀和内容校验带来的风险。', 50),
    ('file_include', '文件包含', 'file_include.php', 'incomplete', 'FLAG{FILE_INCLUDE_PATH_TRAVERSAL}',
     '通过帮助文档读取功能观察路径拼接和目录穿越带来的文件读取风险。', 60),
    ('csrf', 'CSRF', 'csrf.php', 'incomplete', 'FLAG{CSRF_CHANGE_EMAIL_NO_TOKEN}',
     '通过修改邮箱操作观察缺少 CSRF Token 校验时的跨站请求伪造风险。', 70),
    ('dom_xss', 'DOM 型 XSS', 'dom_xss.php', 'incomplete', 'FLAG{DOM_XSS_LOCATION_INNERHTML}',
     '通过前端 innerHTML 写入 URL 数据理解 DOM 型 XSS 的触发方式。', 80);
