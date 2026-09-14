<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$page_title = 'CSV 导入影片';

const CSV_HEADER = ['title','director','college','year','theme','duration','poster','synopsis'];
const MAX_ROWS   = 2000;

/* ---------- 解析 + 校验，返回 [rows, fatalError] ---------- */
function parse_and_validate(string $file): array {
    // 编码处理：支持 UTF-8（含 BOM）与 GBK
    $raw = file_get_contents($file);
    if ($raw === false) return [[], '无法读取上传文件'];
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    if (!mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'GB18030');
    }
    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $raw);
    rewind($fh);

    $header = fgetcsv($fh);
    if (!$header) return [[], 'CSV 为空或格式不正确'];
    $header = array_map(fn($s) => strtolower(trim($s)), $header);
    if (array_slice($header, 0, 3) !== ['title', 'director', 'college']) {
        return [[], '表头不正确，应为：' . implode(',', CSV_HEADER)];
    }

    // 已存在的影片（用于查重）
    $existing = [];
    foreach (db()->query('SELECT title, director, year FROM films') as $r) {
        $existing[mb_strtolower($r['title'] . '|' . $r['director'] . '|' . $r['year'])] = true;
    }

    $rows = [];
    $seen = [];
    $line = 1;
    while (($cols = fgetcsv($fh)) !== false) {
        $line++;
        if (count($cols) === 1 && trim((string)$cols[0]) === '') continue; // 跳过空行
        if (count($rows) >= MAX_ROWS) { $rows[] = ['line' => $line, 'data' => [], 'errors' => ["超过单次最大 " . MAX_ROWS . " 行限制，后续行已忽略"]]; break; }
        $cols = array_map(fn($s) => trim((string)$s), $cols);
        $cols = array_pad(array_slice($cols, 0, 8), 8, '');
        [$title, $director, $college, $year, $theme, $duration, $poster, $synopsis] = $cols;

        $errors = [];
        if ($title === '')            $errors[] = '片名不能为空';
        if (mb_strlen($title) > 200)  $errors[] = '片名超长';
        if ($director === '')         $errors[] = '导演不能为空';
        if ($college === '')          $errors[] = '学院不能为空';
        if (!preg_match('/^\d{4}$/', $year) || (int)$year < 1900 || (int)$year > 2100)
                                      $errors[] = '年份须为 1900-2100 的四位数字';
        if (!ctype_digit($duration) || (int)$duration < 1 || (int)$duration > 600)
                                      $errors[] = '片长须为 1-600 的整数（分钟）';
        if ($poster !== '' && !preg_match('#^https?://#i', $poster)
            && !preg_match('/^[\w.\-一-龥]+\.(jpg|jpeg|png|gif|webp)$/iu', $poster))
                                      $errors[] = '海报须为图片文件名或 http(s) 链接';

        $key = mb_strtolower("$title|$director|$year");
        if (!$errors) {
            if (isset($existing[$key]))  $errors[] = '数据库中已存在同名同导演同年份影片';
            if (isset($seen[$key]))      $errors[] = '文件内重复行';
        }
        $seen[$key] = true;

        $rows[] = [
            'line'   => $line,
            'data'   => compact('title','director','college','year','theme','duration','poster','synopsis'),
            'errors' => $errors,
        ];
    }
    fclose($fh);
    if (!$rows) return [[], 'CSV 中没有数据行'];
    return [$rows, null];
}

