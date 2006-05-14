<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

	require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';

	if($pun_config['o_pms_enabled'] && !$pun_user['is_guest'])
	{ 
?>
							<dt><?php echo $lang_pms['PM'] ?>: </dt>
							<dd><a href="message_send.php?id=<?php echo $id ?>"><?php echo $lang_pms['Quick message'] ?></a></dd>
<?php
	}
?>
