-- Existing database migration: Low / Medium difficulty isolation.
-- MySQL 5.7 / 8.0 compatible. Back up the database and run this file once.

USE `vuln_db`;

ALTER TABLE `challenge_progress`
    ADD COLUMN `difficulty` VARCHAR(10) NOT NULL DEFAULT 'low' AFTER `slug`;

UPDATE `challenge_progress` SET `difficulty` = 'low';

ALTER TABLE `challenge_progress`
    DROP INDEX `uk_challenge_slug`,
    ADD UNIQUE KEY `uk_challenge_slug_difficulty` (`slug`, `difficulty`);

ALTER TABLE `messages`
    ADD COLUMN `difficulty` VARCHAR(10) NOT NULL DEFAULT 'low' AFTER `id`,
    ADD KEY `idx_messages_difficulty_id` (`difficulty`, `id`);

UPDATE `messages` SET `difficulty` = 'low';

INSERT INTO `challenge_progress` (`slug`, `difficulty`, `title`, `target_file`, `status`, `flag`, `description`, `sort_order`) VALUES
    ('sqli', 'medium', 'SQL 注入', 'sqli.php', 'incomplete', 'FLAG{MEDIUM_SQLI_FILTER_BYPASS}', '错误关键字过滤仍可绕过。', 10),
    ('command', 'medium', '命令注入', 'command.php', 'incomplete', 'FLAG{MEDIUM_COMMAND_SEPARATOR_BYPASS}', '连接符黑名单遗漏可用组合。', 20),
    ('stored_xss', 'medium', '存储型 XSS', 'stored_xss.php', 'incomplete', 'FLAG{MEDIUM_STORED_XSS_EVENT_HANDLER}', '只移除 script 标签。', 30),
    ('reflected_xss', 'medium', '反射型 XSS', 'reflected_xss.php', 'incomplete', 'FLAG{MEDIUM_REFLECTED_XSS_ALLOWED_TAG}', '危险标签白名单仍保留事件属性。', 40),
    ('upload', 'medium', '文件上传', 'upload.php', 'incomplete', 'FLAG{MEDIUM_UPLOAD_IMAGE_POLYGLOT}', '图片校验保留原扩展名。', 50),
    ('file_include', 'medium', '文件包含', 'file_include.php', 'incomplete', 'FLAG{MEDIUM_LFI_NESTED_TRAVERSAL}', '一次路径替换仍可绕过。', 60),
    ('csrf', 'medium', 'CSRF', 'csrf.php', 'incomplete', 'FLAG{MEDIUM_CSRF_WEAK_REFERER}', '弱 Referer 子串检查。', 70),
    ('dom_xss', 'medium', 'DOM 型 XSS', 'dom_xss.php', 'incomplete', 'FLAG{MEDIUM_DOM_XSS_FILTER_BYPASS}', '少量字符串过滤后仍进入 innerHTML。', 80);

INSERT INTO `messages` (`difficulty`, `name`, `content`, `ip_address`) VALUES
    ('medium', '系统提示', '欢迎来到 Medium 留言板。本难度加入了不完整过滤。', '127.0.0.1'),
    ('medium', 'Alice', '第一条 Medium 普通留言，与 Low 数据相互隔离。', '127.0.0.1');
