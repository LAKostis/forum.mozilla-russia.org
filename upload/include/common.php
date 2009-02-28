<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2006  LAKostis (lakostis@mozilla-russia.org)

  This file is part of Russian Mozilla Team PunBB modification.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/

// Enable DEBUG mode by removing // from the following line
//define('PUN_DEBUG', 1);

// This displays all executed queries in the page footer.
// DO NOT enable this in a production environment!
//define('PUN_SHOW_QUERIES', 1);

if (!defined('PUN_ROOT'))
	exit('The constant PUN_ROOT must be defined and point to a valid PunBB installation root directory.');


// Load the functions script
require PUN_ROOT.'include/functions.php';

// Reverse the effect of register_globals
unregister_globals();

// Make sure PHP reports all errors except E_NOTICE. PunBB supports E_ALL, but a lot of scripts it may interact with, do not.
error_reporting(E_ALL ^ E_NOTICE);

@include PUN_ROOT.'config.php';

// If PUN isn't defined, config.php is missing or corrupt
if (!defined('PUN'))
	exit('The file \'config.php\' doesn\'t exist or is corrupt. Please run <a href="install.php">install.php</a> to install PunBB first.');


// Record the start time (will be used to calculate the generation time for the page)
list($usec, $sec) = explode(' ', microtime());
$pun_start = ((float)$usec + (float)$sec);

// Turn off magic_quotes_runtime
set_magic_quotes_runtime(0);

// Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
if (get_magic_quotes_gpc())
{
	function stripslashes_array($array)
	{
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}

	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
}

// Seed the random number generator (PHP <4.2.0 only)
if (version_compare(PHP_VERSION, '4.2.0', '<'))
	mt_srand((double)microtime()*1000000);

// If a cookie name is not specified in config.php, we use the default (punbb_cookie)
if (empty($cookie_name))
	$cookie_name = 'punbb_cookie';

// Define a few commonly used constants
define('PUN_UNVERIFIED', 32000);
define('PUN_ADMIN', 1);
define('PUN_MOD', 2);
define('PUN_GUEST', 3);
define('PUN_MEMBER', 4);


// Load DB abstraction layer and connect
require PUN_ROOT.'include/dblayer/common_db.php';

// set db encoding
if ($db_type == 'mysql')
{
	// FIXME - just a hack :(
	if (!isset($language))
		$language='English';
	require PUN_ROOT.'lang/'.$language.'/common.php';
	// FIXME - we need more accurate charset handling
	if (strpos($lang_common['lang_encoding'], '8859') == false)
		define('DB_INIT_CHARSET', "SET NAMES ".$lang_common['db_lang_encoding']);
}

// Start a transaction
$db->start_transaction();

// Load cached config
@include PUN_ROOT.'cache/cache_config.php';
if (!defined('PUN_CONFIG_LOADED'))
{
	require PUN_ROOT.'include/cache.php';
	generate_config_cache();
	require PUN_ROOT.'cache/cache_config.php';
}


// Enable output buffering
if (!defined('PUN_DISABLE_BUFFERING'))
{
	// For some very odd reason, "Norton Internet Security" unsets this
	$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

	// Should we use gzip output compression?
	if ($pun_config['o_gzip'] && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');
	else
		ob_start();
}


// Check/update/set cookie and fetch user info
$pun_user = array();
check_cookie($pun_user);

// Attempt to load the common language file
@include PUN_ROOT.'lang/'.$pun_user['language'].'/common.php';
if (!isset($lang_common))
	exit('There is no valid language pack \''.pun_htmlspecialchars($pun_user['language']).'\' installed. Please reinstall a language of that name.');

// Check if we are to display a maintenance message
if ($pun_config['o_maintenance'] && $pun_user['g_id'] > PUN_ADMIN && !defined('PUN_TURN_OFF_MAINT'))
	maintenance_message();

// Load unicode support
if (strpos($lang_common['lang_encoding'], 'utf-8') !== false)
{
	require_once PUN_ROOT.'include/utf8/utf8.php';
	require_once PUN_ROOT.'include/utf8/strcasecmp.php';
	require_once PUN_ROOT.'include/utf8/ucwords.php';
}

// Load cached bans
@include PUN_ROOT.'cache/cache_bans.php';
if (!defined('PUN_BANS_LOADED'))
{
	require_once PUN_ROOT.'include/cache.php';
	generate_bans_cache();
	require PUN_ROOT.'cache/cache_bans.php';
}

// Load cached users count
@include PUN_ROOT.'cache/cache_users_count.php';
if (!defined('PUN_USERS_COUNT_LOADED'))
{
	require_once PUN_ROOT.'include/cache.php';
	generate_users_count_cache();
	require PUN_ROOT.'cache/cache_users_count.php';
}

// Load cached last user
@include PUN_ROOT.'cache/cache_last_user.php';
if (!defined('PUN_LAST_USER_LOADED'))
{
	require_once PUN_ROOT.'include/cache.php';
	generate_last_user_cache();
	require PUN_ROOT.'cache/cache_last_user.php';
}

// Load cached forums
@include PUN_ROOT.'cache/cache_forums.php';
if (!defined('PUN_FORUMS_LOADED'))
{
	require_once PUN_ROOT.'include/cache.php';
	generate_forums_cache();
	require PUN_ROOT.'cache/cache_forums.php';
}

// Load cached topics autoclose
@include PUN_ROOT.'cache/cache_topics_autoclose.php';
if (!defined('PUN_TOPICS_AUTOCLOSE_LOADED') || $pun_topics_autoclose < time())
{
	require_once PUN_ROOT.'include/cache.php';
	generate_topics_autoclose_cache();
	require PUN_ROOT.'cache/cache_topics_autoclose.php';
}

// Load cached max users
@include PUN_ROOT.'cache/cache_max_users.php';
if (!defined('PUN_MAX_USERS_LOADED'))
{
	require_once PUN_ROOT.'include/cache.php';
	generate_max_users_cache();
	require PUN_ROOT.'cache/cache_max_users.php';
}

// Check if current user is banned
if (!defined('PUN_NO_BAN'))
	check_bans();

// Update online list
update_users_online();
