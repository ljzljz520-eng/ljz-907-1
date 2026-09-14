<?php
require_once __DIR__ . '/includes/auth.php';
$page_title = '注册';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!preg_match('/^[\w\x{4e00}-\x{9fa5}]{2,30}$/u', $name)) {
        flash('err', '用户名需为 2-30 位字母、数字、下划线或中文');
    } elseif (strlen($pass) < 6) {
        flash('err', '密码至少 6 位');
    } else {
        $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
        $st->execute([$name]);
        if ($st->fetchColumn()) {
            flash('err', '用户名已被占用');
        } else {
            $st = db()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $st->execute([$name, password_hash($pass, PASSWORD_DEFAULT)]);
            $_SESSION['uid'] = db()->lastInsertId();
            flash('ok', '注册成功，欢迎！');
            redirect('/index.php');
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>注册</h1>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>用户名 <input name="username" required maxlength="30"></label>
  <label>密码 <input type="password" name="password" required minlength="6"></label>
  <button type="submit">注册</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
