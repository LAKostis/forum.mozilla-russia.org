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


if (isset($_GET['action']))
	define('PUN_QUIET_VISIT', 1);

define('PUN_ROOT', './');
define('PUN_NO_BAN', 1);
require PUN_ROOT.'include/common.php';

// Load the misc.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;


if ($action == 'rules')
{
	// Load the register.php language file
	require PUN_ROOT.'lang/'.$pun_user['language'].'/register.php';

	$page_title = pun_htmlspecialchars($lang_register['Forum rules']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	require PUN_ROOT.'header.php';

?>
<div class="block">
	<h2><span><?php echo $lang_register['Forum rules'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<p><?php echo $pun_config['o_rules_message'] ?></p>
		</div>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if ($action == 'markread')
{
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
	$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].', read_topics=NULL WHERE id='.$pun_user['id']) or error('Unable to update user last visit data', __FILE__, __LINE__, $db->error());

	redirect('index.php', $lang_misc['Mark read redirect']);
}


// MOD: MARK TOPICS AS READ - FOLLOWING ELSE-IF BLOCK NEW CODE
else if ($action == 'markforumread')
{
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($pun_user['is_guest'])
		message($lang_common['No permission']);
	if ($id < 1)
		message($lang_common['Bad request']);

	// Make sure the user can view the topic
	$result = $db->query('SELECT 1 FROM '.$db->prefix.'topics AS t LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=t.forum_id AND fp.group_id=1) WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.forum_id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$pun_user['read_topics']['f'][$id] = time();

	$db->query('UPDATE '.$db->prefix.'users SET read_topics=\''.$db->escape(serialize($pun_user['read_topics'])).'\' WHERE id='.$pun_user['id']) or error('Unable to update read-topic data', __FILE__, __LINE__, $db->error());

	redirect('viewforum.php?id='.$id, $lang_misc['Mark forum read redirect']);
}

else if (isset($_GET['email']))
{
	check_bans();

	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$recipient_id = intval($_GET['email']);
	if ($recipient_id < 2)
		message($lang_common['Bad request']);

	$result = $db->query('SELECT username, email, email_setting FROM '.$db->prefix.'users WHERE id='.$recipient_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	list($recipient, $recipient_email, $email_setting) = $db->fetch_row($result);

	if ($email_setting == 2 && $pun_user['g_id'] > PUN_MOD)
		message($lang_misc['Form e-mail disabled']);


	if (isset($_POST['form_sent']))
	{
		// Clean up message and subject from POST
		$subject = pun_trim($_POST['req_subject']);
		$message = pun_trim($_POST['req_message']);

		if ($subject == '')
			message($lang_misc['No e-mail subject']);
		else if ($message == '')
			message($lang_misc['No e-mail message']);
		else if (strlen($message) > 65535)
			message($lang_misc['Too long e-mail message']);

		// Load the "form e-mail" template
		$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$pun_user['language'].'/mail_templates/form_email.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
		$mail_message = str_replace('<sender>', $pun_user['username'], $mail_message);
		$mail_message = str_replace('<board_title>', $pun_config['o_board_title'], $mail_message);
		$mail_message = str_replace('<mail_message>', $message, $mail_message);
		$mail_message = str_replace('<board_mailer>', $pun_config['o_board_title'].' '.$lang_common['Mailer'], $mail_message);

		require_once PUN_ROOT.'include/email.php';

		$cur_username = '"'.str_replace('"', '', $pun_user['username']).'"';
		pun_mail($recipient_email, $mail_subject, $mail_message, encode($cur_username).' <'.$pun_user['email'].'>');

		redirect(htmlspecialchars($_POST['redirect_url']), $lang_misc['E-mail sent redirect']);
	}


	// Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the users profile after the e-mail is sent)
	$redirect_url = isset($_SERVER['HTTP_REFERER']) && preg_match('#^'.preg_quote($pun_config['o_base_url']).'/(.*?)\.php#i', $_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php';

	$page_title = pun_htmlspecialchars($lang_misc['Send e-mail to']).' '.pun_htmlspecialchars($recipient).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	$required_fields = ['req_subject' => $lang_misc['E-mail subject'], 'req_message' => $lang_misc['E-mail message']];
	$focus_element = ['email', 'req_subject'];
	require PUN_ROOT.'header.php';

?>
<div class="blockform">
	<h2><span><?php echo $lang_misc['Send e-mail to'] ?> <?php echo pun_htmlspecialchars($recipient) ?></span></h2>
	<div class="box">
		<form id="email" method="post" action="misc.php?email=<?php echo $recipient_id ?>" onsubmit="return process_form(this)">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Write e-mail'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="redirect_url" value="<?php echo $redirect_url ?>" />
						<label><strong><?php echo $lang_misc['E-mail subject'] ?></strong><br />
						<input class="longinput" type="text" name="req_subject" size="75" maxlength="70" tabindex="1" /><br /></label>
						<label><strong><?php echo $lang_misc['E-mail message'] ?></strong><br />
						<textarea name="req_message" rows="10" cols="75" onkeypress="if (event.keyCode==10 || (event.ctrlKey && event.keyCode==13))document.getElementById('submit').click()" tabindex="2"></textarea><br /></label>
						<p><?php echo $lang_misc['E-mail disclosure note'] ?></p>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" id="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="3" accesskey="s" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if (isset($_GET['report']))
{
	check_bans();

	if ($pun_user['is_guest'])
		message($lang_common['No permission']);

	$post_id = intval($_GET['report']);
	if ($post_id < 1)
		message($lang_common['Bad request']);

	if (isset($_POST['form_sent']))
	{
		// Clean up reason from POST
		$reason = pun_linebreaks(pun_trim($_POST['req_reason']));

		if (!empty($_POST['spam']))
			$reason = 'Spam';
		if ($reason == '')
			message($lang_misc['No reason']);

		// Get the topic ID
		$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$post_id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		$topic_id = $db->result($result);

		// Get the subject and forum ID
		$result = $db->query('SELECT subject, forum_id, announcement FROM '.$db->prefix.'topics WHERE id='.$topic_id) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
		if (!$db->num_rows($result))
			message($lang_common['Bad request']);

		list($subject, $forum_id, $announcement) = $db->fetch_row($result);

		// Should we use the internal report handling?
		if ($pun_config['o_report_method'] == 0 || $pun_config['o_report_method'] == 2)
		{
			$db->query('INSERT INTO '.$db->prefix.'reports (post_id, topic_id, forum_id, reported_by, created, message) VALUES('.$post_id.', '.$topic_id.', '.$forum_id.', '.$pun_user['id'].', '.time().', \''.$db->escape($reason).'\')' ) or error('Unable to create report', __FILE__, __LINE__, $db->error());

			$result = $db->query('SELECT DISTINCT forum_id, topic_id, post_id, reported_by FROM '.$db->prefix.'reports WHERE message = \'Spam\' AND zapped IS NULL') or error('Unable to select reports', __FILE__, __LINE__, $db->error());
			if ($db->num_rows($result))
			{
				$whitelist = !empty($pun_config['o_spamreport_whitelist']) ? explode(',', $pun_config['o_spamreport_whitelist']) : [];
				$blacklist = !empty($pun_config['o_spamreport_blacklist']) ? explode(',', $pun_config['o_spamreport_blacklist']) : [];
				$forums = !empty($pun_config['o_spamreport_forums']) ? explode(',', $pun_config['o_spamreport_forums']) : [];
				$count = (int)$pun_config['o_spamreport_count'] > 1 ? (int)$pun_config['o_spamreport_count'] : 2;

				$blocked = [];
				while ($cur_report = $db->fetch_assoc($result))
				{
					if (in_array($cur_report['forum_id'], $forums))
						continue;
					if (in_array($cur_report['reported_by'], $blacklist))
						continue;
					if (in_array($cur_report['reported_by'], $whitelist))
						$blocked[$cur_report['post_id']] = $count;
					elseif (array_key_exists($cur_report['post_id'], $blocked))
						$blocked[$cur_report['post_id']]++;
					else
						$blocked[$cur_report['post_id']] = 1;
				}

				$forums_blocked = [];
				foreach ($blocked as $forum_id => $reports)
					if ($reports >= $count)
						$forums_blocked[] = (int)$forum_id;

				if (sizeof($forums_blocked))
					$db->query('UPDATE '.$db->prefix.'posts SET blocked=1 WHERE blocked=0 AND id IN('.join(',', $forums_blocked) . ')') or error('Unable to block post', __FILE__, __LINE__, $db->error());
			}
		}

		// Should we e-mail the report?
		if ($pun_config['o_report_method'] == 1 || $pun_config['o_report_method'] == 2)
		{
			// We send it to the complete mailing-list in one swoop
			if ($pun_config['o_mailing_list'] != '')
			{
				if ($announcement == '1')
					$forum_id = 'announcement';

				$mail_subject = 'Report('.$forum_id.') - \''.$subject.'\'';
				$mail_message = 'User \''.$pun_user['username'].'\' has reported the following message:'."\n".$pun_config['o_base_url'].'/viewtopic.php?pid='.$post_id.'#p'.$post_id."\n\n".'Reason:'."\n".$reason;

				require PUN_ROOT.'include/email.php';

				pun_mail($pun_config['o_mailing_list'], $mail_subject, $mail_message);
			}

			// We send it to the complete jabber-list in one swoop
			if ($pun_config['o_jabber_list'] != '')
			{
				$jabber_message = 'User \''.$pun_user['username'].'\' has reported the following message:'."\n".$pun_config['o_base_url'].'/viewtopic.php?pid='.$post_id.'#p'.$post_id."\n\n".'Reason:'."\n".$reason;

				require PUN_ROOT.'include/jabber.php';

				pun_jabber($pun_config['o_jabber_list'], $jabber_message);
			}
		}

		redirect('viewtopic.php?pid='.$post_id.'#p'.$post_id, $lang_misc['Report redirect']);
	}


	$page_title = pun_htmlspecialchars($lang_misc['Report post']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	$focus_element = ['report', 'req_reason'];
	require PUN_ROOT.'header.php';

?>
<div class="blockform">
	<h2><span><?php echo $lang_misc['Report post'] ?></span></h2>
	<div class="box">
		<form id="report" method="post" action="misc.php?report=<?php echo $post_id ?>" onsubmit="return process_form(this)">
			<div class="inform" id="reason">
				<fieldset>
					<legend><?php echo $lang_misc['Reason desc'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<label><strong><?php echo $lang_misc['Reason'] ?></strong><br /><textarea name="req_reason" rows="5" onkeypress="if (event.keyCode==10 || (event.ctrlKey && event.keyCode==13))document.getElementById('submit').click()" cols="60"></textarea><br /></label>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Report more'] ?></legend>
					<div class="infldset">
						<div class="rbox">
							<label><input type="checkbox" name="spam" value="1" onclick="toggleSpamReport(this)" />&nbsp;<?php echo $lang_misc['Report spam'] ?></label>
						</div>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" id="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


else if (isset($_GET['subscribe']))
{
	check_bans();

	if ($pun_user['is_guest'] || $pun_config['o_subscriptions'] != '1')
		message($lang_common['No permission']);

	$topic_id = intval($_GET['subscribe']);
	if ($topic_id < 1)
		message($lang_common['Bad request']);

	$result = $db->query('SELECT 1 FROM '.$db->prefix.'subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
		message($lang_misc['Already subscribed']);

	$db->query('INSERT INTO '.$db->prefix.'subscriptions (user_id, topic_id) VALUES('.$pun_user['id'].' ,'.$topic_id.')') or error('Unable to add subscription', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$topic_id, $lang_misc['Subscribe redirect']);
}


else if (isset($_GET['unsubscribe']))
{
	if ($pun_user['is_guest'] || $pun_config['o_subscriptions'] != '1')
		message($lang_common['No permission']);

	$topic_id = intval($_GET['unsubscribe']);
	if ($topic_id < 1)
		message($lang_common['Bad request']);

	$result = $db->query('SELECT 1 FROM '.$db->prefix.'subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$topic_id) or error('Unable to fetch subscription info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_misc['Not subscribed']);

	$db->query('DELETE FROM '.$db->prefix.'subscriptions WHERE user_id='.$pun_user['id'].' AND topic_id='.$topic_id) or error('Unable to remove subscription', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?id='.$topic_id, $lang_misc['Unsubscribe redirect']);
}


else
	message($lang_common['Bad request']);
