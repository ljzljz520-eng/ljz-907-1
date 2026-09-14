<?php require_once __DIR__ . '/auth.php'; $u = current_user(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($page_title) ? h($page_title) . ' · ' : '' ?>校园微电影展映</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<nav class="nav">
  <a class="brand" href="<?= BASE_URL ?>/index.php">🎬 校园微电影展映</a>
  <a href="<?= BASE_URL ?>/index.php">影片</a>
  <a href="<?= BASE_URL ?>/screenings.php">放映安排</a>
  <?php if ($u): ?>
    <a href="<?= BASE_URL ?>/favorites.php">我的收藏</a>
    <?php if ($u['role'] === 'admin'): ?>
      <a href="<?= BASE_URL ?>/admin/index.php">后台管理</a>
    <?php endif; ?>
    <span class="nav-user">你好，<?= h($u['username']) ?></span>
    <a href="<?= BASE_URL ?>/logout.php">退出</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/login.php">登录</a>
    <a href="<?= BASE_URL ?>/register.php">注册</a>
  <?php endif; ?>
</nav>
<main class="container">
<?php if ($m = flash('ok')): ?><div class="alert ok"><?= h($m) ?></div><?php endif; ?>
<?php if ($m = flash('err')): ?><div class="alert err"><?= h($m) ?></div><?php endif; ?>
