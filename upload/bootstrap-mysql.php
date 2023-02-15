<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2020  LAKostis (lakostis@mozilla-russia.org)

  This file is part of Russian Mozilla Team PunBB modification.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
 eWITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


// The PunBB version this script installs
$punbb_version = '1.2.17';

// sane defaults
$db_prefix = '';

define('PUN_ROOT', './');

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


// Make sure we are running at least PHP 4.1.0
if (intval(str_replace('.', '', phpversion())) < 410)
	exit('You are running PHP version '.PHP_VERSION.'. PunBB requires at least PHP 4.1.0 to run properly. You must upgrade your PHP installation before you can continue.');

// Disable error reporting for uninitialized variables
error_reporting(E_ALL);

// Turn off PHP time limit
@set_time_limit(0);


if (!isset($_POST['form_sent']))
{
	// Determine available database extensions
	$dual_mysql = false;
	$db_extensions = [];
	if (function_exists('mysqli_connect'))
		$db_extensions[] = ['mysqli', 'MySQL Improved'];
	if (function_exists('mysql_connect'))
	{
		$db_extensions[] = ['mysql', 'MySQL Standard'];

		if (count($db_extensions) > 1)
			$dual_mysql = true;
	}

	if (empty($db_extensions))
		exit('This PHP environment does not have support for any of the databases that PunBB supports. PHP needs to have support for either MySQL, PostgreSQL or SQLite in order for PunBB to be installed.');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>PunBB Installation</title>
<link rel="stylesheet" type="text/css" href="style/Oxygen.css" />
<script type="text/javascript">
<!--
function process_form(the_form)
{
	var element_names = new Object()
	element_names["req_username"] = "Administrator username"
	element_names["req_password1"] = "Administrator password 1"
	element_names["req_password2"] = "Administrator password 2"
	element_names["req_email"] = "Administrator's e-mail"
	element_names["req_base_url"] = "Base URL"

	if (document.all || document.getElementById)
	{
		for (i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i]
			if (elem.name && elem.name.substring(0, 4) == "req_")
			{
				if (elem.type && (elem.type=="text" || elem.type=="textarea" || elem.type=="password" || elem.type=="file") && elem.value=='')
				{
					alert("\"" + element_names[elem.name] + "\" is a required field in this form.")
					elem.focus()
					return false
				}
			}
		}
	}

	return true
}
// -->
</script>
</head>
<body onload="document.getElementById('install').req_db_type.focus()">

<div id="puninstall" style="margin: auto 10% auto 10%">
<div class="pun">

<div class="block">
	<h2><span>PunBB Installation</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Welcome to PunBB installation! You are about to install PunBB. In order to install PunBB you must complete the form set out below. If you encounter any difficulties with the installation, please refer to the documentation.</p>
		</div>
	</div>
</div>

