<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$page_title = '我的收藏';

$st = db()->prepare(
    'SELECT f.*, c.name AS college_name FROM favorites fv
     JOIN films f ON f.id = fv.film_id
     JOIN colleges c ON c.id = f.college_id
     WHERE fv.user_id = ? ORDER BY fv.created_at DESC'
);
$st->execute([$u['id']]);
$films = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<h1>我的收藏</h1>
<?php if (!$films): ?>
  <p class="empty">还没有收藏，去<a href="<?= BASE_URL ?>/index.php">影片列表</a>看看。</p>
<?php else: ?>
<div class="grid">
  <?php foreach ($films as $f): ?>
  <div class="card">
    <a href="<?= BASE_URL ?>/film.php?id=<?= $f['id'] ?>">
      <img class="poster" src="<?= h(poster_url($f['poster'])) ?>" alt="海报">
    </a>
    <div class="card-body">
      <a class="title" href="<?= BASE_URL ?>/film.php?id=<?= $f['id'] ?>"><?= h($f['title']) ?></a>
      <div class="meta"><?= h($f['director']) ?> · <?= h($f['college_name']) ?></div>
      <div class="meta"><?= $f['year'] ?> · <?= $f['duration'] ?> 分钟</div>
      <form method="post" action="<?= BASE_URL ?>/toggle_favorite.php">
        <?= csrf_field() ?>
        <input type="hidden" name="film_id" value="<?= $f['id'] ?>">
        <button class="fav on" type="submit">★ 取消收藏</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
