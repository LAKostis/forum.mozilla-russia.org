<?php
/***********************************************************************

  Caleb Champlin (med_mediator@hotmail.com)

************************************************************************/

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);


// Did someone just hit "Submit"
if (isset($_POST['form_sent']))
{
	// Make sure form_user is correct
	if ($_POST['form_user'] != $pun_user['username'])
		message($lang_common['Bad request']);


	$subject = pun_trim($_POST['req_subject']);

	if ($subject == '')
		$errors[] = "You need a subject";
	else if (pun_strlen($subject) > 70)
		$errors[] = "Your subject is to long";
	else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($subject) == $subject)
		$subject = ucwords(pun_strtolower($subject));


	$username = $pun_user['username'];
	$email = $pun_user['email'];

	// Clean up message from POST
	$message = pun_linebreaks(pun_trim($_POST['req_message']));

	if ($message == '')
		$errors[] = "You need a message";
	else if (strlen($message) > 65535)
		$errors[] = "Your message is to long";
	else if ($pun_config['p_message_all_caps'] == '0' && strtoupper($message) == $message)
		$message = ucwords(pun_strtolower($message));

	// Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1' && strpos($message, '[') !== false && strpos($message, ']') !== false)
	{
		require PUN_ROOT.'include/parser.php';
		$message = preparse_bbcode($message, $errors);
	}


	require PUN_ROOT.'include/search_idx.php';

	$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;

	$now = time();

	// Did everything go according to plan?
	if (empty($errors))
	{
		// Create the topic
		$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, announcement, forum_id) VALUES(\''.$db->escape($username).'\', \''.$db->escape($subject).'\', '.$now.', '.$now.', \''.$db->escape($username).'\', \'1\', \'0\')') or error('Unable to create topic', __FILE__, __LINE__, $db->error());
		$new_tid = $db->insert_id();

		// Create the post ("topic post")
		$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($username).'\', '.$pun_user['id'].', \''.get_remote_address().'\', \''.$db->escape($message).'\', \''.$hide_smilies.'\', '.$now.', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());

		$new_pid = $db->insert_id();

		// Update the topic with last_post_id
		$db->query('UPDATE '.$db->prefix.'topics SET last_post_id='.$new_pid.' WHERE id='.$new_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

		update_search_index('post', $new_pid, $message, $subject);

	}

	$low_prio = $db_type == 'mysql' ? 'LOW_PRIORITY ' : '';
	$db->query('UPDATE '.$low_prio.$db->prefix.'users SET num_posts=num_posts+1, last_post='.$now.' WHERE id='.$pun_user['id']) or error('Unable to update user', __FILE__, __LINE__, $db->error());

	redirect('viewtopic.php?pid='.$new_pid.'#p'.$new_pid, "Announcement Created");
}
else	// If not, we show the "Show text" form
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div id="announcementplugin" class="blockform">
		<h2><span>Announcement Plugin</span></h2>
		<div class="box">
			<div class="inbox">
				<p>This plugin gives administrators the ability to create 'global' Announcements.</p>
			</div>
		</div>
<?php
$form = '<form id="post" method="post" action="'.$_SERVER['REQUEST_URI'].'">';
$cur_index = 1;

?>
	<h2 class="block2"><span>New Announcement</span></h2>
	<div class="box">
		<?php echo $form."\n" ?>
			<div class="inform">
				<fieldset>
					<legend>Message</legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo pun_htmlspecialchars($pun_user['username']); ?>" />
						<label><strong>Subject</strong><br /><input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
						<label><strong>Message</strong><br />
						<textarea name="req_message" rows="20" cols="95" tabindex="<?php echo $cur_index++ ?>"></textarea><br /></label>
						<div class="bbincrement"><a href="#" onclick="incrementForm();return false;" style="text-decoration:none">[ + ]</a> <a href="#" onclick="decrementForm();return false;" style="text-decoration:none">[ − ]</a></div>
						<ul class="bblinks">
							<li><a href="help.php#bbcode" onclick="window.open(this.href); return false;">BBCode</a>: <?php echo $pun_config['p_message_bbcode'] == '1' ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#img" onclick="window.open(this.href); return false;">Img tag</a>: <?php echo $pun_config['p_message_img_tag'] == '1' ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#smilies" onclick="window.open(this.href); return false;">Smilies</a>: <?php echo $pun_config['o_smilies'] == '1' ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
					</div>
				</fieldset>
<?php

$checkboxes = [];
	if ($pun_config['o_smilies'] == '1')
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="'.($cur_index++).'"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' />Hide Smilies';

if (!empty($checkboxes))
{

?>
			</div>
			<div class="inform">
				<fieldset>
					<legend>Options</legend>
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
			<p><input type="submit" name="submit" value="Submit" tabindex="<?php echo $cur_index++ ?>" accesskey="s" /><a href="javascript:history.go(-1)">Go back</a></p>
		</form>
	</div>
</div>
<?php

}

// Note that the script just ends here. The footer will be included by admin_loader.php.
