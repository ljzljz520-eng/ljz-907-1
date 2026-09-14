<?php
// 安装脚本：建库建表 + 创建管理员。安装完成后请删除本文件！
require_once __DIR__ . '/config.php';

$msg = [];
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    // 逐条执行 schema.sql，兼容不支持多语句的驱动
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
        $stmt = preg_replace('/^--.*$/m', '', $stmt); // 去注释行
        if (trim($stmt) !== '') $pdo->exec($stmt);
    }
    $msg[] = '数据库结构创建成功';

    $pdo->exec('USE ' . DB_NAME);
    $st = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if (!$st->fetchColumn()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, "admin")')
            ->execute(['admin', $hash]);
        $msg[] = '已创建管理员：admin / admin123（请登录后立即修改）';
    } else {
        $msg[] = '管理员已存在，跳过创建';
    }
} catch (Throwable $e) {
    $msg[] = '安装失败：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta charset="UTF-8"><title>安装</title></head>
<body style="font-family:sans-serif;max-width:600px;margin:60px auto">
<h1>校园微电影展映站 · 安装</h1>
<?php foreach ($msg as $m): ?><p><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>
<p><a href="<?= BASE_URL ?>/index.php">进入首页</a> ｜ 安装完成后请删除 install.php</p>
</body></html>
