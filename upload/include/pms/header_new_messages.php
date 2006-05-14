<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

	if(!$pun_user['is_guest']){
		require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';
		
		// Check for new messages
		$result_messages = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'messages WHERE showed=0 AND owner='.$pun_user['id']) or error('Unable to check for new messages', __FILE__, __LINE__, $db->error());
		if ($db->result($result_messages, 0)){
			$tpl_temp .= "\n\t\t\t\t".'<li class="pmlink"><strong><a href="message_list.php">'.$lang_pms['New messages'].'</a></strong></li>';
		}
		// Check if the inbox is full
		if($pun_config['o_pms_messages'] != 0 && $pun_user['g_id'] > PUN_GUEST){
			$result = $db->query('SELECT count(*) FROM '.$db->prefix.'messages WHERE owner='.$pun_user['id']) or error('Unable to test if the message-box is full', __FILE__, __LINE__, $db->error());
			list($count) = $db->fetch_row($result);
	
			// Display error message
			if($count >= $pun_config['o_pms_messages'])
				$tpl_temp .= "\n\t\t\t\t".'<li class="pmlink"><strong><a href="message_list.php">'.$lang_pms['Full inbox'].'</a></strong></li>';
		}
	}
?>
