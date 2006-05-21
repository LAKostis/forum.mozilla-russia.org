<?php

// some ACL level defines
define('AUTH_NONE',0);
define('AUTH_READ',1);
define('AUTH_EDIT',2);
define('AUTH_CREATE',4);
define('AUTH_UPLOAD',8);
define('AUTH_GRANT',255);

function auth_quickaclcheck($id){
	return auth_aclcheck($id);
}

function auth_aclcheck($id,$user = 0,$groups = 0){
	global $pun_user;
	return $pun_user['g_wiki_level'];
}

?>
