<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2015  LAKostis (lakostis@mozilla-russia.org)

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


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';


// If we are logged in, we shouldn't be here
if (!$pun_user['is_guest'])
{
	hidden_redirect('index.php');
	exit;
}

// Load the register.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

// Load the register.php/profile.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/prof_reg.php';

if ($pun_config['o_regs_allow'] == '0')
	message($lang_register['No new regs']);


// User pressed the cancel button
if (isset($_GET['cancel']))
	redirect('index.php', $lang_register['Reg cancel redirect']);


else if ($pun_config['o_rules'] == '1' && !isset($_GET['agree']) && !isset($_POST['form_sent']))
{
	$page_title = pun_htmlspecialchars($lang_register['Register']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	require PUN_ROOT.'header.php';

?>
<div class="blockform">
	<h2><span><?php echo $lang_register['Forum rules'] ?></span></h2>
	<div class="box">
		<form method="get" action="register.php">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_register['Rules legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $pun_config['o_rules_message'] ?></p>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="agree" value="<?php echo $lang_register['Agree'] ?>" /><input type="submit" name="cancel" value="<?php echo $lang_register['Cancel'] ?>" /></p>
		</form>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if (isset($_POST['form_sent'], $_POST['req_username'], $_POST['req_email1']))
{
	$username = pun_trim($_POST['req_username']);
	$email1 = strtolower(trim($_POST['req_email1']));

	if ($pun_config['o_regs_verify'] == '1')
	{
		$email2 = isset($_POST['req_email2']) ? strtolower(trim($_POST['req_email2'])) : '';

		$password1 = random_pass(8);
		$password2 = $password1;
	}
	else
	{
		$password1 = isset($_POST['req_password1']) ? trim($_POST['req_password1']) : '';
		$password2 = isset($_POST['req_password2']) ? trim($_POST['req_password2']) : '';
	}

	// Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
	$username = preg_replace('#\s+#s', ' ', $username);

	// Validate username and passwords
	if (strlen($username) < 2)
		message($lang_prof_reg['Username too short']);
	else if (pun_strlen($username) > 25)	// This usually doesn't happen since the form element only accepts 25 characters
	    message($lang_common['Bad request']);
	else if (strlen($password1) < 4)
		message($lang_prof_reg['Pass too short']);
	else if ($password1 != $password2)
		message($lang_prof_reg['Pass not match']);
	else if (!strcasecmp($username, 'Guest') || !pun_strcasecmp($username, $lang_common['Guest']))
		message($lang_prof_reg['Username guest']);
	else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
		message($lang_prof_reg['Username IP']);
	else if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		message($lang_prof_reg['Username reserved chars']);
	else if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[quote=|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
		message($lang_prof_reg['Username BBCode']);

	// Check username for any censored words
	if ($pun_config['o_censoring'] == '1')
	{
		// If the censored username differs from the username
		if (censor_words($username) != $username)
			message($lang_register['Username censor']);
	}

	// Image verifcation
	if ($pun_config['o_regs_verify_image'] == '1')
	{
		require_once("recaptcha.php");
	}

	$listed_usernames = file(PUN_ROOT.'cache/listed_username_1.txt', FILE_IGNORE_NEW_LINES);
	if($listed_usernames) {
		foreach ($listed_usernames as $listed_username) {
			if ($username == urldecode($listed_username))
				error('Unable to create user', 'register.php','', '');
		}
	}

	// Check that the username (or a too similar username) is not already registered
	$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE UPPER(username)=UPPER(\''.$db->escape($username).'\') OR UPPER(username)=UPPER(\''.$db->escape(preg_replace('/[^\w]/', '', $username)).'\')') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

	if ($db->num_rows($result))
	{
		$busy = $db->result($result);
		message($lang_register['Username dupe 1'].' '.pun_htmlspecialchars($busy).'. '.$lang_register['Username dupe 2']);
	}


	// Validate e-mail
	require PUN_ROOT.'include/email.php';

	// ... and jabber
	require PUN_ROOT.'include/jabber.php';

	if (!is_valid_reg_email($email1))
		message($lang_common['Invalid e-mail']);
	else if ($pun_config['o_regs_verify'] == '1' && $email1 != $email2)
		message($lang_register['E-mail not match']);

	// Check it it's a banned e-mail address
	if (is_banned_email($email1))
	{
		if ($pun_config['p_allow_banned_email'] == '0')
			message($lang_prof_reg['Banned e-mail']);

		$banned_email = true;	// Used later when we send an alert e-mail
	}
	else
		$banned_email = false;

	// Check if someone else already has registered with that e-mail address
	$dupe_list = [];

	$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE email=\''.$email1.'\'') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
	{
		if ($pun_config['p_allow_dupe_email'] == '0')
			message($lang_prof_reg['Dupe e-mail']);

		while ($cur_dupe = $db->fetch_assoc($result))
			$dupe_list[] = $cur_dupe['username'];
	}

	// Make sure we got a valid language string
	if (isset($_POST['language']))
	{
		$language = preg_replace('#[\.\\\/]#', '', (string) $_POST['language']);
		if (!file_exists(PUN_ROOT.'lang/'.$language.'/common.php'))
				message($lang_common['Bad request']);
	}
	else
		$language = $pun_config['o_default_lang'];

	if (isset($_POST['timezone']))
		$timezone = round($_POST['timezone'], 1);
	else
		message($lang_common['Bad request']);

	$save_pass = !isset($_POST['save_pass']) || $_POST['save_pass'] != '1' ? '0' : '1';

	if (isset($_POST['email_setting']))
		$email_setting = intval($_POST['email_setting']);
	else
		message($lang_common['Bad request']);

	if ($email_setting < 0 || $email_setting > 2)
		$email_setting = 1;

	// Insert the new user into the database. We do this now to get the last inserted id for later use.
	$now = time();

	$intial_group_id = $pun_config['o_regs_verify'] == '0' ? $pun_config['o_default_user_group'] : PUN_UNVERIFIED;
	$password_hash = pun_hash($password1);

	// NeoSecurityTeam PunBB 1.2.10 DoS Patch by K4P0
	$reglimit = $now - 60*5; // Lets wait for 5 minutes.
	$result = $db->query('SELECT * FROM '.$db->prefix.'users WHERE registration_ip =\''.get_remote_address().'\' AND registered > \''.$reglimit.'\'') or error('Unable to create user', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result)) error('Please wait some minutes to register again', 'register.php', '', '');
	// IP doesn't try to flood our forum, insert user safely. :)

	$listed_ips = file(PUN_ROOT.'cache/listed_ip_1.txt', FILE_IGNORE_NEW_LINES);
	if($listed_ips) {
		foreach ($listed_ips as $listed_ip) {
			if (get_remote_address() == $listed_ip)
				error('Unable to create user', 'register.php','', '');
		}
	}

	// final clearance check
	require_once(PUN_ROOT.'include/stopforumspam.php');
	$sfs = new StopForumSpam();
	$args = ['email' => $email1, 'ip' => get_remote_address(), 'username' => $username];

	$spamcheck = $sfs->is_spammer( $args );
	if ($spamcheck['spammer']=='1' && $spamcheck['known']=='1')
		error('Unable to create user', 'register.php','', '');

	// Add the user
	$db->query('INSERT INTO '.$db->prefix.'users (username, group_id, password, email, email_setting, save_pass, timezone, language, style, registered, registration_ip, last_visit) VALUES(\''.$db->escape($username).'\', '.$intial_group_id.', \''.$password_hash.'\', \''.$email1.'\', '.$email_setting.', '.$save_pass.', '.$timezone.' , \''.$db->escape($language).'\', \''.$pun_config['o_default_style'].'\', '.$now.', \''.get_remote_address().'\', '.$now.')') or error('Unable to create user', __FILE__, __LINE__, $db->error());
	$new_uid = $db->insert_id();

	// Regenerate the users count cache
	require_once PUN_ROOT.'include/cache.php';
	generate_users_count_cache();

	// Regenerate the last user cache
	require_once PUN_ROOT.'include/cache.php';
	generate_last_user_cache();

	// If we previously found out that the e-mail was banned
	if ($banned_email)
	{
		if ($pun_config['o_mailing_list'] != '')
		{
			$mail_subject = 'Alert - Banned e-mail detected';
			$mail_message = 'User \''.$username.'\' registered with banned e-mail address: '.$email1."\n\n".'User profile: '.$pun_config['o_base_url'].'/profile.php?id='.$new_uid."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

			pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
		}

		if ($pun_config['o_jabber_list'] != '')
		{
			$jabber_message = 'User \''.$username.'\' registered with banned e-mail address: '.$email1."\n\n".'User profile: '.$pun_config['o_base_url'].'/profile.php?id='.$new_uid;

			pun_jabber($pun_config['o_jabber_list'], $jabber_message);
		}
	}

	// If we previously found out that the e-mail was a dupe
	if (!empty($dupe_list))
	{
		if ($pun_config['o_mailing_list'] != '')
		{
			$mail_subject = 'Alert - Duplicate e-mail detected';
			$mail_message = 'User \''.$username.'\' registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.$pun_config['o_base_url'].'/profile.php?id='.$new_uid."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

			pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
		}

		if ($pun_config['o_jabber_list'] != '')
		{
			$jabber_message = 'User \''.$username.'\' registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.$pun_config['o_base_url'].'/profile.php?id='.$new_uid;

			pun_jabber($pun_config['o_jabber_list'], $jabber_message);
		}
	}

	// Should we alert people on the admin mailing list that a new user has registered?
	if ($pun_config['o_regs_report'] == '1')
	{
		if ($pun_config['o_mailing_list'] != '')
		{
			$mail_subject = 'Alert - New registration';
			$mail_message = 'User \''.$username.'\' registered in the forums at '.$pun_config['o_base_url']."\n\n".'User profile: '.$pun_config['o_base_url'].'/profile.php?id='.$new_uid."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';

			pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
		}

		if ($pun_config['o_jabber_list'] != '')
		{
			$jabber_message = 'User \''.$username.'\' registered in the forums at '.$pun_config['o_base_url']."\n\n".'User profile: '.$pun_config['o_base_url'].'/profile.php?id='.$new_uid;

			pun_jabber($pun_config['o_jabber_list'], $jabber_message);
		}
	}

	// Must the user verify the registration or do we log him/her in right now?
	if ($pun_config['o_regs_verify'] == '1')
	{
		// Load the "welcome" template
		$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/welcome.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<board_title>', $pun_config['o_board_title'], $mail_subject);
		$mail_message = str_replace('<base_url>', $pun_config['o_base_url'].'/', $mail_message);
		$mail_message = str_replace('<username>', $username, $mail_message);
		$mail_message = str_replace('<password>', $password1, $mail_message);
		$mail_message = str_replace('<login_url>', $pun_config['o_base_url'].'/login.php', $mail_message);
		$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'].' '.$lang_common['Mailer'], $mail_message);

		pun_mail($email1, $mail_subject, $mail_message);

		message($lang_register['Reg e-mail'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.', true);
	}

	pun_setcookie($new_uid, $password_hash, $save_pass != '0' ? $now + 31536000 : 0);

	redirect('index.php', $lang_register['Reg complete']);
}


$page_title = pun_htmlspecialchars($lang_register['Register']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
$required_fields = ['req_username' => $lang_common['Username'], 'req_password1' => $lang_common['Password'], 'req_password2' => $lang_prof_reg['Confirm pass'], 'req_email1' => $lang_common['E-mail'], 'req_email2' => $lang_common['E-mail'].' 2', 'req_image' => $lang_register['Image text']];
$focus_element = ['register', 'req_username'];
require PUN_ROOT.'header.php';

?>
<div class="blockform">
	<h2><span><?php echo $lang_register['Register'] ?></span></h2>
	<div class="box">
		<form id="register" method="post" action="register.php?action=register" onsubmit="return process_form(this)">
			<div class="inform">
				<div class="forminfo">
					<h3><?php echo $lang_common['Important information'] ?></h3>
					<p><?php echo $lang_register['Desc 1'] ?></p>
					<p><?php echo $lang_register['Desc 2'] ?></p>
				</div>
				<fieldset>
					<legend><?php echo $lang_register['Username legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="form_sent" value="1" />
						<label><strong><?php echo $lang_common['Username'] ?></strong><br /><input type="text" name="req_username" size="25" maxlength="25" /><br /></label>
					</div>
				</fieldset>
			</div>
<?php if ($pun_config['o_regs_verify_image'] == '1'): ?>
	<div class="inform">
		<fieldset>
			<legend><?php echo $lang_register['Image verification'] ?></legend>
			<div class="infldset">
			<div class="g-recaptcha" data-sitekey="6LeuiXYUAAAAADW9ew-ye8BFi0vI0IPJiZQ-GV9u"></div>
			</div>
		</fieldset>
	</div>
<?php endif; ?>
<?php if ($pun_config['o_regs_verify'] == '0'): ?>			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_register['Pass legend 1'] ?></legend>
					<div class="infldset">
						<label class="conl"><strong><?php echo $lang_common['Password'] ?></strong><br /><input type="password" name="req_password1" size="16" maxlength="16" /><br /></label>
						<label class="conl"><strong><?php echo $lang_prof_reg['Confirm pass'] ?></strong><br /><input type="password" name="req_password2" size="16" maxlength="16" /><br /></label>
						<p class="clearb"><?php echo $lang_register['Pass info'] ?></p>
					</div>
				</fieldset>
			</div>
<?php endif; ?>			<div class="inform">
				<fieldset>
					<legend><?php echo $pun_config['o_regs_verify'] == '1' ? $lang_prof_reg['E-mail legend 2'] : $lang_prof_reg['E-mail legend'] ?></legend>
					<div class="infldset">
<?php if ($pun_config['o_regs_verify'] == '1'): ?>			<p><?php echo $lang_register['E-mail info'] ?></p>
<?php endif; ?>					<label><strong><?php echo $lang_common['E-mail'] ?></strong><br />
						<input type="text" name="req_email1" size="50" maxlength="50" /><br /></label>
<?php if ($pun_config['o_regs_verify'] == '1'): ?>						<label><strong><?php echo $lang_register['Confirm e-mail'] ?></strong><br />
						<input type="text" name="req_email2" size="50" maxlength="50" /><br /></label>
<?php endif; ?>					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_prof_reg['Localisation legend'] ?></legend>
					<div class="infldset">
						<label><?php echo $lang_prof_reg['Timezone'] ?>: <?php echo $lang_prof_reg['Timezone info'] ?>
						<br /><select id="time_zone" name="timezone">
							<option value="-12"<?php if ($pun_config['o_server_timezone'] == -12 ) echo ' selected="selected"' ?>>-12</option>
							<option value="-11"<?php if ($pun_config['o_server_timezone'] == -11) echo ' selected="selected"' ?>>-11</option>
							<option value="-10"<?php if ($pun_config['o_server_timezone'] == -10) echo ' selected="selected"' ?>>-10</option>
							<option value="-9.5"<?php if ($pun_config['o_server_timezone'] == -9.5) echo ' selected="selected"' ?>>-9.5</option>
							<option value="-9"<?php if ($pun_config['o_server_timezone'] == -9 ) echo ' selected="selected"' ?>>-09</option>
							<option value="-8.5"<?php if ($pun_config['o_server_timezone'] == -8.5) echo ' selected="selected"' ?>>-8.5</option>
							<option value="-8"<?php if ($pun_config['o_server_timezone'] == -8 ) echo ' selected="selected"' ?>>-08 PST</option>
							<option value="-7"<?php if ($pun_config['o_server_timezone'] == -7 ) echo ' selected="selected"' ?>>-07 MST</option>
							<option value="-6"<?php if ($pun_config['o_server_timezone'] == -6 ) echo ' selected="selected"' ?>>-06 CST</option>
							<option value="-5"<?php if ($pun_config['o_server_timezone'] == -5 ) echo ' selected="selected"' ?>>-05 EST</option>
							<option value="-4"<?php if ($pun_config['o_server_timezone'] == -4 ) echo ' selected="selected"' ?>>-04 AST</option>
							<option value="-3.5"<?php if ($pun_config['o_server_timezone'] == -3.5) echo ' selected="selected"' ?>>-3.5</option>
							<option value="-3"<?php if ($pun_config['o_server_timezone'] == -3 ) echo ' selected="selected"' ?>>-03 ADT</option>
							<option value="-2"<?php if ($pun_config['o_server_timezone'] == -2 ) echo ' selected="selected"' ?>>-02</option>
							<option value="-1"<?php if ($pun_config['o_server_timezone'] == -1) echo ' selected="selected"' ?>>-01</option>
							<option value="0"<?php if ($pun_config['o_server_timezone'] == 0) echo ' selected="selected"' ?>>00 GMT</option>
							<option value="1"<?php if ($pun_config['o_server_timezone'] == 1) echo ' selected="selected"' ?>>+01 CET</option>
							<option value="2"<?php if ($pun_config['o_server_timezone'] == 2 ) echo ' selected="selected"' ?>>+02</option>
							<option value="3"<?php if ($pun_config['o_server_timezone'] == 3 ) echo ' selected="selected"' ?>>+03</option>
							<option value="3.5"<?php if ($pun_config['o_server_timezone'] == 3.5 ) echo ' selected="selected"' ?>>+03.5</option>
							<option value="4"<?php if ($pun_config['o_server_timezone'] == 4 ) echo ' selected="selected"' ?>>+04</option>
							<option value="4.5"<?php if ($pun_config['o_server_timezone'] == 4.5 ) echo ' selected="selected"' ?>>+04.5</option>
							<option value="5"<?php if ($pun_config['o_server_timezone'] == 5 ) echo ' selected="selected"' ?>>+05</option>
							<option value="5.5"<?php if ($pun_config['o_server_timezone'] == 5.5 ) echo ' selected="selected"' ?>>+05.5</option>
							<option value="6"<?php if ($pun_config['o_server_timezone'] == 6 ) echo ' selected="selected"' ?>>+06</option>
							<option value="6.5"<?php if ($pun_config['o_server_timezone'] == 6.5 ) echo ' selected="selected"' ?>>+06.5</option>
							<option value="7"<?php if ($pun_config['o_server_timezone'] == 7 ) echo ' selected="selected"' ?>>+07</option>
							<option value="8"<?php if ($pun_config['o_server_timezone'] == 8 ) echo ' selected="selected"' ?>>+08</option>
							<option value="9"<?php if ($pun_config['o_server_timezone'] == 9 ) echo ' selected="selected"' ?>>+09</option>
							<option value="9.5"<?php if ($pun_config['o_server_timezone'] == 9.5 ) echo ' selected="selected"' ?>>+09.5</option>
							<option value="10"<?php if ($pun_config['o_server_timezone'] == 10) echo ' selected="selected"' ?>>+10</option>
							<option value="10.5"<?php if ($pun_config['o_server_timezone'] == 10.5 ) echo ' selected="selected"' ?>>+10.5</option>
							<option value="11"<?php if ($pun_config['o_server_timezone'] == 11) echo ' selected="selected"' ?>>+11</option>
							<option value="11.5"<?php if ($pun_config['o_server_timezone'] == 11.5 ) echo ' selected="selected"' ?>>+11.5</option>
							<option value="12"<?php if ($pun_config['o_server_timezone'] == 12 ) echo ' selected="selected"' ?>>+12</option>
							<option value="13"<?php if ($pun_config['o_server_timezone'] == 13 ) echo ' selected="selected"' ?>>+13</option>
							<option value="14"<?php if ($pun_config['o_server_timezone'] == 14 ) echo ' selected="selected"' ?>>+14</option>
						</select>
						<br /></label>
<?php

		$languages = [];
		$d = dir(PUN_ROOT.'lang');
		while (($entry = $d->read()) !== false)
		{
			if ($entry != '.' && $entry != '..' && is_dir(PUN_ROOT.'lang/'.$entry) && file_exists(PUN_ROOT.'lang/'.$entry.'/common.php'))
				$languages[] = $entry;
		}
		$d->close();

		// Only display the language selection box if there's more than one language available
		if (count($languages) > 1)
		{

?>
							<label><?php echo $lang_prof_reg['Language'] ?>: <?php echo $lang_prof_reg['Language info'] ?>
							<br /><select name="language">
<?php

			foreach ($languages as $temp)
			{
				if ($pun_config['o_default_lang'] == $temp)
					echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
			}

?>
							</select>
							<br /></label>
<?php

		}
?>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_prof_reg['Privacy options legend'] ?></legend>
					<div class="infldset">
						<p><?php echo $lang_prof_reg['E-mail setting info'] ?></p>
						<div class="rbox">
							<label><input type="radio" name="email_setting" value="0" /><?php echo $lang_prof_reg['E-mail setting 1'] ?><br /></label>
							<label><input type="radio" name="email_setting" value="1" checked="checked" /><?php echo $lang_prof_reg['E-mail setting 2'] ?><br /></label>
							<label><input type="radio" name="email_setting" value="2" /><?php echo $lang_prof_reg['E-mail setting 3'] ?><br /></label>
						</div>
						<p><?php echo $lang_prof_reg['Save user/pass info'] ?></p>
						<div class="rbox">
							<label><input type="checkbox" name="save_pass" value="1" checked="checked" /><?php echo $lang_prof_reg['Save user/pass'] ?><br /></label>
						</div>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="register" value="<?php echo $lang_register['Register'] ?>" /></p>
		</form>
	</div>
</div>
<?php

require PUN_ROOT.'footer.php';
