<?php

//
// error_reporting(0);

include 'include/kcaptcha/kcaptcha.php';

session_start();

$captcha = new KCAPTCHA();

$_SESSION['text'] = $captcha->getKeyString();

?>
