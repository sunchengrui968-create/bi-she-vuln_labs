-- ============================================================
-- 本地授权漏洞靶场数据库初始化脚本
-- 兼容 MySQL 5.7 / 8.0；会删除现有表，请先备份实验数据。
-- ============================================================

CREATE DATABASE IF NOT EXISTS `vuln_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `vuln_db`;

DROP TABLE IF EXISTS `challenge_progress`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(60) NOT NULL,
    `nickname` VARCHAR(80) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `role` VARCHAR(30) NOT NULL DEFAULT 'student',
    `password` VARCHAR(120) NOT NULL,
    `password_hash` CHAR(64) NOT NULL,
    `profile` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`username`, `nickname`, `email`, `role`, `password`, `password_hash`, `profile`) VALUES
    ('admin', '系统管理员', 'admin@vuln-lab.local', 'admin', 'Admin@Lab2026!', SHA2('Admin@Lab2026!', 256), '管理员测试账号，用于观察普通查询与注入查询的回显差异。'),
    ('alice', '安全课程学生 Alice', 'alice@vuln-lab.local', 'student', 'Alice#Sqlmap2026', SHA2('Alice#Sqlmap2026', 256), '正在学习 SQL 注入与 XSS。'),
    ('bob', '网络实验学生 Bob', 'bob@vuln-lab.local', 'student', 'Bob#Union2026', SHA2('Bob#Union2026', 256), '负责命令注入实验记录。'),
    ('teacher', '指导老师', 'teacher@vuln-lab.local', 'teacher', 'Teacher#Demo2026', SHA2('Teacher#Demo2026', 256), '用于毕业设计答辩展示。');

CREATE TABLE `messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `difficulty` VARCHAR(10) NOT NULL DEFAULT 'low',
    `name` VARCHAR(80) NOT NULL,
    `content` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_messages_difficulty_id` (`difficulty`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `messages` (`difficulty`, `name`, `content`, `ip_address`) VALUES
    ('low', '系统提示', '欢迎来到 Low 留言板。这里用于演示存储型 XSS。', '127.0.0.1'),
    ('low', 'Alice', '第一条 Low 普通留言，用于对比恶意脚本留言。', '127.0.0.1'),
    ('medium', '系统提示', '欢迎来到 Medium 留言板。本难度加入了不完整过滤。', '127.0.0.1'),
    ('medium', 'Alice', '第一条 Medium 普通留言，与 Low 数据相互隔离。', '127.0.0.1');

CREATE TABLE `challenge_progress` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(50) NOT NULL,
    `difficulty` VARCHAR(10) NOT NULL DEFAULT 'low',
    `title` VARCHAR(80) NOT NULL,
    `target_file` VARCHAR(120) NOT NULL,
    `status` ENUM('incomplete', 'passed') NOT NULL DEFAULT 'incomplete',
    `flag` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_challenge_slug_difficulty` (`slug`, `difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `challenge_progress` (`slug`, `difficulty`, `title`, `target_file`, `status`, `flag`, `description`, `sort_order`) VALUES
    ('sqli', 'low', 'SQL 注入', 'sqli.php', 'incomplete', 'FLAG{SQLI_UNION_QUERY_SUCCESS}', '数字参数直接拼接 SQL。', 10),
    ('command', 'low', '命令注入', 'command.php', 'incomplete', 'FLAG{COMMAND_INJECTION_LOCAL_EXEC}', 'Ping 参数直接拼接系统命令。', 20),
    ('stored_xss', 'low', '存储型 XSS', 'stored_xss.php', 'incomplete', 'FLAG{STORED_XSS_MESSAGE_BOARD}', '留言原样存储并输出。', 30),
    ('reflected_xss', 'low', '反射型 XSS', 'reflected_xss.php', 'incomplete', 'FLAG{REFLECTED_XSS_SEARCH_ECHO}', '搜索参数直接写入响应。', 40),
    ('upload', 'low', '文件上传', 'upload.php', 'incomplete', 'FLAG{FILE_UPLOAD_WEBSHELL_LAB}', '文件不校验类型和后缀。', 50),
    ('file_include', 'low', '文件包含', 'file_include.php', 'incomplete', 'FLAG{FILE_INCLUDE_PATH_TRAVERSAL}', 'page 参数直接拼接本地路径。', 60),
    ('csrf', 'low', 'CSRF', 'csrf.php', 'incomplete', 'FLAG{CSRF_CHANGE_EMAIL_NO_TOKEN}', '邮箱修改不校验 Token。', 70),
    ('dom_xss', 'low', 'DOM 型 XSS', 'dom_xss.php', 'incomplete', 'FLAG{DOM_XSS_LOCATION_INNERHTML}', 'URL 数据直接进入 innerHTML。', 80),
    ('sqli', 'medium', 'SQL 注入', 'sqli.php', 'incomplete', 'FLAG{MEDIUM_SQLI_FILTER_BYPASS}', '错误关键字过滤仍可绕过。', 10),
    ('command', 'medium', '命令注入', 'command.php', 'incomplete', 'FLAG{MEDIUM_COMMAND_SEPARATOR_BYPASS}', '连接符黑名单遗漏可用组合。', 20),
    ('stored_xss', 'medium', '存储型 XSS', 'stored_xss.php', 'incomplete', 'FLAG{MEDIUM_STORED_XSS_EVENT_HANDLER}', '只移除 script 标签。', 30),
    ('reflected_xss', 'medium', '反射型 XSS', 'reflected_xss.php', 'incomplete', 'FLAG{MEDIUM_REFLECTED_XSS_ALLOWED_TAG}', '危险标签白名单仍保留事件属性。', 40),
    ('upload', 'medium', '文件上传', 'upload.php', 'incomplete', 'FLAG{MEDIUM_UPLOAD_IMAGE_POLYGLOT}', '图片校验保留原扩展名。', 50),
    ('file_include', 'medium', '文件包含', 'file_include.php', 'incomplete', 'FLAG{MEDIUM_LFI_NESTED_TRAVERSAL}', '一次路径替换仍可绕过。', 60),
    ('csrf', 'medium', 'CSRF', 'csrf.php', 'incomplete', 'FLAG{MEDIUM_CSRF_WEAK_REFERER}', '弱 Referer 子串检查。', 70),
    ('dom_xss', 'medium', 'DOM 型 XSS', 'dom_xss.php', 'incomplete', 'FLAG{MEDIUM_DOM_XSS_FILTER_BYPASS}', '少量字符串过滤后仍进入 innerHTML。', 80);
