<?php

	// If PUN isn't defined, the file is accessed directly
	if(!defined('PUN'))
		die('No way!');

	require PUN_ROOT.'lang/'.$language.'/splittopic.php';
	require PUN_ROOT.'lang/'.$language.'/post.php';
	require PUN_ROOT.'include/parser.php';

	$posts = $_POST['posts'];
	if (empty($posts))
		message($lang_mod['No posts selected']);

	$page_title = $lang_misc['Moderate'].' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	require $pun_root.'header.php';

	if( isset($_POST['create_topic']) )
	{

?>

<table class="punspacer" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<form method="post" action="moderate.php?fid=<?php echo $fid ?>&amp;tid=<?php echo $tid ?>">
	<input type="hidden" name="posts" value="<?php echo implode(',', array_keys($posts)) ?>">
	<input type="hidden" name="postcount" value="<?php echo sizeof($posts); ?>">
	<table class="punmain" cellspacing="1" cellpadding="4">
		<tr class="punhead">
			<td class="punhead" colspan="2"><?php echo $lang_mod['Create topic'] ?></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php echo $lang_common['Subject']; ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">&nbsp;<input type="text" name="new_topic_subject" size="80" maxlength="70" tabindex="1"></td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><b><?php echo $lang_common['Forum']; ?></b>&nbsp;&nbsp;</td>
			<td class="puncon2">
				&nbsp;<select name="new_forum_id">
<?php

	$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id ORDER BY c.disp_position, c.id, f.disp_position') or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

	while ($cur_forum = $db->fetch_assoc($result))
	{
		if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
		{
			if (!empty($cur_category))
				echo "\t\t\t\t\t".'</optgroup>'."\n";

			echo "\t\t\t\t\t".'<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">'."\n";
			$cur_category = $cur_forum['cid'];
		}

		if ($cur_forum['fid'] != $forum_id)
			echo "\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'">'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'" selected>'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
	}

?>
					</optgroup>
				</select>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php echo $lang_mod['First post']; ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>
<?php

	$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id WHERE p.topic_id='.$tid.' AND p.id IN ('.implode(',', array_keys($posts)).') ORDER BY p.id') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$cur_post = $db->fetch_assoc($result);

	echo parse_message($cur_post['message'],$cur_post['hide_smilies']);

?>
				<br><br>
			</td>
		</tr>
		<tr>
			<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php echo $lang_common['Actions']; ?>&nbsp;&nbsp;</td>
			<td class="puncon2">
				<br>&nbsp;<input type="submit" name="create_topic_comply" value="<?php echo $lang_mod['Create topic'] ?>">&nbsp;&nbsp;&nbsp;<a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a><br><br>
			</td>
		</tr>
	</table>
</form>

<table class="punspacer" cellspacing="1" cellpadding="4"><tr><td>&nbsp;</td></tr></table>

<?php

	}

	else if(isset($_POST['create_topic_comply']))
	{
		// Check topic length
		if($_POST['new_topic_subject'] == ''){
			$_POST['new_topic_subject'] = 'Unnamed';
		}

		$id = intval($_POST['new_forum_id']);
		$tid = intval($_GET['tid']);
		$num_posts = intval($_POST['postcount']);

		// Fetch first post info
		$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.posted, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id WHERE p.topic_id='.$tid.' AND p.id IN ('.$posts.') ORDER BY p.id') or error('Unable to fetch post info while first fetching', __FILE__, __LINE__, $db->error());
		$cur_post = $db->fetch_assoc($result);

		// Fetch last post info
		$result = $db->query('SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, p.id, p.poster, p.poster_id, p.poster_ip, p.poster_email, p.message, p.posted, p.edited, p.edited_by FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'users AS u ON u.id=p.poster_id WHERE p.topic_id='.$tid.' AND p.id IN ('.$posts.') ORDER BY p.id DESC') or error('Unable to fetch post info while fetching', __FILE__, __LINE__, $db->error());
		$last_post = $db->fetch_assoc($result);

		// Create the topic (start transaction)
		$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, num_replies, last_post_id, forum_id) VALUES(\''.addslashes($cur_post['poster']).'\', \''.addslashes($_POST['new_topic_subject']).'\', '.$cur_post['posted'].', '.$last_post['posted'].', \''.addslashes($last_post['poster']).'\', '.($num_posts-1).', '.$last_post['id'].', '.$id.')', PUN_TRANS_START) or error('Unable to create topic (start)', __FILE__, __LINE__, $db->error());
		$new_tid = $db->insert_id();

		// Move the posts
		$db->query('UPDATE '.$db->prefix.'posts SET topic_id='.$new_tid.' WHERE id IN ('.$posts.')') or die(error('Unable to move posts to new topic', __FILE__, __LINE__, $db->error()));

		// Update the old topic
		$result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$tid.' AND id NOT IN ('.$posts.') ORDER BY id DESC') or error('Unable to fetch post info (update)', __FILE__, __LINE__, $db->error());
		$last_post = $db->fetch_assoc($result);
		$db->query('UPDATE '.$db->prefix.'topics SET num_replies=num_replies-'.$num_posts.', last_poster=\''.addslashes($last_post['poster']).'\', last_post_id='.$last_post['id'].', last_post='.$last_post['posted'].' WHERE id='.$tid) or error('Unable to update old topic', __FILE__, __LINE__, $db->error());

		// Update "FROM"-forum
		$result = $db->query('SELECT forum_id FROM '.$db->prefix.'topics WHERE id='.$tid) or error('Unable to fetch topic info (FROM)', __FILE__, __LINE__, $db->error());
		$fid = $db->result($result, 0);
		if($fid != $id)
			update_forum($fid);

		// Update "TO"-forum
		update_forum($id, PUN_TRANS_END);	// end transaction

		// Redirect to the old topic
		$redirect_msg = $num_posts > 1 ? $lang_mod['Move posts redirect'] : $lang_mod['Move post redirect'];
		redirect('viewtopic.php?id='.$tid, $redirect_msg);

	}

	else
	{

		message($lang_common['Bad request']);

	}

	require PUN_ROOT.'footer.php';

?>
