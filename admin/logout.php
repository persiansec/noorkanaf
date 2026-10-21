<?php
require __DIR__ . '/../inc/core.php';
admin_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = [];
    session_destroy();
}
redirect('login.php');
