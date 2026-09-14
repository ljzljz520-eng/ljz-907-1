<?php
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT f.*, c.name AS college_name FROM films f
                     JOIN colleges c ON c.id = f.college_id WHERE f.id = ?');
$st->execute([$id]);
$film = $st->fetch();
if (!$film) { http_response_code(404); exit('影片不存在'); }
$page_title = $film['title'];

$st = db()->prepare('SELECT t.name FROM film_theme ft JOIN themes t ON t.id = ft.theme_id
                     WHERE ft.film_id = ? ORDER BY t.name');
$st->execute([$id]);
$filmThemes = $st->fetchAll(PDO::FETCH_COLUMN);

$st = db()->prepare('SELECT * FROM screenings WHERE film_id = ? AND start_time >= NOW()
                     ORDER BY start_time');
$st->execute([$id]);
$screenings = $st->fetchAll();

$st = db()->prepare('SELECT cm.content, cm.created_at, u.username FROM comments cm
                     JOIN users u ON u.id = cm.user_id
                     WHERE cm.film_id = ? AND cm.status = \'approved\'
                     ORDER BY cm.created_at DESC');
$st->execute([$id]);
$comments = $st->fetchAll();

$isFav = false;
if ($u = current_user()) {
    $st = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND film_id = ?');
    $st->execute([$u['id'], $id]);
    $isFav = (bool)$st->fetchColumn();
}

require __DIR__ . '/includes/header.php';
?>
<div class="film-detail">
  <img class="poster-lg" src="<?= h(poster_url($film['poster'])) ?>" alt="海报">
  <div>
    <h1><?= h($film['title']) ?></h1>
    <p class="meta">导演：<?= h($film['director']) ?></p>
    <p class="meta">学院：<?= h($film['college_name']) ?> · 年份：<?= $film['year'] ?> · 片长：<?= $film['duration'] ?> 分钟</p>
    <?php if ($filmThemes): ?>
      <p class="meta">主题：<?= h(implode('、', $filmThemes)) ?></p>
    <?php endif; ?>
    <p><?= nl2br(h($film['synopsis'] ?? '')) ?></p>
    <?php if ($u): ?>
    <form method="post" action="<?= BASE_URL ?>/toggle_favorite.php">
      <?= csrf_field() ?>
      <input type="hidden" name="film_id" value="<?= $film['id'] ?>">
      <button class="fav <?= $isFav ? 'on' : '' ?>" type="submit"><?= $isFav ? '★ 已收藏' : '☆ 收藏想看' ?></button>
    </form>
    <?php else: ?>
      <p><a href="<?= BASE_URL ?>/login.php">登录</a>后可收藏想看。</p>
    <?php endif; ?>
  </div>
</div>

<h2>近期放映</h2>
<?php if (!$screenings): ?>
  <p class="empty">暂无排期。</p>
<?php else: ?>
<ul class="list">
  <?php foreach ($screenings as $s): ?>
    <li><?= date('Y-m-d H:i', strtotime($s['start_time'])) ?> · <?= h($s['venue']) ?></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<h2>观众留言</h2>
<?php if ($u): ?>
<form method="post" action="<?= BASE_URL ?>/comment.php" class="comment-form">
  <?= csrf_field() ?>
  <input type="hidden" name="film_id" value="<?= $film['id'] ?>">
  <textarea name="content" rows="3" maxlength="1000" required placeholder="写下你的观后感（审核后公开显示）"></textarea>
  <button type="submit">提交留言</button>
</form>
<?php else: ?>
  <p><a href="<?= BASE_URL ?>/login.php">登录</a>后可留言。</p>
<?php endif; ?>

<?php foreach ($comments as $cm): ?>
<div class="comment">
  <div class="meta"><?= h($cm['username']) ?> · <?= h($cm['created_at']) ?></div>
  <div><?= nl2br(h($cm['content'])) ?></div>
</div>
<?php endforeach; ?>
<?php if (!$comments): ?><p class="empty">还没有公开的留言。</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
