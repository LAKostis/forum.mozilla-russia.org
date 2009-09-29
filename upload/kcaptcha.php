<?php

include 'include/kcaptcha/kcaptcha.php';

$captcha = new KCAPTCHA();

if(isset($_REQUEST[session_name()]))
{
	session_start();
	$_SESSION['text'] = $captcha->getKeyString();
}

?>
