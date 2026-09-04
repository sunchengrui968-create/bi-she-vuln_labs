# 基于常见漏洞的本地靶场平台

这是一个毕业设计项目，用于在本地环境中学习、演示和分析常见 Web 安全漏洞。项目基于 PHP + MySQL 开发，推荐使用 phpStudy 作为本地 Web 服务器环境运行。

> 本项目仅供学习交流、毕业设计展示、课堂演示和授权安全测试使用。项目中包含故意保留的不安全代码，请勿部署到公网环境，切勿用于任何非法用途。使用者应自行遵守当地法律法规，并仅在明确授权的环境中进行测试。

## 项目简介

本靶场参考 DVWA 一类本地漏洞实验平台的思路，围绕常见 Web 漏洞设计了多个可复现实验模块。平台提供统一入口、难度切换、Flag 校验、通关进度记录、当前难度重置和安全修复演示，方便在毕业设计或教学场景中展示漏洞成因、利用过程和修复思路。

项目的公共平台逻辑尽量采用安全写法，故意不安全的实验代码集中放在 `vulnerabilities/` 目录下，便于对比 Low、Medium 和 Secure 实现。

## 功能模块

- SQL 注入
- 命令注入
- 存储型 XSS
- 反射型 XSS
- 文件上传
- 文件包含
- CSRF
- DOM 型 XSS

每个模块目前包含 Low、Medium 和 Secure 三类实现：

- `low.php`：基础漏洞实现，防护较少，适合理解漏洞原理。
- `medium.php`：加入不完整防护，适合练习过滤绕过和安全边界分析。
- `secure.php`：安全修复示例，用于对比修复思路。

## 运行环境

- Windows + phpStudy
- PHP 7.4 或更高版本
- MySQL 5.7 / 8.0
- 浏览器访问本地站点

项目默认数据库配置位于 `config.php`：

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'vuln_db';
const DB_USER = 'root';
const DB_PASS = 'root';
```

如果本地 phpStudy 的 MySQL 用户名、密码或端口不同，请按实际环境修改。

## 部署步骤

1. 将项目放入 phpStudy 网站根目录，例如：

   ```text
   E:\phpstudy_pro\WWW\labs
   ```

2. 在 phpStudy 中启动 Web 服务和 MySQL 服务。

3. 导入数据库初始化脚本：

   ```text
   database/init.sql
   ```

   该脚本会创建 `vuln_db` 数据库，并初始化用户、留言、Flag 和通关进度数据。

4. 在浏览器中访问：

   ```text
   http://localhost/labs/index.php
   ```

5. 在首页选择 Low 或 Medium 难度，进入对应漏洞模块进行练习。

## 项目结构

```text
.
├── assets/                  # 页面样式
├── database/                # 数据库初始化与迁移脚本
├── flags/                   # 部分本地 Flag 文件
├── tests/                   # 项目结构检查脚本
├── tools/                   # phpStudy 同步和辅助文件
├── uploads/                 # 文件上传实验目录
├── vulnerabilities/         # 各漏洞模块的 Low / Medium / Secure 实现
├── config.php               # 公共配置与平台函数
├── index.php                # 靶场首页
└── *.php                    # 各漏洞模块入口文件
```

## 辅助脚本

项目包含结构检查脚本，可在 PowerShell 中执行：

```powershell
powershell -ExecutionPolicy Bypass -File tests\phase1_structure_check.ps1
powershell -ExecutionPolicy Bypass -File tests\difficulty_structure_check.ps1
```

如果需要同步到本机 phpStudy 目录，可根据自己的安装路径调整 `tools/sync_to_phpstudy.ps1` 中的目标目录后执行。

## 使用说明

- 首页会显示当前数据库连接状态、当前难度和各模块通关进度。
- Low 与 Medium 难度的数据和 Flag 相互隔离。
- 通过提交正确 Flag 标记对应模块通关。
- “重置当前难度”会清空当前难度的通关状态、留言数据、上传文件和自动修复状态。
- “自动修复”用于切换到对应模块的 Secure 实现，帮助对比漏洞代码与安全代码。

## 安全声明

本项目是一个本地授权实验环境，包含命令执行、文件上传、文件包含、XSS、SQL 注入等故意设计的脆弱代码。请务必注意：

- 不要部署到公网服务器。
- 不要连接真实业务数据库。
- 不要使用真实账号、密码或敏感数据进行测试。
- 不要将本项目用于未授权扫描、攻击、入侵或破坏行为。
- 建议仅在本机 `localhost` 或隔离虚拟机中运行。

因违反法律法规或超出授权范围使用本项目造成的任何后果，由使用者自行承担。

## 许可证与用途

本仓库主要用于个人毕业设计、课程学习和安全研究交流。若用于二次开发或教学展示，请保留项目来源说明和安全声明。