<div class="blockform">
	<h2><span>Install PunBB 1.2</span></h2>
	<div class="box">
		<form id="install" method="post" action="bootstrap.php" onsubmit="return process_form(this)">
		<div><input type="hidden" name="form_sent" value="1" /></div>
			<div class="inform">
				<div class="forminfo">
					<h3>Administration setup</h3>
					<p>Please enter the requested information in order to setup an administrator for your PunBB installation</p>
				</div>
				<fieldset>
					<legend>Enter Administrators username</legend>
					<div class="infldset">
						<p>The username of the forum administrator. You can later create more administrators and moderators. Usernames can be between 2 and 25 characters long.</p>
						<label><strong>Administrator username</strong><br /><input type="text" name="req_username" size="25" maxlength="25" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend>Enter and confirm Administrator password</legend>
					<div class="infldset">
					<p>Passwords can be between 8 and 21 characters long. Passwords are case sensitive.</p>
						<label class="conl"><strong>Password</strong><br /><input id="req_password1" type="text" name="req_password1" size="21" maxlength="21" /><br /></label>
						<label class="conl"><strong>Confirm password</strong><br /><input type="text" name="req_password2" size="21" maxlength="21" /><br /></label>
						<div class="clearer"></div>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend>Enter Administrator's e-mail</legend>
					<div class="infldset">
						<p>The e-mail address of the forum administrator.</p>
						<label for="req_email"><strong>Administrator's e-mail</strong><br /><input id="req_email" type="text" name="req_email" size="50" maxlength="50" /><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend>Enter the Base URL of your PunBB installation</legend>
					<div class="infldset">
						<p>The URL (without trailing slash) of your PunBB forum (example: http://forum.myhost.com or http://myhost.com/~myuser). This <strong>must</strong> be correct or administrators and moderators will not be able to submit any forms. Please note that the preset value below is just an educated guess by PunBB.</p>
						<label><strong>Base URL</strong><br /><input type="text" name="req_base_url" value="http://<?php echo $_SERVER['SERVER_NAME'].str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) ?>" size="60" maxlength="100" /><br /></label>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="start" value="Start install" /></p>
		</form>
	</div>
</div>

</div>
</div>

</body>
</html>
<?php

}
else
{
	if (!defined($db_prefix))
	    $db_prefix = 'punbb_';
	$username = trim($_POST['req_username']);
	$email = strtolower(trim($_POST['req_email']));
	$password1 = trim($_POST['req_password1']);
	$password2 = trim($_POST['req_password2']);


	// Make sure base_url doesn't end with a slash
	if (substr($_POST['req_base_url'], -1) == '/')
		$base_url = substr($_POST['req_base_url'], 0, -1);
	else
		$base_url = $_POST['req_base_url'];


	// Validate username and passwords
	if (strlen($username) < 2)
		error('Usernames must be at least 2 characters long. Please go back and correct.');
	if (strlen($password1) < 8)
		error('Passwords must be at least 8 characters long. Please go back and correct.');
	if ($password1 != $password2)
		error('Passwords do not match. Please go back and correct.');
	if (!strcasecmp($username, 'Guest'))
		error('The username guest is reserved. Please go back and correct.');
	if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
		error('Usernames may not be in the form of an IP address. Please go back and correct.');
	if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
		error('Usernames may not contain any of the text formatting tags (BBCode) that the forum uses. Please go back and correct.');

	if (strlen($email) > 50 || !preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email))
		error('The administrator e-mail address you entered is invalid. Please go back and correct.');


	// Load the appropriate DB layer class
	switch ($db_type)
	{
		case 'mysql':
			require PUN_ROOT.'include/dblayer/mysql.php';
			break;

		case 'mysqli':
			require PUN_ROOT.'include/dblayer/mysqli.php';
			break;

		default:
			error('\''.$db_type.'\' is not a valid database type.');
	}

	// Create the database object (and connect/select db)
	$db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, false);


	// Do some DB type specific checks
	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			break;
	}


	// Make sure PunBB isn't already installed
	$result = $db->query('SELECT 1 FROM '.$db_prefix.'users WHERE id=1');
	if ($db->num_rows($result))
		error('A table called "'.$db_prefix.'users" is already present in the database "'.$db_name.'". This could mean that PunBB is already installed or that another piece of software is installed and is occupying one or more of the table names PunBB requires. If you want to install multiple copies of PunBB in the same database, you must choose a different table prefix.');


	// Create all tables
	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."bans (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					username VARCHAR(200),
					ip VARCHAR(255),
					email VARCHAR(50),
					message VARCHAR(255),
					expire INT(10) UNSIGNED,
					initiator INT(10) UNSIGNED,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'bans. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."categories (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					cat_name VARCHAR(80) NOT NULL DEFAULT 'New Category',
					disp_position INT(10) NOT NULL DEFAULT 0,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'categories. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."censoring (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					search_for VARCHAR(60) NOT NULL DEFAULT '',
					replace_with VARCHAR(60) NOT NULL DEFAULT '',
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'censoring. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."config (
					conf_name VARCHAR(255) NOT NULL DEFAULT '',
					conf_value TEXT,
					PRIMARY KEY (conf_name)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'config. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."forum_perms (
					group_id INT(10) NOT NULL DEFAULT 0,
					forum_id INT(10) NOT NULL DEFAULT 0,
					read_forum TINYINT(1) NOT NULL DEFAULT 1,
					post_replies TINYINT(1) NOT NULL DEFAULT 1,
					post_topics TINYINT(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (group_id, forum_id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'forum_perms. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."forums (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					forum_name VARCHAR(80) NOT NULL DEFAULT 'New forum',
					forum_desc TEXT,
					redirect_url VARCHAR(100),
					moderators TEXT,
					num_topics MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
					num_posts MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
					last_post INT(10) UNSIGNED,
					last_post_id INT(10) UNSIGNED,
					last_poster VARCHAR(200),
					sort_by TINYINT(1) NOT NULL DEFAULT 0,
					disp_position INT(10) NOT NULL DEFAULT 0,
					closed TINYINT(1) NOT NULL DEFAULT '0',
					cat_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'forums. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."groups (
					g_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					g_title VARCHAR(50) NOT NULL DEFAULT '',
					g_user_title VARCHAR(50),
					g_read_board TINYINT(1) NOT NULL DEFAULT 1,
					g_post_replies TINYINT(1) NOT NULL DEFAULT 1,
					g_post_topics TINYINT(1) NOT NULL DEFAULT 1,
					g_post_polls TINYINT(1) NOT NULL DEFAULT 1,
					g_edit_posts TINYINT(1) NOT NULL DEFAULT 1,
					g_delete_posts TINYINT(1) NOT NULL DEFAULT 1,
					g_delete_topics TINYINT(1) NOT NULL DEFAULT 1,
					g_set_title TINYINT(1) NOT NULL DEFAULT 1,
					g_search TINYINT(1) NOT NULL DEFAULT 1,
					g_search_users TINYINT(1) NOT NULL DEFAULT 1,
					g_edit_subjects_interval SMALLINT(6) NOT NULL DEFAULT 300,
					g_post_flood SMALLINT(6) NOT NULL DEFAULT 30,
					g_search_flood SMALLINT(6) NOT NULL DEFAULT 30,
					g_wiki_level SMALLINT(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (g_id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'groups. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."iptrylog (
					ip VARCHAR(255) DEFAULT NULL,
					lasttry INT(10) UNSIGNED NOT NULL DEFAULT 0
					) ENGINE=HEAP;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'iptrylog. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."messages (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					owner INT(10) NOT NULL DEFAULT '0',
					subject VARCHAR(120) NOT NULL DEFAULT '',
					message TEXT,
					sender VARCHAR(120) NOT NULL DEFAULT '',
					sender_id INT(10) NOT NULL DEFAULT 0,
					posted INT(10) NOT NULL DEFAULT 0,
					sender_ip VARCHAR(120) DEFAULT NULL,
					smileys TINYINT(4) DEFAULT 1,
					status TINYINT(4) DEFAULT 0,
					showed TINYINT(4) DEFAULT 0,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'messages. Please check your settings and try again.', __FILE__, __LINE__, $db->error());

	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."online (
					user_id INT(10) UNSIGNED NOT NULL DEFAULT 1,
					ident VARCHAR(200) NOT NULL DEFAULT '',
					logged INT(10) UNSIGNED NOT NULL DEFAULT 0,
					idle TINYINT(1) NOT NULL DEFAULT 0,
					show_online TINYINT(1) NOT NULL DEFAULT 1
					) ENGINE=HEAP;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'online. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db->prefix."polls (
					id INT(11) NOT NULL AUTO_INCREMENT,
					pollid INT(11) NOT NULL DEFAULT '0',
					options LONGTEXT NOT NULL,
					voters LONGTEXT NOT NULL,
					ptype tinyint(4) NOT NULL DEFAULT '0',
					votes LONGTEXT NOT NULL,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}
	$db->query($sql) or error('Unable to create table '.$db->prefix.'polls. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."posts (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					poster VARCHAR(200) NOT NULL DEFAULT '',
					poster_id INT(10) UNSIGNED NOT NULL DEFAULT 1,
					poster_ip VARCHAR(15),
					poster_email VARCHAR(50),
					poster_uagent VARCHAR(150),
					message TEXT,
					hide_smilies TINYINT(1) NOT NULL DEFAULT 0,
					posted INT(10) UNSIGNED NOT NULL DEFAULT 0,
					edited INT(10) UNSIGNED,
					edited_by VARCHAR(200),
					topic_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					blocked TINYINT(1) NOT NULL DEFAULT 0,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'posts. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."ranks (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					rank VARCHAR(50) NOT NULL DEFAULT '',
					min_posts MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'titles. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."reports (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					post_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					topic_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					forum_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					reported_by INT(10) UNSIGNED NOT NULL DEFAULT 0,
					created INT(10) UNSIGNED NOT NULL DEFAULT 0,
					message TEXT,
					zapped INT(10) UNSIGNED,
					zapped_by INT(10) UNSIGNED,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'reports. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."search_cache (
					id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					ident VARCHAR(200) NOT NULL DEFAULT '',
					search_data TEXT,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'search_cache. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."search_matches (
					post_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					word_id MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
					subject_match TINYINT(1) NOT NULL DEFAULT 0
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'search_matches. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."search_words (
					id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
					word VARCHAR(40) BINARY NOT NULL DEFAULT '',
					PRIMARY KEY (word),
					KEY ".$db_prefix."search_words_id_idx (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'search_words. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."subscriptions (
					user_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					topic_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY (user_id, topic_id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'subscriptions. Please check your settings and try again.', __FILE__, __LINE__, $db->error());



	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."topics (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					poster VARCHAR(200) NOT NULL DEFAULT '',
					subject VARCHAR(255) NOT NULL DEFAULT '',
					posted INT(10) UNSIGNED NOT NULL DEFAULT 0,
					last_post INT(10) UNSIGNED NOT NULL DEFAULT 0,
					last_post_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					last_poster VARCHAR(200),
					num_views MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
					num_replies MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT 0,
					closed TINYINT(1) NOT NULL DEFAULT 0,
					sticky TINYINT(1) NOT NULL DEFAULT 0,
					moved_to INT(10) UNSIGNED,
					forum_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
					question VARCHAR(255) NOT NULL DEFAULT '',
					yes VARCHAR(30) NOT NULL DEFAULT '',
					no VARCHAR(30) NOT NULL DEFAULT '',
					announcement TINYINT(1) NOT NULL DEFAULT 0,
					post_sticky TINYINT(1) NOT NULL DEFAULT 0,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'topics. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."uploaded (
					id INT(11) NOT NULL DEFAULT 0,
					file TEXT NOT NULL,
					user TEXT NOT NULL
					) ENGINE=MyISAM;";
			break;
	}
	$db->query($sql) or error('Unable to create table '.$db_prefix.'uploaded. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."uploads_conf (
					g_id SMALLINT(6) NOT NULL DEFAULT 0,
					u_fsize INT(10) UNSIGNED NOT NULL DEFAULT 0,
					p_view TINYINT(4) NOT NULL DEFAULT 0,
					p_globalview TINYINT(4) NOT NULL DEFAULT 0,
					p_upload TINYINT(4) NOT NULL DEFAULT 0,
					p_delete TINYINT(4) NOT NULL DEFAULT 0,
					p_globaldelete TINYINT(4) NOT NULL DEFAULT 0,
					p_setop TINYINT(4) NOT NULL DEFAULT 0
					) ENGINE=MyISAM;";
			break;
	}
	$db->query($sql) or error('Unable to create table '.$db_prefix.'uploads_conf. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$sql = 'CREATE TABLE '.$db_prefix."users (
					id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					group_id INT(10) UNSIGNED NOT NULL DEFAULT 4,
					membergroupids VARCHAR(64) DEFAULT NULL,
					username VARCHAR(200) NOT NULL DEFAULT '',
					password VARCHAR(40) NOT NULL DEFAULT '',
					email VARCHAR(50) NOT NULL DEFAULT '',
					title VARCHAR(50),
					realname VARCHAR(40),
					url VARCHAR(100),
					jabber VARCHAR(75),
					icq VARCHAR(12),
					msn VARCHAR(50),
					aim VARCHAR(30),
					yahoo VARCHAR(30),
					location VARCHAR(30),
					use_avatar TINYINT(1) NOT NULL DEFAULT 0,
					signature TEXT,
					disp_topics TINYINT(3) UNSIGNED,
					disp_posts TINYINT(3) UNSIGNED,
					email_setting TINYINT(1) NOT NULL DEFAULT 1,
					save_pass TINYINT(1) NOT NULL DEFAULT 1,
					notify_with_post TINYINT(1) NOT NULL DEFAULT 0,
					pm_email_notify TINYINT(1) NOT NULL DEFAULT 1,
					show_smilies TINYINT(1) NOT NULL DEFAULT 1,
					show_img TINYINT(1) NOT NULL DEFAULT 1,
					show_img_sig TINYINT(1) NOT NULL DEFAULT 1,
					show_avatars TINYINT(1) NOT NULL DEFAULT 1,
					show_sig TINYINT(1) NOT NULL DEFAULT 1,
					timezone FLOAT NOT NULL DEFAULT 0,
					language VARCHAR(25) NOT NULL DEFAULT 'English',
					style VARCHAR(25) NOT NULL DEFAULT 'Oxygen',
					num_posts INT(10) UNSIGNED NOT NULL DEFAULT 0,
					last_post INT(10) UNSIGNED,
					registered INT(10) UNSIGNED NOT NULL DEFAULT 0,
					registration_ip VARCHAR(15) NOT NULL DEFAULT '0.0.0.0',
					last_visit INT(10) UNSIGNED NOT NULL DEFAULT 0,
					admin_note VARCHAR(30),
					activate_string VARCHAR(50),
					activate_key VARCHAR(8),
					show_online TINYINT(1) NOT NULL DEFAULT 1,
					read_topics MEDIUMTEXT,
					reputation_minus INT(11) UNSIGNED DEFAULT 0,
					reputation_plus INT(11) UNSIGNED DEFAULT 0,
					last_reputation_voice INT(10) UNSIGNED DEFAULT NULL,
					imgaward varchar(255) NOT NULL DEFAULT '',
					show_redirect TINYINT(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
					) ENGINE=MyISAM;";
			break;
	}

	$db->query($sql) or error('Unable to create table '.$db_prefix.'users. Please check your settings and try again.', __FILE__, __LINE__, $db->error());


	// Add some indexes
	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
			// We use MySQL's ALTER TABLE ... ADD INDEX syntax instead of CREATE INDEX to avoid problems with users lacking the INDEX privilege
			$queries[] = 'ALTER TABLE '.$db_prefix.'online ADD UNIQUE INDEX '.$db_prefix.'online_user_id_ident_idx(user_id,ident)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'online ADD INDEX '.$db_prefix.'online_user_id_idx(user_id)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'posts ADD INDEX '.$db_prefix.'posts_topic_id_idx(topic_id)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'posts ADD INDEX '.$db_prefix.'posts_multi_idx(poster_id, topic_id)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'reports ADD INDEX '.$db_prefix.'reports_zapped_idx(zapped)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'search_matches ADD INDEX '.$db_prefix.'search_matches_word_id_idx(word_id)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'search_matches ADD INDEX '.$db_prefix.'search_matches_post_id_idx(post_id)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'topics ADD INDEX '.$db_prefix.'topics_forum_id_idx(forum_id)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'topics ADD INDEX '.$db_prefix.'topics_moved_to_idx(moved_to)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'users ADD INDEX '.$db_prefix.'users_registered_idx(registered)';
			$queries[] = 'ALTER TABLE '.$db_prefix.'search_cache ADD INDEX '.$db_prefix.'search_cache_ident_idx(ident(8))';
			$queries[] = 'ALTER TABLE '.$db_prefix.'users ADD INDEX '.$db_prefix.'users_username_idx(username(8))';
			break;

		default:
			$queries[] = 'CREATE INDEX '.$db_prefix.'online_user_id_idx ON '.$db_prefix.'online(user_id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'posts_topic_id_idx ON '.$db_prefix.'posts(topic_id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'posts_multi_idx ON '.$db_prefix.'posts(poster_id, topic_id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'reports_zapped_idx ON '.$db_prefix.'reports(zapped)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'search_matches_word_id_idx ON '.$db_prefix.'search_matches(word_id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'search_matches_post_id_idx ON '.$db_prefix.'search_matches(post_id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'topics_forum_id_idx ON '.$db_prefix.'topics(forum_id)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'topics_moved_to_idx ON '.$db_prefix.'topics(moved_to)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'users_registered_idx ON '.$db_prefix.'users(registered)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'users_username_idx ON '.$db_prefix.'users(username)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'search_cache_ident_idx ON '.$db_prefix.'search_cache(ident)';
			$queries[] = 'CREATE INDEX '.$db_prefix.'search_words_id_idx ON '.$db_prefix.'search_words(id)';
			break;
	}

	@reset($queries);
	while (list(, $sql) = @each($queries))
		$db->query($sql) or error('Unable to create indexes. Please check your configuration and try again.', __FILE__, __LINE__, $db->error());



	$now = time();

	// Insert the four preset groups
	$db->query('INSERT INTO '.$db->prefix."groups (g_title, g_user_title, g_read_board, g_post_replies, g_post_topics, g_post_polls, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_edit_subjects_interval, g_post_flood, g_search_flood) VALUES('Administrators', 'Administrator', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)") or error('Unable to add group', __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix."groups (g_title, g_user_title, g_read_board, g_post_replies, g_post_topics, g_post_polls, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_edit_subjects_interval, g_post_flood, g_search_flood) VALUES('Moderators', 'Moderator', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0)") or error('Unable to add group', __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix."groups (g_title, g_user_title, g_read_board, g_post_replies, g_post_topics, g_post_polls, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_edit_subjects_interval, g_post_flood, g_search_flood) VALUES('Guest', NULL, 1, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0)") or error('Unable to add group', __FILE__, __LINE__, $db->error());
	$db->query('INSERT INTO '.$db->prefix."groups (g_title, g_user_title, g_read_board, g_post_replies, g_post_topics, g_post_polls, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_edit_subjects_interval, g_post_flood, g_search_flood) VALUES('Members', NULL, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 300, 60, 30)") or error('Unable to add group', __FILE__, __LINE__, $db->error());

	// Insert guest and first admin user
	$db->query('INSERT INTO '.$db_prefix."users (group_id, username, password, email) VALUES(3, 'Guest', 'Guest', 'Guest')")
		or error('Unable to add guest user. Please check your configuration and try again.');

	$db->query('INSERT INTO '.$db_prefix."users (group_id, username, password, email, num_posts, last_post, registered, registration_ip, last_visit) VALUES(1, '".$db->escape($username)."', '".pun_hash($password1)."', '$email', 1, ".$now.", ".$now.", '127.0.0.1', ".$now.')')
		or error('Unable to add administrator user. Please check your configuration and try again.');


	// Insert initial uploader user
	$db->query('INSERT INTO '.$db_prefix."uploads_conf (g_id, u_fsize, p_view, p_globalview, p_upload, p_delete, p_globaldelete, p_setop) VALUES(1, 0, 1, 1, 1, 1, 1, 1)") or error('Unable to add uploader config', __FILE__, __LINE__, $db->error());

	// Insert config data
	$config = [
		'o_cur_version'				=> "'$punbb_version'",
		'o_board_title'				=> "'My PunBB forum'",
		'o_board_desc'				=> "'Unfortunately no one can be told what PunBB is - you have to see it for yourself.'",
		'o_server_timezone'			=> "'0'",
		'o_time_format'				=> "'H:i:s'",
		'o_date_format'				=> "'Y-m-d'",
		'o_timeout_login'			=> "'10'",
		'o_timeout_visit'			=> "'600'",
		'o_timeout_online'			=> "'300'",
		'o_redirect_delay'			=> "'1'",
		'o_show_version'			=> "'0'",
		'o_show_user_info'			=> "'1'",
		'o_show_post_count'			=> "'1'",
		'o_smilies'					=> "'1'",
		'o_smilies_sig'				=> "'1'",
		'o_make_links'				=> "'1'",
		'o_default_lang'			=> "'English'",
		'o_default_style'			=> "'Oxygen'",
		'o_default_user_group'		=> "'4'",
		'o_topic_review'			=> "'15'",
		'o_disp_topics_default'		=> "'30'",
		'o_disp_posts_default'		=> "'25'",
		'o_indent_num_spaces'		=> "'4'",
		'o_quickpost'				=> "'1'",
		'o_users_online'			=> "'1'",
		'o_censoring'				=> "'0'",
		'o_ranks'					=> "'1'",
		'o_show_dot'				=> "'0'",
		'o_quickjump'				=> "'1'",
		'o_gzip'					=> "'0'",
		'o_additional_navlinks'		=> "''",
		'o_report_method'			=> "'0'",
		'o_regs_report'				=> "'0'",
		'o_mailing_list'			=> "'$email'",
		'o_jabber_list'				=> "''",
		'o_avatars'					=> "'1'",
		'o_avatars_dir'				=> "'img/avatars'",
		'o_avatars_width'			=> "'72'",
		'o_avatars_height'			=> "'72'",
		'o_avatars_size'			=> "'10240'",
		'o_search_all_forums'		=> "'1'",
		'o_base_url'				=> "'$base_url'",
		'o_admin_email'				=> "'$email'",
		'o_webmaster_email'			=> "'$email'",
		'o_subscriptions'			=> "'1'",
		'o_smtp_host'				=> "NULL",
		'o_smtp_user'				=> "NULL",
		'o_smtp_pass'				=> "NULL",
		'o_regs_allow'				=> "'1'",
		'o_regs_verify'				=> "'0'",
		'o_announcement'			=> "'0'",
		'o_announcement_message'	=> "'Enter your announcement here.'",
		'o_rannouncement'			=> "'0'",
		'o_rannouncement_message'	=> "'Enter your first announcement here.\nEnter your second announcement here.'",
		'o_iconize_subforums'		=> "''",
		'o_autoclose_subforums'		=> "''",
		'o_autoclose_timeout'		=> "'730'",
		'o_urls_in_signature'		=> "'50'",
		'o_rules'					=> "'0'",
		'o_rules_message'			=> "'Enter your rules here.'",
		'o_maintenance'				=> "'0'",
		'o_maintenance_message'		=> "'The forums are temporarily down for maintenance. Please try again in a few minutes.<br />\\n<br />\\n/Administrator'",
		'p_mod_edit_users'			=> "'1'",
		'p_mod_rename_users'		=> "'0'",
		'p_mod_change_passwords'	=> "'0'",
		'p_mod_ban_users'			=> "'0'",
		'p_message_bbcode'			=> "'1'",
		'p_message_img_tag'			=> "'1'",
		'p_message_all_caps'		=> "'1'",
		'p_subject_all_caps'		=> "'1'",
		'p_sig_all_caps'			=> "'1'",
		'p_sig_bbcode'				=> "'1'",
		'p_sig_img_tag'				=> "'0'",
		'p_sig_length'				=> "'400'",
		'p_sig_lines'				=> "'4'",
		'p_allow_banned_email'		=> "'1'",
		'p_allow_dupe_email'		=> "'0'",
		'p_force_guest_email'		=> "'1'",
		'o_pms_enabled'				=> "'1'",
		'o_pms_messages'			=> "'50'",
		'o_pms_mess_per_page'		=> "'10'",
		'o_polls' 					=> "'0'",
		'o_poll_change' 			=> "'1'",
		'o_poll_multi' 				=> "'0'",
		'p_guests_poll'				=> "'0'",
		'o_regs_verify_image' 		=> "'0'",
		'poll_max_fields' 			=> "'10'",
		'o_reputation_enabled' 		=> "'0'",
		'o_reputation_timeout' 		=> "'120'",
		'o_timeout_login' 			=> "'10'",
		'o_merge_timeout'			=> "'300'",
		'o_message_counter_exceptions'		=> "''",
		'o_spamreport_whitelist'	=> "''",
		'o_spamreport_blacklist'	=> "''",
		'o_spamreport_forums'		=> "''",
		'o_spamreport_count'		=> "'2'"
	];

	while (list($conf_name, $conf_value) = @each($config))
	{
		$db->query('INSERT INTO '.$db_prefix."config (conf_name, conf_value) VALUES('$conf_name', $conf_value)")
			or error('Unable to insert into table '.$db_prefix.'config. Please check your configuration and try again.');
	}

	// Insert some other default data
	$db->query('INSERT INTO '.$db_prefix."categories (cat_name, disp_position) VALUES('Test category', 1)")
		or error('Unable to insert into table '.$db_prefix.'categories. Please check your configuration and try again.');

	$db->query('INSERT INTO '.$db_prefix."forums (forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, disp_position, cat_id) VALUES('Test forum', 'This is just a test forum', 1, 1, ".$now.", 1, '".$db->escape($username)."', 1, 1)")
		or error('Unable to insert into table '.$db_prefix.'forums. Please check your configuration and try again.');

	$db->query('INSERT INTO '.$db_prefix."topics (poster, subject, posted, last_post, last_post_id, last_poster, forum_id) VALUES('".$db->escape($username)."', 'Test post', ".$now.", ".$now.", 1, '".$db->escape($username)."', 1)")
		or error('Unable to insert into table '.$db_prefix.'topics. Please check your configuration and try again.');

	$db->query('INSERT INTO '.$db_prefix."posts (poster, poster_id, poster_ip, message, posted, topic_id) VALUES('".$db->escape($username)."', 2, '127.0.0.1', 'If you are looking at this (which I guess you are), the install of PunBB appears to have worked! Now log in and head over to the administration control panel to configure your forum.', ".$now.', 1)')
		or error('Unable to insert into table '.$db_prefix.'posts. Please check your configuration and try again.');

	$db->query('INSERT INTO '.$db_prefix."ranks (rank, min_posts) VALUES('New member', 0)")
		or error('Unable to insert into table '.$db_prefix.'ranks. Please check your configuration and try again.');

	$db->query('INSERT INTO '.$db_prefix."ranks (rank, min_posts) VALUES('Member', 10)")
		or error('Unable to insert into table '.$db_prefix.'ranks. Please check your configuration and try again.');


	if ($db_type == 'pgsql' || $db_type == 'sqlite')
		$db->end_transaction();



	$alerts = '';
	// Check if the cache directory is writable
	if (!@is_writable('./cache/'))
		$alerts .= '<p style="font-size: 1.1em"><span style="color: #C03000"><strong>The cache directory is currently not writable!</strong></span> In order for PunBB to function properly, the directory named <em>cache</em> must be writable by PHP. Use chmod to set the appropriate directory permissions. If in doubt, chmod to 0777.</p>';

	// Check if default avatar directory is writable
	if (!@is_writable('./img/avatars/'))
		$alerts .= '<p style="font-size: 1.1em"><span style="color: #C03000"><strong>The avatar directory is currently not writable!</strong></span> If you want users to be able to upload their own avatar images you must see to it that the directory named <em>img/avatars</em> is writable by PHP. You can later choose to save avatar images in a different directory (see Admin/Options). Use chmod to set the appropriate directory permissions. If in doubt, chmod to 0777.</p>';


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>PunBB Installation</title>
<link rel="stylesheet" type="text/css" href="style/Oxygen.css" />
</head>
<body>

<div id="puninstall" style="margin: auto 10% auto 10%">
<div class="pun">

<div class="blockform">
	<h2>Final instructions</h2>
	<div class="box">
		<div class="fakeform">
			<div class="inform">
				<div class="forminfo">
					<p>Congratulations, your PunBB is installed!</p>
					<p><a href="index.php">Go to forum index</a></p>
				</div>
			</div>
		</div>
	</div>
</div>

</div>
</div>

</body>
</html>
<?php

}
