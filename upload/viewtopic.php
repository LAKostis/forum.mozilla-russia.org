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


define('PUN_ROOT', './');
define('PUN_NO_BAN', 1);
require PUN_ROOT.'post.common.php';
require PUN_ROOT.'include/common.php';


if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
	message($lang_common['Bad request']);

// Load the viewtopic.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/reputation.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/userlist.php';

// create SQL for multigroup mod
$mgrp_extra = multigrp_getSql($db);

// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	$result = $db->query('SELECT topic_id FROM '.$db->prefix.'posts WHERE id='.$pid) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$id = $db->result($result);

	// Determine on what page the post is located (depending on $pun_user['disp_posts'])
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$id.' ORDER BY posted') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	for ($i = 0; $i < $num_posts; ++$i)
	{
		$cur_id = $db->result($result, $i);
		if ($cur_id == $pid)
			break;
	}
	++$i;	// we started at 0

	$_GET['p'] = ceil($i / $pun_user['disp_posts']);
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new' && !$pun_user['is_guest'])
{
	// MOD: MARK TOPICS AS READ - 5 LINES NEW CODE FOLLOW
	if(!empty($pun_user['read_topic']['t'][$id])) {
		$last_read = $pun_user['read_topic']['t'][$id];
	} else { // if the user hasn't read the topic
		$last_read = $pun_user['last_visit'];
	}
	// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
	$result = $db->query('SELECT MIN(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id.' AND posted>'.$last_read) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$first_new_post_id = $db->result($result);

	if ($first_new_post_id)
		hidden_redirect('viewtopic.php?pid='.$first_new_post_id.'#p'.$first_new_post_id);
	else	// If there is no new post, we go to the last post
		hidden_redirect('viewtopic.php?id='.$id.'&action=last');

	exit;
}

// If action=last, we redirect to the last post
else if ($action == 'last')
{
	$result = $db->query('SELECT MAX(id) FROM '.$db->prefix.'posts WHERE topic_id='.$id) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$last_post_id = $db->result($result);

	if ($last_post_id)
	{
		hidden_redirect('viewtopic.php?pid='.$last_post_id.'#p'.$last_post_id);
		exit;
	}
}


// Fetch some info about the topic
if (!$pun_user['is_guest'])
{
	// MOD Announcement: CODE FOLLOWS
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.announcement FROM ' . $db->prefix . 'topics AS t WHERE t.id=' . $id . ' AND t.announcement=\'1\' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
	if (!$db->num_rows($result))
		$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.announcement, t.sticky, t.last_post, t.question, t.yes, t.no, t.poster, t.post_sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') '.$mgrp_extra.' AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
}
else
{
	// MOD Announcement: CODE FOLLOWS
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.announcement FROM ' . $db->prefix . 'topics AS t WHERE t.id=' . $id . ' AND t.announcement=\'1\' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.announcement, t.sticky, t.question, t.yes, t.no, t.post_sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
}

define('TOPIC_ID', $id);

if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $db->fetch_assoc($result);

$forum_id = ($cur_topic['announcement'] == '1') ? 'announcement' : $cur_topic['forum_id'];

// MOD: MARK TOPICS AS READ - 1 LINE NEW CODE FOLLOWS
if (!$pun_user['is_guest']) mark_topic_read($id, $cur_topic['forum_id'], $cur_topic['last_post']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$is_admmod = ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && array_key_exists($pun_user['username'], $mods_array))) ? true : false;

// Can we or can we not post replies?
if ($cur_topic['closed'] == '0')
{
	if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1' || $is_admmod)
		$post_link = '<p class="pagelink conl"><a href="post.php?tid='.$id.'" class="reply">'.$lang_topic['Post reply'].'</a></p>'."\n";
	else
		$post_link = '';
}
else
{
	$post_link = '<p class="pagelink conl">' . $lang_topic['Topic closed'];

	if ($is_admmod)
		$post_link .= '<br /><a href="post.php?tid='.$id.'" class="reply">'.$lang_topic['Post reply'].'</a>';

	$post_link .= '</p>'."\n";
}


