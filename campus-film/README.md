# 校园微电影展映站（PHP + MySQL）

## 功能
- **学生端**
  - 按学院 / 年份 / 主题筛选浏览影片，支持片名、导演关键词搜索
  - 注册登录后收藏想看的影片、查看收藏列表
  - 查看影片详情、近期放映场次，发表留言（审核通过后公开）
- **管理端**
  - CSV 批量导入影片（片名、导演、学院、年份、主题、片长、海报、简介）
  - **导入必须先预览**：上传后逐行校验并展示错误行，确认后才在事务中写入，任何一步失败都会回滚，不会写乱数据库
  - 新增 / 删除放映场次
  - 审核观众留言（通过 / 拒绝）

## 部署
1. 环境：PHP 8.0+（PDO MySQL 扩展）+ MySQL 5.7+/8.0+
2. 修改 `config.php` 中的数据库账号与 `BASE_URL`
3. 浏览器访问 `install.php` 自动建库建表并创建管理员（admin / admin123），**完成后删除 install.php**
4. 海报图片放入 `uploads/posters/`（需可写），CSV 中填文件名；也可直接填 http(s) 图片链接

## CSV 格式
首行表头固定：
```
title,director,college,year,theme,duration,poster,synopsis
```
- `theme` 多个主题用 `;` 分隔；学院、主题不存在时自动创建
- `poster` 可留空；填本地图片文件名或 http(s) 链接
- 支持 UTF-8（含 BOM）与 GBK 编码；单文件 ≤ 2MB、≤ 2000 行
- 校验规则：必填项、年份 1900–2100、片长 1–600 分钟、库内/文件内查重（片名+导演+年份）
- 示例见 `samples/films_sample.csv`（含故意写错的行，可用来验证预览报错）

## 目录结构
```
config.php            配置
install.php           安装脚本（用后删除）
index.php             影片浏览（筛选）
film.php              影片详情 + 留言
screenings.php        放映安排
login/register/logout 账号
favorites.php / toggle_favorite.php / comment.php
admin/index.php       后台首页
admin/import.php      CSV 导入（预览 → 确认 → 事务写入）
admin/screenings.php  场次管理
admin/comments.php    留言审核
includes/             db / auth / helpers / 模板
sql/schema.sql        数据库结构
uploads/posters/      海报目录
```

## 安全要点
- 全部 SQL 使用 PDO 预处理语句；输出统一 `htmlspecialchars` 转义
- 所有写操作校验 CSRF Token；密码 `password_hash` 存储
- 导入确认时重新校验数据，整体事务写入，失败回滚
