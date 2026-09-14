<?php
require_once __DIR__ . '/includes/auth.php';
$page_title = '登录';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $user = $st->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $user['id'];
        flash('ok', '登录成功');
        redirect($user['role'] === 'admin' ? '/admin/index.php' : '/index.php');
    }
    flash('err', '用户名或密码错误');
}
require __DIR__ . '/includes/header.php';
?>
<h1>登录</h1>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>用户名 <input name="username" required></label>
  <label>密码 <input type="password" name="password" required></label>
  <button type="submit">登录</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
