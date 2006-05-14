<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

	// Delete users private messages
	$db->query('DELETE FROM '.$db->prefix.'messages WHERE owner='.$user_id) or error('Unable to delete users messages', __FILE__, __LINE__, $db->error());

?>
