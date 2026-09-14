<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = '留言审核';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['approved', 'rejected'], true)) {
        db()->prepare('UPDATE comments SET status = ? WHERE id = ?')->execute([$action, $id]);
        flash('ok', '已更新留言状态');
    }
    redirect('/admin/comments.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}

$status = $_GET['status'] ?? 'pending';
if (!in_array($status, ['pending', 'approved', 'rejected'], true)) $status = 'pending';
$st = db()->prepare(
    'SELECT cm.*, u.username, f.title FROM comments cm
     JOIN users u ON u.id = cm.user_id
     JOIN films f ON f.id = cm.film_id
     WHERE cm.status = ? ORDER BY cm.created_at DESC'
);
$st->execute([$status]);
$rows = $st->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>留言审核</h1>
<p class="tabs">
  <?php foreach (['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝'] as $k => $label): ?>
    <a class="<?= $status === $k ? 'active' : '' ?>" href="?status=<?= $k ?>"><?= $label ?></a>
  <?php endforeach; ?>
</p>
<?php if (!$rows): ?><p class="empty">没有<?= ['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝'][$status] ?>的留言。</p><?php endif; ?>
<?php foreach ($rows as $r): ?>
<div class="comment">
  <div class="meta">
    <strong><?= h($r['username']) ?></strong> 评论
    <a href="<?= BASE_URL ?>/film.php?id=<?= $r['film_id'] ?>"><?= h($r['title']) ?></a>
    · <?= h($r['created_at']) ?>
  </div>
  <div><?= nl2br(h($r['content'])) ?></div>
  <form method="post" class="inline-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <?php if ($status !== 'approved'): ?>
      <button name="action" value="approved" class="primary">通过</button>
    <?php endif; ?>
    <?php if ($status !== 'rejected'): ?>
      <button name="action" value="rejected" class="danger">拒绝</button>
    <?php endif; ?>
  </form>
</div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
