<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
session_destroy();
session_start();
session_regenerate_id(true);
redirect(BASE_URL . '/auth/login.php');
