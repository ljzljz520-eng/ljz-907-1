<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
csrf_check();

$fid = (int)($_POST['film_id'] ?? 0);
$st = db()->prepare('SELECT 1 FROM films WHERE id = ?');
$st->execute([$fid]);
if (!$st->fetchColumn()) { http_response_code(404); exit('影片不存在'); }

$st = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND film_id = ?');
$st->execute([$u['id'], $fid]);
if ($st->fetchColumn()) {
    db()->prepare('DELETE FROM favorites WHERE user_id = ? AND film_id = ?')->execute([$u['id'], $fid]);
    flash('ok', '已取消收藏');
} else {
    db()->prepare('INSERT INTO favorites (user_id, film_id) VALUES (?, ?)')->execute([$u['id'], $fid]);
    flash('ok', '已加入收藏');
}
$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');
header('Location: ' . $back);
exit;
