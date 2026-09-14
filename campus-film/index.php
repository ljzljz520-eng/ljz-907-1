<?php
require_once __DIR__ . '/includes/auth.php';
$page_title = '影片列表';

$colleges = db()->query('SELECT * FROM colleges ORDER BY name')->fetchAll();
$themes   = db()->query('SELECT * FROM themes ORDER BY name')->fetchAll();
$years    = db()->query('SELECT DISTINCT year FROM films ORDER BY year DESC')->fetchAll(PDO::FETCH_COLUMN);

// 筛选参数
$cid   = (int)($_GET['college'] ?? 0);
$tid   = (int)($_GET['theme'] ?? 0);
$year  = (int)($_GET['year'] ?? 0);
$kw    = trim($_GET['q'] ?? '');

$sql = 'SELECT DISTINCT f.*, c.name AS college_name
        FROM films f
        JOIN colleges c ON c.id = f.college_id';
if ($tid) $sql .= ' JOIN film_theme ft ON ft.film_id = f.id AND ft.theme_id = ?';
$sql .= ' WHERE 1=1';
$args = [];
if ($tid)   $args[] = $tid;
if ($cid) { $sql .= ' AND f.college_id = ?'; $args[] = $cid; }
if ($year){ $sql .= ' AND f.year = ?';       $args[] = $year; }
if ($kw !== '') { $sql .= ' AND (f.title LIKE ? OR f.director LIKE ?)'; $args[] = "%$kw%"; $args[] = "%$kw%"; }
$sql .= ' ORDER BY f.year DESC, f.id DESC';

$st = db()->prepare($sql);
$st->execute($args);
$films = $st->fetchAll();

// 当前用户已收藏的影片
$faved = [];
if ($u = current_user()) {
    $st = db()->prepare('SELECT film_id FROM favorites WHERE user_id = ?');
    $st->execute([$u['id']]);
    $faved = array_flip($st->fetchAll(PDO::FETCH_COLUMN));
}

require __DIR__ . '/includes/header.php';
?>
<h1>影片展映</h1>
<form class="filter" method="get">
  <select name="college">
    <option value="0">全部学院</option>
    <?php foreach ($colleges as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $cid === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="theme">
    <option value="0">全部主题</option>
    <?php foreach ($themes as $t): ?>
      <option value="<?= $t['id'] ?>" <?= $tid === (int)$t['id'] ? 'selected' : '' ?>><?= h($t['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="year">
    <option value="0">全部年份</option>
    <?php foreach ($years as $y): ?>
      <option value="<?= $y ?>" <?= $year === (int)$y ? 'selected' : '' ?>><?= $y ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="q" placeholder="片名 / 导演" value="<?= h($kw) ?>">
  <button type="submit">筛选</button>
</form>

<?php if (!$films): ?>
  <p class="empty">暂无符合条件的影片。</p>
<?php else: ?>
<div class="grid">
  <?php foreach ($films as $f): ?>
  <div class="card">
    <a href="<?= BASE_URL ?>/film.php?id=<?= $f['id'] ?>">
      <img class="poster" src="<?= h(poster_url($f['poster'])) ?>" alt="海报" loading="lazy">
    </a>
    <div class="card-body">
      <a class="title" href="<?= BASE_URL ?>/film.php?id=<?= $f['id'] ?>"><?= h($f['title']) ?></a>
      <div class="meta"><?= h($f['director']) ?> · <?= h($f['college_name']) ?></div>
      <div class="meta"><?= $f['year'] ?> · <?= $f['duration'] ?> 分钟</div>
      <?php if ($u): ?>
      <form method="post" action="<?= BASE_URL ?>/toggle_favorite.php">
        <?= csrf_field() ?>
        <input type="hidden" name="film_id" value="<?= $f['id'] ?>">
        <button class="fav <?= isset($faved[$f['id']]) ? 'on' : '' ?>" type="submit">
          <?= isset($faved[$f['id']]) ? '★ 已收藏' : '☆ 收藏' ?>
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
