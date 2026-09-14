<?php
require_once __DIR__ . '/includes/helpers.php';
$_SESSION = [];
session_destroy();
session_start();
flash('ok', '已退出登录');
redirect('/index.php');
