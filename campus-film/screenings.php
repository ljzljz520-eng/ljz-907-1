<?php
require_once __DIR__ . '/includes/auth.php';
$page_title = '放映安排';
$rows = db()->query(
    'SELECT s.*, f.title, f.duration FROM screenings s
     JOIN films f ON f.id = s.film_id
     ORDER BY s.start_time DESC'
)->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<h1>放映安排</h1>
<?php if (!$rows): ?><p class="empty">暂无放映场次。</p><?php endif; ?>
<table class="table">
  <tr><th>时间</th><th>影片</th><th>片长</th><th>地点</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= date('Y-m-d H:i', strtotime($r['start_time'])) ?></td>
    <td><a href="<?= BASE_URL ?>/film.php?id=<?= $r['film_id'] ?>"><?= h($r['title']) ?></a></td>
    <td><?= $r['duration'] ?> 分钟</td>
    <td><?= h($r['venue']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