// Determine the post offset (based on $_GET['p'])
$num_pages = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

$p = isset($_GET['p']) ? ($_GET['p'] == 'last' ? $num_pages : ((!is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'])) : 1;

$start_from = $pun_user['disp_posts'] * ($p - 1);

// Generate paging links
$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'viewtopic.php?id='.$id);


if ($pun_config['o_censoring'] == '1')
	$cur_topic['subject'] = censor_words($cur_topic['subject']);


$quickpost = false;
if ($pun_config['o_quickpost'] == '1' &&
	$cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') &&
	($cur_topic['closed'] == '0' || $is_admmod))
{
	$required_fields = array('req_message' => $lang_common['Message']);
	$quickpost = true;
}

$page_title = pun_htmlspecialchars($cur_topic['subject']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
define('PUN_ALLOW_INDEX', 1);
require PUN_ROOT.'header.php';
require PUN_ROOT.'include/parser.php';

?>
<div class="linkst">
	<div class="inbox">
		<?php echo $post_link ?>
		<p class="postlink conr"><?php echo $paging_links ?></p>
		<ul><li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li><li>&nbsp;&raquo;&nbsp;<a href="viewforum.php?id=<?php echo $cur_topic['forum_id'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li><li>&nbsp;&raquo;&nbsp;<?php echo iconize_topic(pun_htmlspecialchars($cur_topic['subject']), $cur_topic['forum_id']) ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<?php

$bg_switch = true;	// Used for switching background color in posts
$post_count = 0;	// Keep track of post numbers

// MOD
require PUN_ROOT.'include/polls/viewpoll.php';
// END MOD

$topic_sql = 'SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, u.reputation_minus, u.reputation_plus, u.show_online, u.imgaward, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.blocked, g.g_id, g.g_user_title, g.g_title, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.topic_id='.$id.' ORDER BY p.id LIMIT '.$start_from.','.$pun_user['disp_posts'];

if($start_from && $cur_topic['post_sticky'])
	$topic_sql = '(SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, u.reputation_minus, u.reputation_plus, u.show_online, u.imgaward, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, p.blocked, g.g_id, g.g_user_title, g.g_title, o.user_id AS is_online FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id LEFT JOIN '.$db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) WHERE p.topic_id='.$id.' ORDER BY p.id LIMIT 0,1) UNION (' . $topic_sql . ')';

// Retrieve the posts (and their respective poster/online status)
$result = $db->query($topic_sql, true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
while ($cur_post = $db->fetch_assoc($result))
{
	$post_count++;
	$user_avatar = '';
	$user_info = array();
	$user_contacts = array();
	$post_actions = array();
	$is_online = '';
	$signature = '';
	$user_image_award = '';

	// If the poster is a registered user.
	if ($cur_post['poster_id'] > 1)
	{
		// Image Award Mod Block Start
		if(strlen($cur_post['imgaward']) > 0)
		{
			$awardmod_filename=$cur_post['imgaward'];
			$awardmod_temp=substr($awardmod_filename,strrpos($awardmod_filename,'_')+1); //we still have the file extentsion
			$awardmod_temp=substr($awardmod_temp,0,strpos($awardmod_temp,'.'));
			$awardmod_dimensions = explode('x',$awardmod_temp);
			$awardmod_name=str_replace('_',' ',substr($awardmod_filename,0,strrpos($awardmod_filename,'_')));
			if($pun_config['o_avatars'] == '1' && $pun_user['show_avatars'] != '0')
				$user_image_award = "\t\t\t\t\t".'<dd><img src="img/awards/'.$awardmod_filename.'" width="'.$awardmod_dimensions[0].'" height="'.$awardmod_dimensions[1].'" alt="Award: '.$awardmod_name.'" /></dd>';
			else
				$user_image_award = "\t\t\t\t\t".'<dd>Award: "'.$awardmod_name.'"</dd>';
		} // Image Award Mod Block End
		if (!$quickpost)
			$username = '<a href="profile.php?id='.$cur_post['poster_id'].'">'.pun_htmlspecialchars($cur_post['username']).'</a>';
		else
			$username = '<a href="profile.php?id='.$cur_post['poster_id'].'" onclick="pasteN(this);return false;">'.pun_htmlspecialchars($cur_post['username']).'</a>';
		$user_title = get_title($cur_post);

		$user_banned = $user_title == $lang_common['Banned'];

		$group_title = $cur_post['g_title'];

		if ($pun_config['o_censoring'] == '1')
			$user_title = censor_words($user_title);

		// Format the online indicator
		$is_online = ($cur_post['is_online'] == $cur_post['poster_id'] && $cur_post['show_online'] == '1' || $cur_post['is_online'] == $cur_post['poster_id'] && $cur_post['show_online'] == 0 && $pun_user['group_id'] < PUN_MOD) ? '<strong class="online">'.$lang_topic['Online'].'</strong>' : '<span class="offline">' . $lang_topic['Offline'] . '</span>';

		if ($pun_config['o_avatars'] == '1' && !$user_banned && $cur_post['use_avatar'] == '1' && $pun_user['show_avatars'] != '0' && $pun_user['is_guest'] != '1')
		{
			if ($img_size = @getimagesize($pun_config['o_avatars_dir'].'/'.$cur_post['poster_id'].'.gif'))
				$user_avatar = '<img src="'.$pun_config['o_avatars_dir'].'/'.$cur_post['poster_id'].'.gif" '.$img_size[3].' alt="" />';
			else if ($img_size = @getimagesize($pun_config['o_avatars_dir'].'/'.$cur_post['poster_id'].'.jpg'))
				$user_avatar = '<img src="'.$pun_config['o_avatars_dir'].'/'.$cur_post['poster_id'].'.jpg" '.$img_size[3].' alt="" />';
			else if ($img_size = @getimagesize($pun_config['o_avatars_dir'].'/'.$cur_post['poster_id'].'.png'))
				$user_avatar = '<img src="'.$pun_config['o_avatars_dir'].'/'.$cur_post['poster_id'].'.png" '.$img_size[3].' alt="" />';
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
				$user_info[] = '<dd>'.$lang_common['Posts'].': <a href="search.php?action=show_user&amp;user_id='.$cur_post['poster_id'].'">'.$cur_post['num_posts'].'</a>';

			// Now let's deal with the contact links (E-mail and URL)
			if (($cur_post['email_setting'] == '0' && !$pun_user['is_guest']) || $pun_user['g_id'] < PUN_GUEST)
				$user_contacts[] = '<a href="mailto:'.$cur_post['email'].'" class="email">'.$lang_common['E-mail'].'</a>';
			else if ($cur_post['email_setting'] == '1' && !$pun_user['is_guest'])
				$user_contacts[] = '<a href="misc.php?email='.$cur_post['poster_id'].'" class="email">'.$lang_common['E-mail'].'</a>';
			require(PUN_ROOT.'include/pms/viewtopic_PM-link.php');
			if ($cur_post['url'] != '')
				$user_contacts[] = '<a href="'.pun_htmlspecialchars($cur_post['url']).'" class="website">'.$lang_topic['Website'].'</a>';
		}

		if ($pun_user['g_id'] < PUN_GUEST)
			$user_info[] = '<dd>IP: <a href="admin_users.php?show_users='.$cur_post['poster_ip'].'">'.$cur_post['poster_ip'].'</a>';

		if (($pun_user['g_id'] < PUN_GUEST || ($pun_user['g_id'] > PUN_GUEST && $pun_user['id'] == $cur_post['poster_id'])) && $cur_post['admin_note'] != '')
				$user_info[] = '<dd><strong>'.pun_htmlspecialchars($cur_post['admin_note']).'</strong>';
	}
	// If the poster is a guest (or a user that has been deleted)
	else
	{
		$username = pun_htmlspecialchars($cur_post['username']);

		$group_title = $cur_post['g_title'];

		$user_info[] = '<dd>'.$lang_ul['User group'].': <strong>'.$group_title.'</strong>';

		if ($pun_user['g_id'] < PUN_GUEST)
			$user_info[] = '<dd>IP: <a href="admin_users.php?show_users='.$cur_post['poster_ip'].'">'.$cur_post['poster_ip'].'</a>';

		if ($pun_config['o_show_user_info'] == '1' && $cur_post['poster_email'] != '' && !$pun_user['is_guest'])
			$user_contacts[] = '<a href="mailto:'.$cur_post['poster_email'].'" class="email">'.$lang_common['E-mail'].'</a>';
	}

	// Generation post action array (quote, edit, delete etc.)
	if (!$is_admmod)
	{
		if (($cur_post['poster_id'] != '1') && $quickpost)
			$post_actions[] = '<li class="postreport"><a href="profile.php?id='.$cur_post['poster_id'].'" class="profile">'.$lang_common['Profile'].'</a>';
		if (!$pun_user['is_guest'])
			$post_actions[] = '<li class="postreport"><a href="misc.php?report='.$cur_post['id'].'" class="report">'.$lang_topic['Report'].'</a>';

		if ($cur_topic['closed'] == '0')
		{
			if ($cur_post['poster_id'] == $pun_user['id'])
			{
				if ((($start_from + $post_count) == 1 && $pun_user['g_delete_topics'] == '1') || (($start_from + $post_count) > 1 && $pun_user['g_delete_posts'] == '1'))
					$post_actions[] = '<li class="postdelete"><a href="delete.php?id='.$cur_post['id'].'" class="delete">'.$lang_topic['Delete'].'</a>';
				if ($pun_user['g_edit_posts'] == '1')
					$post_actions[] = '<li class="postedit"><a href="edit.php?id='.$cur_post['id'].'" class="edit">'.$lang_topic['Edit'].'</a>';
			}

			if (($cur_topic['post_replies'] == '' && $pun_user['g_post_replies'] == '1') || $cur_topic['post_replies'] == '1')
			$post_actions[] = '</li><li class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'" class="reply">'.$lang_topic['Reply'].'</a>';
			if ($quickpost)
			$post_actions[] = '</li><li class="postquote" onmouseover="copyQ(this);"><a href="viewtopic.php?pid='.$cur_post['id'].'#p'.$cur_post['id'].'" onclick=\'pasteQ("' . pun_htmlspecialchars($cur_post['username']) . '", ' . $cur_post['id'] . ');return false;\' class="quote">'.$lang_topic['Quote'].'</a>';
		}
	}
	else
	{
		if (($cur_post['poster_id'] != '1') && $quickpost)
			$post_actions[] = '<li class="postreport"><a href="profile.php?id='.$cur_post['poster_id'].'" class="profile">'.$lang_common['Profile'].'</a>';

		$post_actions[] = '<li class="postreport"><a href="misc.php?report='.$cur_post['id'].'" class="report">'.$lang_topic['Report'].'</a>'.$lang_topic['Link separator'].'</li><li class="postdelete"><a href="delete.php?id='.$cur_post['id'].'" class="delete">'.$lang_topic['Delete'].'</a>'.$lang_topic['Link separator'].'</li><li class="postedit"><a href="edit.php?id='.$cur_post['id'].'" class="edit">'.$lang_topic['Edit'].'</a>'.$lang_topic['Link separator'].'</li><li class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'" class="reply">'.$lang_topic['Reply'].'</a>'.$lang_topic['Link separator'].'</li><li class="postquote" onmouseover="copyQ(this);"><a href="viewtopic.php?pid='.$cur_post['id'].'#p'.$cur_post['id'].'" onclick=\'pasteQ("' . pun_htmlspecialchars($cur_post['username']) . '", ' . $cur_post['id'] . ');return false;\' class="quote">'.$lang_topic['Quote'].'</a>';
	}

	// Switch the background color for every message.
	$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;
	$vtbg = ($bg_switch) ? ' roweven' : ' rowodd';


	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

	// Do signature parsing/caching
	if (!$user_banned && $cur_post['signature'] != '' && $pun_user['show_sig'] != '0')
	{
		if (isset($signature_cache[$cur_post['poster_id']]))
			$signature = $signature_cache[$cur_post['poster_id']];
		else
		{
			$signature = parse_signature($cur_post['signature']);
			$signature_cache[$cur_post['poster_id']] = $signature;
		}
	}

?>
<div id="p<?php echo $cur_post['id'] ?>" class="blockpost<?php echo $vtbg ?><?php if (($post_count + $start_from) == 1) echo ' firstpost'; ?>">
	<h2><span><span class="conr"><a href="viewtopic.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>">№<?php
	if ($cur_topic['post_sticky']) {
		$cur_topic['post_sticky'] = 0;
		echo 1;
	}
	else
		echo $start_from + $post_count;
	?></a></span><a href="viewtopic.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>"><?php echo format_time($cur_post['posted']) ?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postleft">
				<dl>
					<dt><strong><?php echo $username ?></strong></dt>
<?php if ($cur_post['poster_id'] > 1): ?>
					<dd class="usertitle"><strong><?php echo $user_title ?></strong></dd>
<?php endif; ?>
					<dd class="postavatar"><?php if (!$user_avatar) {echo "&nbsp;";}else {echo $user_avatar;} ## Validation fix ?></dd>
<?php if (strlen($user_image_award)>0) echo $user_image_award; ?>
<?php if (count($user_info)) echo "\t\t\t\t\t".implode('</dd>'."\n\t\t\t\t\t", $user_info).'</dd>'."\n"; ?>
					<dd><?php
	if($pun_config['o_reputation_enabled'] == '1' && $cur_post['poster_id'] > 1 && $cur_post['g_id'] > PUN_MOD) {
		echo $lang_reputation['Reputation'];
?>: <strong><small>[ <?php $can_vote = ($pun_user['is_guest'] != true && $pun_user['username'] != $cur_post['username']); if($can_vote) { ?><a href="./reputation.php?id=<?php echo $cur_post['poster_id']; ?>&amp;plus"><img src="./img/plus.png" alt="+" border="0" /></a><?php } else { ?>+<?php } ?> <?php echo $cur_post['reputation_plus']; ?> / <?php if($can_vote) { ?><a href="./reputation.php?id=<?php echo $cur_post['poster_id']; ?>&amp;minus"><img src="./img/minus.png" alt="−" border="0" /></a><?php } else { ?>−<?php } ?> <?php echo $cur_post['reputation_minus']; ?> ]</small></strong><?php } ?></dd>
<?php if (count($user_contacts)) echo "\t\t\t\t\t".'<dd class="usercontacts">'.implode('&nbsp;&nbsp;', $user_contacts).'</dd>'."\n"; ?>
				</dl>
			</div>
			<div class="postright">
				<h3><?php if (($post_count + $start_from) > 1) echo ' Re: '; ?><?php echo pun_htmlspecialchars($cur_topic['subject']) ?></h3>
				<div class="postmsg" id="message<?php echo $cur_post['id'] ?>">
					<?php echo $cur_post['blocked'] ? (
						parse_message('[i]' . $lang_topic['Message blocked'] . '[/i]' . ($pun_user['g_id'] > PUN_MOD ? '' : ' [url='.$pun_config['o_base_url'].'/moderate.php?fid='.$forum_id.'&unblock='.$cur_post['id'].']' . $lang_topic['Unblock message'] . '[/url]' . "\n\n"), 1) . ($pun_user['g_id'] > PUN_MOD ? '' : $cur_post['message'] )
					) : $cur_post['message']."\n" ?>
<?php if ($cur_post['edited'] != '') echo "\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.pun_htmlspecialchars($cur_post['edited_by']).' ('.format_time($cur_post['edited']).')</em></p>'."\n"; ?>
<?php if ($cur_topic['post_sticky'] == 1) echo "\t\t\t\t\t".'<p class="sticky"><em>'.$lang_topic['Sticky'].'</em></p>'."\n"; ?>
				</div>
<?php if ($signature != '') echo "\t\t\t\t".'<div class="postsignature"><hr />'.$signature.'</div>'."\n"; ?>
			</div>
			<div class="clearer"></div>
			<div class="postfootleft"><?php if ($cur_post['poster_id'] > 1) echo '<p>'.$is_online.'</p>'; ?></div>
			<div class="postfootright"><?php echo (count($post_actions)) ? '<ul>'.implode($lang_topic['Link separator'].'</li>', $post_actions).'</li></ul></div>'."\n" : '<div>&nbsp;</div></div>'."\n" ?>
		</div>
	</div>
</div>

<?php

}

?>
<div class="postlinksb">
	<div class="inbox">
		<?php echo $post_link ?>
		<p class="postlink conr"><?php echo $paging_links ?></p>
		<ul><li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li><li>&nbsp;&raquo;&nbsp;<a href="viewforum.php?id=<?php echo $cur_topic['forum_id'] ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li><li>&nbsp;&raquo;&nbsp;<?php echo iconize_topic(pun_htmlspecialchars($cur_topic['subject']), $cur_topic['forum_id']) ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<?php

// Display quick post if enabled
if ($quickpost)
{

?>
<!-- MOD AJAX post preview -->
<div id="ajaxpostpreview"></div>
<!--// MOD AJAX post preview -->
<div class="blockform">
	<h2><span><?php echo $lang_topic['Quick post'] ?></span></h2>
	<div class="box">
		<form id="post" method="post" action="post.php?tid=<?php echo $id ?>" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_common['Write message legend'] ?></legend>
					<div class="infldset txtarea">
						<input type="hidden" name="form_sent" value="1" />
						<input type="hidden" name="form_user" value="<?php echo (!$pun_user['is_guest']) ? pun_htmlspecialchars($pun_user['username']) : 'Guest'; ?>" />
						<?php require PUN_ROOT.'mod_easy_bbcode.php'; ?>
						<label><textarea name="req_message" rows="7" cols="75" tabindex="1" onkeyup="setCaret(this);" onclick="setCaret(this);" onselect="setCaret(this);" onkeypress="if (event.keyCode==10 || (event.ctrlKey && event.keyCode==13))document.getElementById('submit').click()"></textarea></label>
						<div class="bbincrement"><a href="#" onclick="incrementForm();return false;" style="text-decoration:none">[ + ]</a> <a href="#" onclick="decrementForm();return false;" style="text-decoration:none">[ − ]</a></div>
						<ul class="bblinks">
							<li><a href="help.php#bbcode" onclick="window.open(this.href); return false;"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#img" onclick="window.open(this.href); return false;"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
							<li><a href="help.php#smilies" onclick="window.open(this.href); return false;"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?></li>
						</ul>
					</div>
				</fieldset>
			</div>
		<p><input type="submit" id="submit" name="submit" tabindex="2" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /><input type="submit" onclick="xajax_getpreview(xajax.getFormValues('post')); document.location.href='#ajaxpostpreview'; return false;" name="preview" value="<?php echo $lang_common['Preview'] ?>" accesskey="p" /></p>
		</form>
	</div>
</div>
<?php

}

// Increment "num_views" for topic
$low_prio = ($db_type == 'mysql') ? 'LOW_PRIORITY ' : '';
$db->query('UPDATE '.$low_prio.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());

$footer_style = 'viewtopic';
require PUN_ROOT.'footer.php';
