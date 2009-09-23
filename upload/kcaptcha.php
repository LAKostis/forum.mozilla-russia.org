<?php

//
// error_reporting(0);

include 'include/kcaptcha/kcaptcha.php';

if(isset($_REQUEST[session_name()]))
	session_start();

$captcha = new KCAPTCHA();

if($_REQUEST[session_name()])
	$_SESSION['text'] = $captcha->getKeyString();


?>
