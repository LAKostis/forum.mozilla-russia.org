<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

	require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';

	if($pun_config['o_pms_enabled'])
		$links[] = '<li id="navpm"><a href="message_list.php">'.$lang_pms['Messages'].'</a>';	
?>
