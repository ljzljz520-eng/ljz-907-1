<?php
require_once __DIR__ . '/helpers.php';

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    static $user = null;
    if ($user === null) {
        $st = db()->prepare('SELECT id, username, role FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $user = $st->fetch() ?: null;
    }
    return $user;
}

function require_login(): array {
    $u = current_user();
    if (!$u) { flash('err', '请先登录'); redirect('/login.php'); }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('需要管理员权限'); }
    return $u;
}