/* ---------- 事务写入（仅写入校验通过的行） ---------- */
function import_rows(array $rows): array {
    $pdo = db();
    $ok = 0; $skipped = 0;
    $pdo->beginTransaction();
    try {
        $stCollege = $pdo->prepare('INSERT IGNORE INTO colleges (name) VALUES (?)');
        $stTheme   = $pdo->prepare('INSERT IGNORE INTO themes (name) VALUES (?)');
        $stFilm    = $pdo->prepare('INSERT INTO films (title, director, college_id, year, duration, poster, synopsis)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stFT      = $pdo->prepare('INSERT IGNORE INTO film_theme (film_id, theme_id) VALUES (?, ?)');
        $qCollege  = $pdo->prepare('SELECT id FROM colleges WHERE name = ?');
        $qTheme    = $pdo->prepare('SELECT id FROM themes WHERE name = ?');

        foreach ($rows as $r) {
            if ($r['errors']) { $skipped++; continue; }
            $d = $r['data'];
            $stCollege->execute([$d['college']]);
            $qCollege->execute([$d['college']]);
            $cid = $qCollege->fetchColumn();

            $stFilm->execute([
                $d['title'], $d['director'], $cid, (int)$d['year'],
                (int)$d['duration'], $d['poster'] !== '' ? $d['poster'] : null,
                $d['synopsis'] !== '' ? $d['synopsis'] : null,
            ]);
            $fid = $pdo->lastInsertId();

            foreach (preg_split('/[;；]/u', $d['theme']) as $t) {
                $t = trim($t);
                if ($t === '') continue;
                $stTheme->execute([$t]);
                $qTheme->execute([$t]);
                $stFT->execute([$fid, $qTheme->fetchColumn()]);
            }
            $ok++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return [$ok, $skipped];
}

/* ---------- 请求处理 ---------- */
$preview = null;   // 预览行
$token   = null;   // 暂存文件令牌
$fatal   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            $fatal = '上传失败，请选择 CSV 文件';
        } elseif ($_FILES['csv']['size'] > 2 * 1024 * 1024) {
            $fatal = '文件不能超过 2MB';
        } else {
            $token = bin2hex(random_bytes(16));
            $tmp = TMP_DIR . "/film_import_$token.csv";
            move_uploaded_file($_FILES['csv']['tmp_name'], $tmp);
            [$preview, $fatal] = parse_and_validate($tmp);
            if ($fatal) { @unlink($tmp); $token = null; }
            else $_SESSION['import_token'] = $token;
        }
    } elseif ($action === 'confirm') {
        $token = $_SESSION['import_token'] ?? '';
        $tmp = TMP_DIR . "/film_import_$token.csv";
        if (!$token || !is_file($tmp)) {
            $fatal = '导入会话已过期，请重新上传';
        } else {
            // 确认时重新解析校验，防止预览后数据变化
            [$rows, $fatal] = parse_and_validate($tmp);
            if (!$fatal) {
                try {
                    [$ok, $skipped] = import_rows($rows);
                    @unlink($tmp);
                    unset($_SESSION['import_token']);
                    flash('ok', "导入完成：成功 $ok 条" . ($skipped ? "，跳过错误行 $skipped 条" : ''));
                    redirect('/admin/import.php');
                } catch (Throwable $e) {
                    $fatal = '导入失败，已回滚，数据库未做任何修改：' . $e->getMessage();
                }
            }
            @unlink($tmp);
            unset($_SESSION['import_token']);
        }
    } elseif ($action === 'cancel') {
        $token = $_SESSION['import_token'] ?? '';
        @unlink(TMP_DIR . "/film_import_$token.csv");
        unset($_SESSION['import_token']);
        redirect('/admin/import.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>CSV 导入影片</h1>
<p class="meta">CSV 表头（首行）：<code><?= implode(',', CSV_HEADER) ?></code>　多个主题用 <code>;</code> 分隔；海报填图片文件名（上传到 uploads/posters/）或 http(s) 链接。支持 UTF-8 / GBK 编码，单文件 ≤ 2MB、≤ <?= MAX_ROWS ?> 行。</p>

<?php if ($fatal): ?><div class="alert err"><?= h($fatal) ?></div><?php endif; ?>

<?php if ($preview === null): ?>
<form method="post" enctype="multipart/form-data" class="form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="upload">
  <label>选择 CSV 文件 <input type="file" name="csv" accept=".csv,text/csv" required></label>
  <button type="submit">上传并预览</button>
</form>
<p><a href="<?= BASE_URL ?>/samples/films_sample.csv">下载示例 CSV</a></p>

<?php else:
  $errCount = count(array_filter($preview, fn($r) => $r['errors']));
  $okCount  = count($preview) - $errCount;
?>
<h2>导入预览</h2>
<p>共 <?= count($preview) ?> 行：<span class="ok-text"><?= $okCount ?> 行可导入</span>，<span class="err-text"><?= $errCount ?> 行有错误（将被跳过）</span>。确认后才会写入数据库。</p>
<table class="table preview">
  <tr><th>行号</th><th>片名</th><th>导演</th><th>学院</th><th>年份</th><th>主题</th><th>片长</th><th>海报</th><th>校验结果</th></tr>
  <?php foreach ($preview as $r): $d = $r['data']; ?>
  <tr class="<?= $r['errors'] ? 'row-err' : 'row-ok' ?>">
    <td><?= $r['line'] ?></td>
    <td><?= h($d['title'] ?? '') ?></td>
    <td><?= h($d['director'] ?? '') ?></td>
    <td><?= h($d['college'] ?? '') ?></td>
    <td><?= h($d['year'] ?? '') ?></td>
    <td><?= h($d['theme'] ?? '') ?></td>
    <td><?= h($d['duration'] ?? '') ?></td>
    <td><?= h($d['poster'] ?? '') ?></td>
    <td><?= $r['errors'] ? '<span class="err-text">' . h(implode('；', $r['errors'])) . '</span>' : '<span class="ok-text">✓</span>' ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<form method="post" class="inline-form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="confirm">
  <button type="submit" class="primary" <?= $okCount === 0 ? 'disabled' : '' ?>>确认导入 <?= $okCount ?> 条有效数据</button>
</form>
<form method="post" class="inline-form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="cancel">
  <button type="submit">取消</button>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
