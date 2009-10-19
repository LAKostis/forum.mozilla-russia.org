<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

	require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';

	if($pun_config['o_pms_enabled'] && !$pun_user['is_guest'])
	{
		$pid = isset($cur_post['poster_id']) ? $cur_post['poster_id'] : $cur_post['id'];
		if($pid != $pun_user['id'])
		  $user_contacts[] = '<a href="message_send.php?id='.$pid.'&amp;tid='.$id.'" class="pm">'.$lang_pms['PM'].'</a>';
	}
?>
