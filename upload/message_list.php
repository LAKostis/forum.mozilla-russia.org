<?php
/***********************************************************************

  Copyright (C) 2002, 2003, 2004  Rickard Andersson (rickard@punbb.org)
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


define('PUN_ROOT', './');
define('PUN_NO_BAN', 1);
require PUN_ROOT.'post.common.php';
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/parser.php';

if(!$pun_config['o_pms_enabled'])
	message($lang_common['No permission']);

if ($pun_user['is_guest'])
	message($lang_common['Login required']);

// Load the message.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/pms.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/userlist.php';

// Inbox or Sent?
if(isset($_GET['box']))
	$box = (int)($_GET['box']);
else
	$box = 0;

$box != 1 ? $box = 0 : $box = 1;
$box != 1 ? $status = 0 : null;
$box == 0 ? $name = $lang_pms['Inbox'] : $name = $lang_pms['Outbox'];
//$name plus the link to the other box
$page_name = $name.' (<a href="?box='.(int)(!$box).'">'.$lang_pms['Box'.(int)$box].'</a>)';

// Delete multiple posts
if( isset($_POST['delete_messages']) || isset($_POST['delete_messages_comply']) )
{
	if( isset($_POST['delete_messages_comply']) )
	{
		//Check this is legit
		confirm_referrer('message_list.php');

		// Delete all messages
		if( isset($_POST['deleteall']) )
			$db->query('DELETE FROM '.$db->prefix.'messages WHERE owner=\''.$pun_user['id'].'\'') or error('Unable to delete messages.', __FILE__, __LINE__, $db->error());
		else
		// Delete messages
		$db->query('DELETE FROM '.$db->prefix.'messages WHERE id IN('.$_POST['messages'].') AND owner=\''.$pun_user['id'].'\'') or error('Unable to delete messages.', __FILE__, __LINE__, $db->error());

		redirect('message_list.php?box='.$_POST['box'], $lang_pms['Deleted redirect']);
	}
	else
	{
		$page_title = pun_htmlspecialchars($lang_pms['Multidelete']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
		$idlist = $_POST['delete_messages'];
		require PUN_ROOT.'header.php';
?>
<div class="blockform">
	<h2><span><?php echo $lang_pms['Multidelete'] ?></span></h2>
	<div class="box">
		<form method="post" action="message_list.php">
			<p><input type="submit" name="delete_messages_comply" value="<?php echo $lang_pms['Delete'] ?>" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p><br/>
			<input type="hidden" name="messages" value="<?php echo implode(',', array_values($idlist)) ?>">
			<input type="hidden" name="box" value="<?php echo $_POST['box']; ?>">
			<div class="inform">
				<fieldset>
					<div class="infldset">
						<p class="warntext"><strong><?php echo $lang_pms['Delete messages comply'] ?></strong></p>
					</div>
				</fieldset>
			</div>
		</form>
	</div>
</div>
<?php
		require PUN_ROOT.'footer.php';
	}
}
else
{
	// Delete all messages
	if (isset($_GET['action']) && $_GET['action'] == 'deleteall')
	{
			$page_title = pun_htmlspecialchars($lang_pms['Delete all']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
			require PUN_ROOT.'header.php';
	?>
	<div class="blockform">
		<h2><span><?php echo $lang_pms['Delete all'] ?></span></h2>
		<div class="box">
			<form method="post" action="message_list.php">
				<p><input type="submit" name="delete_messages_comply" value="<?php echo $lang_pms['Delete'] ?>" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p><br/>
				<input type="hidden" name="deleteall" value="1">
				<input type="hidden" name="box" value="<?php echo $_POST['box']; ?>">
				<div class="inform">
					<fieldset>
						<div class="infldset">
							<p class="warntext"><strong><?php echo $lang_pms['Delete messages comply'] ?></strong></p>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<?php
			require PUN_ROOT.'footer.php';
	}

	// Mark all messages as read
	if (isset($_GET['action']) && $_GET['action'] == 'markall')
	{
		$db->query('UPDATE '.$db->prefix.'messages SET showed=1 WHERE owner='.$pun_user['id']) or error('Unable to update message status', __FILE__, __LINE__, $db->error());
		$p = (!isset($_GET['p']) || intval($_GET['p']) <= 1) ? 1 : intval($_GET['p']);
		redirect('message_list.php?box='.$box.'&p='.$p, $lang_pms['Read redirect']);
	}
}

$page_title = pun_htmlspecialchars($lang_pms['Private Messages']).' | '.pun_htmlspecialchars($name).' | '.pun_htmlspecialchars($pun_config['o_board_title']);

// Get message count
$result = $db->query('SELECT count(*) FROM '.$db->prefix.'messages WHERE status='.$box.' AND owner='.$pun_user['id']) or error('Unable to count messages', __FILE__, __LINE__, $db->error());
list($num_messages) = $db->fetch_row($result);

//What page are we on?
$num_pages = ceil($num_messages / $pun_config['o_pms_mess_per_page']);
$p = (!isset($_GET['p']) || intval($_GET['p']) <= 1 || intval($_GET['p']) > $num_pages) ? 1 : intval($_GET['p']);
$start_from = $pun_config['o_pms_mess_per_page'] * ($p - 1);
$limit = $start_from.','.$pun_config['o_pms_mess_per_page'];

$quickpost = $pun_config['o_quickpost'] == '1';

require PUN_ROOT.'header.php';
?>

<div class="linkst">
	<div class="inbox">
		<p class="pagelink conl"><a href="message_send.php" class="pm"><?php echo $lang_pms['New message']; ?></a></p>
		<p class="postlink conr"><?php echo $lang_common['Pages'].': '.paginate($num_pages, $p, 'message_list.php?box='.$box) ?></p>
		<ul><li><a href="index.php"><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?></a>&nbsp;</li><li>&raquo;&nbsp;<a href="message_list.php"><?php echo $lang_pms['Private Messages'].'</a>&nbsp;</li><li>&raquo;&nbsp;'.$page_name ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<form id="actions" name="actions" method="post" action="message_list.php">
<div id="vf" class="blocktable">
	<h2><span><?php echo $name ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
<?php
		if($pun_config['o_pms_messages'] != 0 && $pun_user['g_id'] > PUN_GUEST){
			// Get total message count
			$result = $db->query('SELECT count(*) FROM '.$db->prefix.'messages WHERE owner='.$pun_user['id']) or error('Unable to count messages', __FILE__, __LINE__, $db->error());
			list($tot_messages) = $db->fetch_row($result);
			$proc = ceil($tot_messages / $pun_config['o_pms_messages'] * 100);
			$status = ' - '.$lang_pms['Status'].' '.$proc.'%';
		}
		else
			$status = '';
?>
					<?php if(isset($_GET['action']) && $_GET['action'] == 'multidelete') { ?>
					<th class="tcmod"><input type="checkbox" onclick="toggleChildren(checked)"></th>
					<?php } ?>
					<th class="tcl"><?php echo $lang_pms['Subject'] ?><?php echo $status ?></th>
					<th><?php if($box == 0) echo $lang_pms['Sender']; else echo $lang_pms['Receiver']; ?></th>
					<th class="tcr"><?php echo $lang_pms['Date'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

// Fetch messages
$result = $db->query('SELECT * FROM '.$db->prefix.'messages WHERE owner='.$pun_user['id'].' AND status='.$box.' ORDER BY posted DESC LIMIT '.$limit) or error('Unable to fetch messages list for forum', __FILE__, __LINE__, $db->error());
$new_messages = false;
$messages_exist = false;

$post_link = $subject_reply = $user_reply = null;

// If there are messages in this folder.
if ($db->num_rows($result))
{
	$messages_exist = true;
	while ($cur_mess = $db->fetch_assoc($result))
	{
		$icon_text = $lang_common['Normal icon'];
		$icon_type = '';
		if ($cur_mess['showed'] == '0')
		{
			$icon_text .= ' '.$lang_common['New icon'];
			$icon_type = 'inew';
		}

		($new_messages == false && $cur_mess['showed'] == '0') ? $new_messages = true : null;

		$subject_link = 'message_list.php?id='.$cur_mess['id'].'&amp;p='.$p.'&amp;box='.(int)$box;
		$subject = '<a href="'.$subject_link.'">'.pun_htmlspecialchars($cur_mess['subject']).'</a>';
		if (!$post_link && isset($_GET['id']))
			if($cur_mess['id'] == $_GET['id'])
			{
				$subject = "<strong>$subject</strong>";
				$post_link = $subject_link;
				$subject_reply = pun_increment_pm(pun_htmlspecialchars($cur_mess['subject']));
				$user_reply = pun_htmlspecialchars($cur_mess['sender']);
			}

?>
	<tr class="<?php echo $icon_type ?>">
<?php if(isset($_GET['action']) && $_GET['action'] == 'multidelete') { ?>
		<td class="tcmod"><input type="checkbox" name="delete_messages[]" value="<?php echo $cur_mess['id']; ?>"></td>
<?php } ?>
		<td class="tcl">
			<div class="intd">
				<div class="icon <?php echo $icon_type ?>"><div class="nosize"><?php echo trim($icon_text) ?></div></div>
				<div class="tclcon">
					<?php echo $subject."\n" ?>
				</div>
			</div>
		</td>
		<td class="tc2" style="white-space: nowrap; OVERFLOW: hidden"><a href="profile.php?id=<?php echo $cur_mess['sender_id'] ?>"><?php echo $cur_mess['sender'] ?></a></td>
		<td class="tcr" style="white-space: nowrap"><?php echo format_time($cur_mess['posted']) ?></td>
	</tr>
<?php

	}
}
else
{
	$cols = isset($_GET['action']) ? '4' : '3';
	echo "\t".'<tr><td class="puncon1" colspan="'.$cols.'">'.$lang_pms['No messages'].'</td></tr>'."\n";
}
?>
			</tbody>
			</table>
		</div>
	</div>
</div>
<?php
//Are we viewing a PM?
if(isset($_GET['id'])){
	//Yes! Lets get the details
	$id = intval($_GET['id']);

	// Set user
	$result = $db->query('SELECT status,owner FROM '.$db->prefix.'messages WHERE id='.$id) or error('Unable to get message status', __FILE__, __LINE__, $db->error());
	list($status, $owner) = $db->fetch_row($result);
	$status == 0 ? $where = 'u.id=m.sender_id' : $where = 'u.id=m.owner';

	$result = $db->query('SELECT m.id AS mid,m.subject,m.sender_ip,m.message,m.smileys,m.posted,m.showed,u.id,u.group_id as g_id,g.g_user_title,g.g_title,u.username,u.registered,u.email,u.title,u.url,u.icq,u.msn,u.aim,u.yahoo,u.location,u.use_avatar,u.email_setting,u.num_posts,u.admin_note,u.signature,u.show_online,o.user_id AS is_online FROM '.$db->prefix.'messages AS m,'.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.idle=0) LEFT JOIN '.$db->prefix.'groups AS g ON u.group_id = g.g_id WHERE '.$where.' AND m.id='.$id) or error('Unable to fetch message and user info', __FILE__, __LINE__, $db->error());
	$cur_post = $db->fetch_assoc($result);

	if ($owner != $pun_user['id'])
		message($lang_common['No permission']);

	if ($cur_post['showed'] == 0)
		$db->query('UPDATE '.$db->prefix.'messages SET showed=1 WHERE id='.$id) or error('Unable to update message info', __FILE__, __LINE__, $db->error());

	if ($cur_post['id'] > 0)
	{
		if (!$quickpost)
			$username = '<a href="profile.php?id='.$cur_post['id'].'">'.pun_htmlspecialchars($cur_post['username']).'</a>';
		else
			$username = '<a href="profile.php?id='.$cur_post['id'].'" onclick="pasteN(this);return false;">'.pun_htmlspecialchars($cur_post['username']).'</a>';

		$user_title = get_title($cur_post);

		$user_banned = $user_title == $lang_common['Banned'];

		$group_title = $cur_post['g_title'];

		if ($pun_config['o_censoring'] == '1')
			$user_title = censor_words($user_title);

		// Format the online indicator
		$is_online = ($cur_post['is_online'] == $cur_post['id'] && $cur_post['show_online'] == '1' || $cur_post['is_online'] == $cur_post['id'] && $cur_post['show_online'] == 0 && $pun_user['group_id'] < PUN_MOD) ? '<strong class="online">'.$lang_topic['Online'].'</strong>' : '<span class="offline">' . $lang_topic['Offline'] . '</span>';

		if ($pun_config['o_avatars'] == '1' && !$user_banned && $cur_post['use_avatar'] == '1' && $pun_user['show_avatars'] != '0')
		{
			if ($img_size = @getimagesize($pun_config['o_avatars_dir'].'/'.$cur_post['id'].'.gif'))
				$user_avatar = '<img src="'.$pun_config['o_avatars_dir'].'/'.$cur_post['id'].'.gif" '.$img_size[3].' alt="" />';
			else if ($img_size = @getimagesize($pun_config['o_avatars_dir'].'/'.$cur_post['id'].'.jpg'))
				$user_avatar = '<img src="'.$pun_config['o_avatars_dir'].'/'.$cur_post['id'].'.jpg" '.$img_size[3].' alt="" />';
			else if ($img_size = @getimagesize($pun_config['o_avatars_dir'].'/'.$cur_post['id'].'.png'))
				$user_avatar = '<img src="'.$pun_config['o_avatars_dir'].'/'.$cur_post['id'].'.png" '.$img_size[3].' alt="" />';
		}
		else
			$user_avatar = '';

			// We only show location, register date, post count and the contact links if "Show user info" is enabled
		if ($pun_config['o_show_user_info'] == '1')
		{
			$user_info[] = '<dd>'.$lang_ul['User group'].': <strong>'.$group_title.'</strong>';
			if ($cur_post['location'] != '')
			{
				if ($pun_config['o_censoring'] == '1')
					$cur_post['location'] = censor_words($cur_post['location']);

				$user_info[] = '<dd>'.$lang_topic['From'].': '.pun_htmlspecialchars($cur_post['location']);
			}


			$user_info[] = '<dd>'.$lang_common['Registered'].': '.date($pun_config['o_date_format'], $cur_post['registered']);

			if ($pun_config['o_show_post_count'] == '1' || $pun_user['g_id'] < PUN_GUEST)
				$user_info[] = '<dd>'.$lang_common['Posts'].': <a href="search.php?action=show_user&amp;user_id='.$cur_post['id'].'">'.$cur_post['num_posts'].'</a>';

			// Now let's deal with the contact links (E-mail and URL)
			if (($cur_post['email_setting'] == '0' && !$pun_user['is_guest']) || $pun_user['g_id'] < PUN_GUEST)
				$user_contacts[] = '<a href="mailto:'.$cur_post['email'].'" class="email">'.$lang_common['E-mail'].'</a>';
			else if ($cur_post['email_setting'] == '1' && !$pun_user['is_guest'])
				$user_contacts[] = '<a href="misc.php?email='.$cur_post['id'].'" class="email">'.$lang_common['E-mail'].'</a>';
			require(PUN_ROOT.'include/pms/viewtopic_PM-link.php');
			if ($cur_post['url'] != '')
				$user_contacts[] = '<a href="'.pun_htmlspecialchars($cur_post['url']).'" class="website">'.$lang_topic['Website'].'</a>';
		}

		//Moderator and Admin stuff
		if ($pun_user['g_id'] < PUN_GUEST)
		{
			$user_info[] = '<dd>IP: <a href="moderate.php?get_host='.$cur_post['id'].'">'.$cur_post['sender_ip'].'</a>';

			if ($cur_post['admin_note'] != '')
				$user_info[] = '<dd><strong>'.pun_htmlspecialchars($cur_post['admin_note']).'</strong>';
		}
		// Generation post action array (reply, delete etc.)
		$post_actions[] = '<li><a href="message_delete.php?id='.$cur_post['mid'].'&amp;box='.(int)$_GET['box'].'&amp;p='.(int)$_GET['p'].'" class="delete">'.$lang_pms['Delete'].'</a>';

		if(!$status)
			$post_actions[] = '<li><a href="message_send.php?id='.$cur_post['id'].'&amp;quote='.$cur_post['mid'].'" class="reply">'.$lang_pms['Reply'].'</a>';

		if(!$status)
			$post_actions[] = '<li onmouseover="copyQ(this);"><a href="'.$post_link.'" onclick=\'pasteQ("' . pun_htmlspecialchars($cur_post['username']) . '");return false;\' class="quote">'.$lang_pms['Quote'].'</a>';

	}
	// If the sender has been deleted
	else
	{
		$result = $db->query('SELECT id,sender,message,posted FROM '.$db->prefix.'messages WHERE id='.$id) or error('Unable to fetch message and user info', __FILE__, __LINE__, $db->error());
		$cur_post = $db->fetch_assoc($result);

		$username = pun_htmlspecialchars($cur_post['sender']);
		$user_title = "Deleted User";

		$post_actions[] = '<li><a href="message_delete.php?id='.$cur_post['id'].'&amp;box='.(int)$_GET['box'].'&amp;p='.(int)$_GET['p'].'" class="delete">'.$lang_pms['Delete'].'</a>';

		$is_online = $lang_topic['Offline'];
	}

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['smileys'] = isset($cur_post['smileys']) ? $cur_post['smileys'] : $pun_user['show_smilies'];
	$cur_post['message'] = parse_message($cur_post['message'], (int)(!$cur_post['smileys']));

	// Do signature parsing/caching
	if (!$user_banned && isset($cur_post['signature']) && $pun_user['show_sig'] != '0')
	{
		$signature = parse_signature($cur_post['signature']);
	}

?>


<div id="p<?php echo $cur_post['id'] ?>" class="blockpost row_odd firstpost">
	<h2><span><a href="<?php echo $post_link ?>"><?php echo format_time($cur_post['posted']) ?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postleft">
				<dl>
					<dt><strong><?php echo $username ?></strong></dt>
					<dd class="usertitle"><strong><?php echo $user_title ?></strong></dd>
					<dd class="postavatar"><?php if (isset($user_avatar)) echo $user_avatar ?></dd>
<?php if (isset($user_info)) if (count($user_info)) echo "\t\t\t\t\t".implode('</dd>'."\n\t\t\t\t\t", $user_info).'</dd>'."\n"; ?>
<?php if (isset($user_contacts)) if (count($user_contacts)) echo "\t\t\t\t\t".'<dd class="usercontacts">'.implode('&nbsp;&nbsp;', $user_contacts).'</dd>'."\n"; ?>
				</dl>
			</div>
			<div class="postright">
				<div class="postmsg">
					<?php echo $cur_post['message']."\n" ?>
				</div>
<?php if (isset($signature)) echo "\t\t\t\t".'<div class="postsignature"><hr />'.$signature.'</div>'."\n"; ?>
			</div>
			<div class="clearer"></div>
			<div class="postfootleft"><?php if ($cur_post['id'] > 1) echo '<p>'.$is_online.'</p>'; ?></div>
			<div class="postfootright"><?php echo (count($post_actions)) ? '<ul>'.implode($lang_topic['Link separator'].'</li>', $post_actions).'</li></ul></div>'."\n" : '<div>&nbsp;</div></div>'."\n" ?>
		</div>
	</div>
</div>
<div class="clearer"></div>
<?php
}

?>

<div class="postlinksb">
	<div class="inbox">
<?php
if(isset($_GET['action']) && $_GET['action'] == 'multidelete')
{
?>
		<p class="pagelink conl"><input type="hidden" name="box" value="<?php echo $box	; ?>"><input type="submit" value="<?php echo $lang_pms['Delete'] ?>"></p>
<?php
}
else
{
?>
		<p class="pagelink conl"><a href="message_send.php" class="pm"><?php echo $lang_pms['New message']; ?></a></p>
<?php
}
?>
		<p class="postlink conr"><?php echo $lang_common['Pages'].': '.paginate($num_pages, $p, 'message_list.php?box='.$box) ?></p>
		<ul><li><a href="index.php"><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?></a>&nbsp;</li><li>&raquo;&nbsp;<a href="message_list.php"><?php echo $lang_pms['Private Messages'].'</a>&nbsp;</li><li>&raquo;&nbsp;'.$page_name; ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>
</form>







<?php

	// Display quick post if enabled
	if (isset($_GET['id']) && $quickpost)
	{

?>
<!-- MOD AJAX post preview -->
<div id="ajaxpostpreview"></div>
<!--// MOD AJAX post preview -->
<div class="blockform">
	<h2><span><?php echo $lang_pms['Send a message'] ?></span></h2>
	<div class="box">
	<form id="post" name="post" method="post" action="message_send.php?action=send" onsubmit="return process_form(this)">
		<div class="inform">
		<fieldset>
			<legend><?php echo $lang_common['Write message legend'] ?></legend>
			<div class="infldset txtarea">
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="topic_redirect" value="<?php echo isset($_GET['tid']) ? intval($_GET['tid']) : '' ?>" />
				<input type="hidden" name="topic_redirect" value="<?php echo isset($_POST['from_profile']) ? $_POST['from_profile'] : '' ?>" />
				<input type="hidden" name="form_user" value="<?php echo (!$pun_user['is_guest']) ? pun_htmlspecialchars($pun_user['username']) : 'Guest'; ?>" />
				<label class="conl"><strong><?php echo $lang_pms['Send to'] ?></strong><br /><input type="text" name="req_username" size="25" maxlength="25" value="<?php echo $user_reply ?>" tabindex="2" /><br /></label>
				<div class="clearer"></div>
				<label><strong><?php echo $lang_common['Subject'] ?></strong><br /><input class="longinput" type="text" name="req_subject" value="<?php echo $subject_reply ?>" size="80" maxlength="70" tabindex="3" /><br /></label>
				<?php require PUN_ROOT.'mod_easy_bbcode.php'; ?>
				<label><strong><?php echo $lang_common['Message'] ?></strong><br />
				<textarea name="req_message" rows="20" cols="95" onkeyup="setCaret(this);" onclick="setCaret(this);" onselect="setCaret(this);" onkeypress="if (event.keyCode==10 || (event.ctrlKey && event.keyCode==13))document.getElementById('submit').click()" tabindex="4"></textarea><br /></label>
				<div class="bbincrement"><a href="#" onclick="incrementForm();return false;" style="text-decoration:none">[ + ]</a> <a href="#" onclick="decrementForm();return false;" style="text-decoration:none">[ − ]</a></div>
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
		$checkboxes[] = '<label><input type="checkbox" name="hide_smilies" value="1" tabindex="5"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' />'.$lang_post['Hide smilies'];

	$checkboxes[] = '<label><input type="checkbox" name="savemessage" value="1" tabindex="6" checked="checked" />'.$lang_pms['Save message'];

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
			<p><input type="submit" id="submit" name="submit" value="<?php echo $lang_pms['Send'] ?>" tabindex="7" accesskey="s" /><input type="submit" onclick="xajax_getpreview(xajax.getFormValues('post')); document.location.href='#ajaxpostpreview'; return false;" name="preview" value="<?php echo $lang_common['Preview'] ?>" tabindex="8" accesskey="p" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
			<!--// MOD AJAX post preview -->
		</form>
	</div>
</div>
<?php

	}

?>















<?php
if(isset($_GET['id'])){
	$forum_id = $id;
}
$footer_style = 'message_list';
require PUN_ROOT.'footer.php';
