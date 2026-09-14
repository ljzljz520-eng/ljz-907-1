<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = '后台管理';
$counts = [];
foreach (['films'=>'影片','screenings'=>'放映场次','users'=>'用户','comments'=>'留言'] as $t => $label) {
    $counts[$label] = db()->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}
$pending = db()->query("SELECT COUNT(*) FROM comments WHERE status='pending'")->fetchColumn();
require __DIR__ . '/../includes/header.php';
?>
<h1>后台管理</h1>
<div class="stats">
  <?php foreach ($counts as $label => $n): ?>
    <div class="stat"><div class="num"><?= $n ?></div><div><?= $label ?></div></div>
  <?php endforeach; ?>
</div>
<ul class="admin-menu">
  <li><a href="<?= BASE_URL ?>/admin/import.php">📥 CSV 导入影片</a></li>
  <li><a href="<?= BASE_URL ?>/admin/screenings.php">📅 放映场次管理</a></li>
  <li><a href="<?= BASE_URL ?>/admin/comments.php">💬 留言审核<?= $pending ? "（$pending 条待审）" : '' ?></a></li>
</ul>
<?php require __DIR__ . '/../includes/footer.php'; ?>
