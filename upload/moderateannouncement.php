<?php
/***********************************************************************

  Caleb Champlin (med_mediator@hotmail.com)

  This file is is a modification of a file from of PunBB.

************************************************************************/

/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)

  This file is part of PunBB.

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


if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang_common['No permission']);


// Load the misc.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/misc.php';


// All other topic moderation features require a topic id in GET
if (isset($_GET['tid']))
{
	$tid = intval($_GET['tid']);
	if ($tid < 1)
		message($lang_common['Bad request']);

	// Fetch some info about the topic
	$result = $db->query('SELECT t.subject, t.num_replies FROM '.$db->prefix.'topics AS t WHERE t.id='.$tid) or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_topic = $db->fetch_assoc($result);


	// Delete one or more posts
	if (isset($_POST['delete_posts']) || isset($_POST['delete_posts_comply']))
	{
		$posts = $_POST['posts'];
		if (empty($posts))
			message($lang_misc['No posts selected']);

		if (isset($_POST['delete_posts_comply']))
		{
			confirm_referrer('moderateannouncement.php');

			if (preg_match('/[^0-9,]/', $posts))
				message($lang_common['Bad request']);

			// Delete the posts
			$db->query('DELETE FROM '.$db->prefix.'posts WHERE id IN('.$posts.')') or error('Unable to delete posts', __FILE__, __LINE__, $db->error());

			require PUN_ROOT.'include/search_idx.php';
			strip_search_index($posts);

			// Get last_post, last_post_id, and last_poster for the topic after deletion
			$result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
			$last_post = $db->fetch_assoc($result);

			// How many posts did we just delete?
			$num_posts_deleted = substr_count($posts, ',') + 1;

			// Update the topic
			$db->query('UPDATE '.$db->prefix.'topics SET last_post='.$last_post['posted'].', last_post_id='.$last_post['id'].', last_poster=\''.$db->escape($last_post['poster']).'\', num_replies=num_replies-'.$num_posts_deleted.' WHERE id='.$tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());


			redirect('viewannouncement.php?id='.$tid, $lang_misc['Delete posts redirect']);
		}


		$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_misc['Moderate'];
		require PUN_ROOT.'header.php';

?>
<div class="blockform">
	<h2><span><?php echo $lang_misc['Delete posts'] ?></span></h2>
	<div class="box">
		<form method="post" action="moderateannouncement.php?tid=<?php echo $tid ?>">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_misc['Confirm delete legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="posts" value="<?php echo implode(',', array_keys($posts)) ?>" />
						<p><?php echo $lang_misc['Delete posts comply'] ?></p>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="delete_posts_comply" value="<?php echo $lang_misc['Delete'] ?>" /><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
		</form>
	</div>
</div>
<?php

		require PUN_ROOT.'footer.php';
	}


	// Show the delete multiple posts view

	// Load the viewtopic.php language file
	require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';

	// Used to disable the Move and Delete buttons if there are no replies to this topic
	$button_status = ($cur_topic['num_replies'] == 0) ? ' disabled' : '';


	// Determine the post offset (based on $_GET['p'])
	$num_pages = ceil(($cur_topic['num_replies'] + 1) / $pun_user['disp_posts']);

	$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
	$start_from = $pun_user['disp_posts'] * ($p - 1);

	// Generate paging links
	$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'moderateannouncement.php?tid='.$tid);


	if ($pun_config['o_censoring'] == '1')
		$cur_topic['subject'] = censor_words($cur_topic['subject']);


	$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$cur_topic['subject'];
	require PUN_ROOT.'header.php';

?>
<div class="linkst">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $paging_links ?></p>
		<ul><li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li><li>&nbsp;&raquo;&nbsp;<a href="viewforum.php?id=<?php echo $fid ?>"><?php echo pun_htmlspecialchars($cur_topic['forum_name']) ?></a></li><li>&nbsp;&raquo;&nbsp;<?php echo pun_htmlspecialchars($cur_topic['subject']) ?></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<form method="post" action="moderateannouncement.php?tid=<?php echo $tid ?>">
<?php

	require PUN_ROOT.'include/parser.php';

	$bg_switch = true;	// Used for switching background color in posts
	$post_count = 0;	// Keep track of post numbers

	// Retrieve the posts (and their respective poster)
	$result = $db->query('SELECT u.title, u.num_posts, g.g_id, g.g_user_title, p.id, p.poster, p.poster_id, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE p.topic_id='.$tid.' ORDER BY p.id LIMIT '.$start_from.','.$pun_user['disp_posts'], true) or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

	while ($cur_post = $db->fetch_assoc($result))
	{
		$post_count++;

		// If the poster is a registered user.
		if ($cur_post['poster_id'] > 1)
		{
			$poster = '<a href="profile.php?id='.$cur_post['poster_id'].'">'.pun_htmlspecialchars($cur_post['poster']).'</a>';

			// get_title() requires that an element 'username' be present in the array
			$cur_post['username'] = $cur_post['poster'];
			$user_title = get_title($cur_post);

			if ($pun_config['o_censoring'] == '1')
				$user_title = censor_words($user_title);
		}
		// If the poster is a guest (or a user that has been deleted)
		else
		{
			$poster = pun_htmlspecialchars($cur_post['poster']);
			$user_title = $lang_topic['Guest'];
		}

		// Switch the background color for every message.
		$bg_switch = ($bg_switch) ? $bg_switch = false : $bg_switch = true;
		$vtbg = ($bg_switch) ? ' roweven' : ' rowodd';

		// Perform the main parsing of the message (BBCode, smilies, censor words etc)
		$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

?>

<div class="blockpost<?php echo $vtbg ?>">
	<a name="<?php echo $cur_post['id'] ?>"></a>
	<h2><span><span class="conr">#<?php echo ($start_from + $post_count) ?>&nbsp;</span><a href="viewannouncement.php?pid=<?php echo $cur_post['id'].'#p'.$cur_post['id'] ?>"><?php echo format_time($cur_post['posted']) ?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postleft">
				<dl>
					<dt><strong><?php echo $poster ?></strong></dt>
					<dd><strong><?php echo $user_title ?></strong></dd>
				</dl>
			</div>
			<div class="postright">
				<h3 class="nosize"><?php echo $lang_common['Message'] ?></h3>
				<div class="postmsg">
					<?php echo $cur_post['message']."\n" ?>
<?php if ($cur_post['edited'] != '') echo "\t\t\t\t\t".'<p class="postedit"><em>'.$lang_topic['Last edit'].' '.pun_htmlspecialchars($cur_post['edited_by']).' ('.format_time($cur_post['edited']).')</em></p>'."\n"; ?>
				</div>
				<?php if ($start_from + $post_count > 1) echo '<p class="multidelete"><label><strong>'.$lang_misc['Select'].'</strong>&nbsp;&nbsp;<input type="checkbox" name="posts['.$cur_post['id'].']" value="1" /></label></p>'."\n" ?>
			</div>
			<div class="clearer"></div>
		</div>
	</div>
</div>




<?php

	}

?>
<div class="postlinksb">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $paging_links ?></p>
		<p class="conr"><input type="submit" name="delete_posts" value="<?php echo $lang_misc['Delete'] ?>"<?php echo $button_status ?> /></p>
		<div class="clearer"></div>
	</div>
</div>
</form>
<?php

	require PUN_ROOT.'footer.php';
}


// Open or close one or more topics
else if (isset($_REQUEST['open']) || isset($_REQUEST['close']))
{
	$action = (isset($_REQUEST['open'])) ? 0 : 1;

	confirm_referrer('viewannouncement.php');

	$topic_id = ($action) ? intval($_GET['close']) : intval($_GET['open']);
	if ($topic_id < 1)
		message($lang_common['Bad request']);

	$db->query('UPDATE '.$db->prefix.'topics SET closed='.$action.' WHERE id='.$topic_id) or error('Unable to close topic', __FILE__, __LINE__, $db->error());

	$redirect_msg = ($action) ? $lang_misc['Close topic redirect'] : $lang_misc['Open topic redirect'];
	redirect('viewannouncement.php?id='.$topic_id, $redirect_msg);
}
