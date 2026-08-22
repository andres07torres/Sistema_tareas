<?php
require_once '../config/auth.php';
startSession();

session_unset();
session_destroy();

header('Location: login.php');
exit;
