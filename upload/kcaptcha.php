<?php

include 'include/kcaptcha/kcaptcha.php';

session_start();

$captcha = new KCAPTCHA();

$_SESSION['text'] = $captcha->getKeyString();

?>
