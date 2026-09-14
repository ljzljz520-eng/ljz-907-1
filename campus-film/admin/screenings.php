<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = '放映场次管理';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $fid   = (int)($_POST['film_id'] ?? 0);
        $venue = trim($_POST['venue'] ?? '');
        $time  = trim($_POST['start_time'] ?? '');
        $ts    = strtotime($time);
        if (!$fid || $venue === '' || !$ts) {
            flash('err', '请完整填写影片、地点和时间');
        } else {
            db()->prepare('INSERT INTO screenings (film_id, venue, start_time) VALUES (?, ?, ?)')
                ->execute([$fid, $venue, date('Y-m-d H:i:s', $ts)]);
            flash('ok', '场次已添加');
        }
    } elseif ($action === 'del') {
        db()->prepare('DELETE FROM screenings WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('ok', '场次已删除');
    }
    redirect('/admin/screenings.php');
}

$films = db()->query('SELECT id, title, year FROM films ORDER BY title')->fetchAll();
$rows  = db()->query('SELECT s.*, f.title FROM screenings s JOIN films f ON f.id = s.film_id
                      ORDER BY s.start_time DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>放映场次管理</h1>
<form method="post" class="form inline-add">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="add">
  <select name="film_id" required>
    <option value="">选择影片</option>
    <?php foreach ($films as $f): ?>
      <option value="<?= $f['id'] ?>"><?= h($f['title']) ?>（<?= $f['year'] ?>）</option>
    <?php endforeach; ?>
  </select>
  <input name="venue" placeholder="放映地点" required maxlength="100">
  <input type="datetime-local" name="start_time" required>
  <button type="submit">新增场次</button>
</form>

<table class="table">
  <tr><th>时间</th><th>影片</th><th>地点</th><th>操作</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= date('Y-m-d H:i', strtotime($r['start_time'])) ?></td>
    <td><?= h($r['title']) ?></td>
    <td><?= h($r['venue']) ?></td>
    <td>
      <form method="post" class="inline-form" onsubmit="return confirm('确定删除该场次？')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="del">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button type="submit" class="danger">删除</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php if (!$rows): ?><p class="empty">暂无场次。</p><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
