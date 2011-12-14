<?php

include 'include/kcaptcha/kcaptcha.php';

session_start();

$captcha = new KCAPTCHA();

if(isset($_REQUEST[session_name()])) {
	$_SESSION['text'] = $captcha->getKeyString();
}

?>
