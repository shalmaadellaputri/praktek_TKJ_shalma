<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_post('admin/login.php');
logout_user();
redirect('login.php');
