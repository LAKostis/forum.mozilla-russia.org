<?php
/***********************************************************************

  Copyright (C) 2002, 2003, 2004  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2006  LAKostis (lakostis@mozilla-russian.org)

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
// MOD AJAX post preview
require PUN_ROOT.'post.common.php';
require PUN_ROOT.'include/common.php';

if(!$pun_config['o_pms_enabled'] || $pun_user['is_guest'])
	message($lang_common['No permission']);

// Load the post.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

if (isset($_POST['form_sent']))
{
	// Flood protection
	if($pun_user['g_id'] > PUN_GUEST){
		$result = $db->query('SELECT posted FROM '.$db->prefix.'messages ORDER BY id DESC LIMIT 1') or error('Unable to fetch message time for flood protection', __FILE__, __LINE__, $db->error());
		if(list($last) = $db->fetch_row($result)){
			if((time() - $last) < $pun_user['g_post_flood'])
				message($lang_pms['Flood start'].' '.$pun_user['g_post_flood'].' '.$lang_pms['Flood end']);
		}
	}
	// Smileys
	if (isset($_POST['hide_smilies']))
		$smilies = 0;
	else
		$smilies = 1;

	// Check subject
	$subject = pun_trim($_POST['req_subject']);
	if ($subject == '')
		message($lang_post['No subject']);
	else if (pun_strlen($subject) > 70)
		message($lang_post['Too long subject']);
	else if ($pun_config['p_subject_all_caps'] == '0' && pun_strtoupper($subject) == $subject && $pun_user['g_id'] > PUN_GUEST)
		$subject = pun_ucwords(pun_strtolower($subject));

	// Clean up message from POST
	$message = pun_linebreaks(pun_trim($_POST['req_message']));

	// Check message
	if ($message == '')
		message($lang_post['No message']);
	else if (strlen($message) > 65535)
		message($lang_post['Too long message']);
	else if ($pun_config['p_message_all_caps'] == '0' && pun_strtoupper($message) == $message && $pun_user['g_id'] > PUN_GUEST)
		$message = pun_ucwords(pun_strtolower($message));

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1' && strpos($message, '[') !== false && strpos($message, ']') !== false)
	{
		require PUN_ROOT.'include/parser.php';
		$message = preparse_bbcode($message, $errors);
	}
	if (isset($errors))
		message($errors[0]);

	// Get userid
	$result = $db->query('SELECT id, username, group_id, pm_email_notify FROM '.$db->prefix.'users WHERE id!=1 AND username=\''.addslashes($_POST['req_username']).'\'') or error('Unable to get user id', __FILE__, __LINE__, $db->error());

	// Send message
	if(list($id,$user,$status,$user_notify) = $db->fetch_row($result)){

		// Check inbox status
		if($pun_config['o_pms_messages'] != 0 && $pun_user['g_id'] > PUN_GUEST && $status > PUN_GUEST)
		{
			$result = $db->query('SELECT count(*) FROM '.$db->prefix.'messages WHERE owner='.$id) or error('Unable to get message count for the receiver', __FILE__, __LINE__, $db->error());
			list($count) = $db->fetch_row($result);
			if($count >= $pun_config['o_pms_messages'])
				message($lang_pms['Inbox full']);
				
			// Also check users own box
			if(isset($_POST['savemessage']) && intval($_POST['savemessage']) == 1)
			{
				$result = $db->query('SELECT count(*) FROM '.$db->prefix.'messages WHERE owner='.$pun_user['id']) or error('Unable to get message count the sender', __FILE__, __LINE__, $db->error());
				list($count) = $db->fetch_row($result);
				if($count >= $pun_config['o_pms_messages'])
					message($lang_pms['Sent full']);
			}
		}
		
		// "Send" message
		$db->query('INSERT INTO '.$db->prefix.'messages (owner, subject, message, sender, sender_id, sender_ip, smileys, posted) VALUES(
			\''.$id.'\',
			\''.addslashes($subject).'\',
			\''.addslashes($message).'\',
			\''.addslashes($pun_user['username']).'\',
			\''.$pun_user['id'].'\',
			\''.get_remote_address().'\',
			\''.$smilies.'\',
			\''.time().'\'
		)') or error('Unable to send message', __FILE__, __LINE__, $db->error());

		// Save an own copy of the message
		if(isset($_POST['savemessage'])){
			$db->query('INSERT INTO '.$db->prefix.'messages (owner, subject, message, sender, sender_id, sender_ip, smileys, showed, status, posted) VALUES(
				\''.$pun_user['id'].'\',
				\''.addslashes($subject).'\',
				\''.addslashes($message).'\',
				\''.addslashes($user).'\',
				\''.$id.'\',
				\''.get_remote_address().'\',
				\''.$smilies.'\',
				\'1\',
				\'1\',
				\''.time().'\'
			)') or error('Unable to send message', __FILE__, __LINE__, $db->error());
		}

		// Should we send out notifications?
		if ($pun_config['o_subscriptions'] == '1' && $user_notify == '1')
		{
			// Get userid
			$result = $db->query('SELECT email, language FROM '.$db->prefix.'users WHERE id!=1 AND id=\''.$id.'\'') or error('Unable to get user email', __FILE__, __LINE__, $db->error());

			require_once PUN_ROOT.'include/email.php';
		
			$notification_emails = array();

			// Loop through subscribed users and send e-mails
			while ($cur_subscriber = $db->fetch_assoc($result)) {
				// Is the subscription e-mail for $cur_subscriber['language'] cached or not?
				if (!isset($notification_emails[$cur_subscriber['language']]))
				{
			
					if (file_exists(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_pms.tpl'))
					{
						// Load the "new reply" template
						$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_pms.tpl'));

						// The first row contains the subject (it also starts with "Subject:")
						$first_crlf = strpos($mail_tpl, "\n");
						$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
						$mail_message = trim(substr($mail_tpl, $first_crlf));

						$mail_subject = str_replace('<board_title>', $pun_config['o_board_title'], $mail_subject);
						$mail_message = str_replace('<sender>', $pun_user['username'], $mail_message);
						$mail_message = str_replace('<subject>', '\''. $subject.'\'', $mail_message);
						$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'].' '.$lang_common['Mailer'], $mail_message);

						$notification_emails[$cur_subscriber['language']][0] = $mail_subject;
						$notification_emails[$cur_subscriber['language']][1] = $mail_message;

						$mail_subject = $mail_message = null;
					}
				}

				// We have to double check here because the templates could be missing
				if (isset($notification_emails[$cur_subscriber['language']]))
				{
					pun_mail($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
				}
			}
	    	}
	}
	else{
		message($lang_pms['No user']);
	}
	
	$topic_redirect = intval($_POST['topic_redirect']);
	$from_profile = isset($_POST['from_profile']) ? intval($_POST['from_profile']) : '';
	if($from_profile != 0)
		redirect('profile.php?id='.$from_profile, $lang_pms['Sent redirect']);
	else if($topic_redirect != 0)
		redirect('viewtopic.php?id='.$topic_redirect, $lang_pms['Sent redirect']);
	else
		redirect('message_list.php', $lang_pms['Sent redirect']);
}
else
{
if (isset($_GET['id']))
	$id = intval($_GET['id']);
else
	$id = 0;

	if($id > 0){
		$result = $db->query('SELECT username FROM '.$db->prefix.'users WHERE id=\''.$id.'\'') or error('Unable to fetch message info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);
		list($username) = $db->fetch_row($result);
	}

	if(isset($_GET['reply']) || isset($_GET['quote'])){
		$r = isset($_GET['reply']) ? intval($_GET['reply']) : 0;
		$q = isset($_GET['quote']) ? intval($_GET['quote']) : 0;

		// Get message info
		empty($r) ? $id = $q : $id = $r;
		$result = $db->query('SELECT * FROM '.$db->prefix.'messages WHERE id=\''.$id.'\' AND owner='.$pun_user['id']) or error('Unable to fetch message info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);
		$message = $db->fetch_assoc($result);

		// Quote the message
		if(isset($_GET['quote']))
			$quote = '[quote='.$message['sender'].']'.$message['message'].'[/quote]';

		// Add subject
		$subject = "RE: " . $message['subject'];
	}

	$action = $lang_pms['Send a message'];
	$form = '<form method="post" id="post" action="message_send.php?action=send" onsubmit="return process_form(this)">';

	$page_title = pun_htmlspecialchars($action).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	$form_name = 'post';

	$cur_index = 1;
	if (!isset($username))
		$username = '';
	if (!isset($quote))
		$quote = '';
	if (!isset($subject))
		$subject = '';
	require PUN_ROOT.'header.php';
?>
<!-- MOD AJAX post preview -->
<div id="ajaxpostpreview"></div>
<!--// MOD AJAX post preview -->
<div class="blockform">
	<h2><span><?php echo $action ?></span></h2>
	<div class="box">
	<?php echo $form."\n" ?>
		<div class="inform">
		<fieldset>
			<legend><?php echo $lang_common['Write message legend'] ?></legend>
			<div class="infldset txtarea">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="topic_redirect" value="<?php echo isset($_GET['tid']) ? intval($_GET['tid']) : '' ?>" />
				<input type="hidden" name="topic_redirect" value="<?php echo isset($_POST['from_profile']) ? $_POST['from_profile'] : '' ?>" />
				<input type="hidden" name="form_user" value="<?php echo (!$pun_user['is_guest']) ? pun_htmlspecialchars($pun_user['username']) : 'Guest'; ?>" />
				<label class="conl"><strong><?php echo $lang_pms['Send to'] ?></strong><br /><?php echo '<input type="text" name="req_username" size="25" maxlength="25" value="'.pun_htmlspecialchars($username).'" tabindex="'.($cur_index++).'" />'; ?><br /></label>
				<div class="clearer"></div>
				<label><strong><?php echo $lang_common['Subject'] ?></strong><br /><input class="longinput" type='text' name='req_subject' value='<?php echo $subject ?>' size="80" maxlength="70" tabindex='<?php echo $cur_index++ ?>' /><br /></label>
				<?php require PUN_ROOT.'mod_easy_bbcode.php'; ?>
				<label><strong><?php echo $lang_common['Message'] ?></strong><br />
				<textarea name="req_message" rows="20" cols="95" onkeyup="setCaret(this);" onclick="setCaret(this);" onselect="setCaret(this);" tabindex="<?php echo $cur_index++ ?>"><?php echo $quote ?></textarea><br /></label>
				<ul class="bblinks">
					<li><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
					<li><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
					<li><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
				</ul>
			</div>
		</fieldset>
<?php
	$checkboxes = array();

	if ($pun_config['o_smilies'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'];

	$checkboxes[] = '<label><input type="checkbox" name="savemessage" value="1" tabindex="'.($cur_index++).'" checked="checked" />'.$lang_pms['Save message'];

	if (!empty($checkboxes))
	{
?>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Options'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<?php echo implode('<br /></label>'."\n\t\t\t\t", $checkboxes).'<br /></label>'."\n" ?>
						</div>
					</div>
				</fieldset>
<?php
	}
?>
			</div>
			<!-- MOD AJAX post preview -->
			<p><input type="submit" name="submit" value="<?php echo $lang_pms['Send'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /><input type="submit" onclick="xajax_getpreview(xajax.getFormValues('post')); document.location.href='#ajaxpostpreview'; return false;" name="preview" value="<?php echo $lang_post['Preview'] ?>" tabindex="<?php echo  $cur_index++ ?>" accesskey="p" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
			<!--// MOD AJAX post preview -->
		</form>
	</div>
</div>
<?php
	require PUN_ROOT.'footer.php';
}
