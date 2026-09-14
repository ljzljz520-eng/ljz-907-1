<?php
// 数据库配置 —— 部署时按实际环境修改
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'campus_film');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', '/campus-film');   // 站点子目录，根目录部署时改为 ''
define('POSTER_DIR', __DIR__ . '/uploads/posters');
define('POSTER_URL', BASE_URL . '/uploads/posters');
define('TMP_DIR', sys_get_temp_dir());

session_start();
