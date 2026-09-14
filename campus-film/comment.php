<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
csrf_check();

$fid = (int)($_POST['film_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
if ($content === '' || mb_strlen($content) > 1000) {
    flash('err', '留言内容不能为空且不超过 1000 字');
} else {
    $st = db()->prepare('SELECT 1 FROM films WHERE id = ?');
    $st->execute([$fid]);
    if (!$st->fetchColumn()) { http_response_code(404); exit('影片不存在'); }
    db()->prepare('INSERT INTO comments (user_id, film_id, content) VALUES (?, ?, ?)')
        ->execute([$u['id'], $fid, $content]);
    flash('ok', '留言已提交，审核通过后显示');
}
redirect('/film.php?id=' . $fid);
