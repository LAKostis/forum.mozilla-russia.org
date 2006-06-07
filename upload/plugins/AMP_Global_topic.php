<?php
/***********************************************************************
  
  Copyright (C) 2005  Connor Dunn (Connorhd@mypunbb.com)

  This software is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  This software is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
    exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);
define('PLUGIN_VERSION',1.0);

if (isset($_POST['post']))
{
	// Do Post
	require PUN_ROOT.'include/search_idx.php';
	if (empty($_POST['subject']) || empty($_POST['message']))
		message('Missing Fields');
	if (!isset($_POST['forums']))
		message('No Forums Selected');
	$now = time();
	$i=0;
	while($i<count($_POST['forums'])){
		$db->query('INSERT INTO '.$db->prefix.'topics (poster, subject, posted, last_post, last_poster, forum_id, sticky) VALUES(\''.$db->escape($pun_user['username']).'\', \''.$db->escape($_POST['subject']).'\', '.$now.', '.$now.', \''.$db->escape($pun_user['username']).'\', '.$_POST['forums'][$i].', '.$_POST['sticky'].')') or error('Unable to create topic', __FILE__, __LINE__, $db->error());
		$new_tid = $db->insert_id();
		$db->query('INSERT INTO '.$db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$db->escape($pun_user['username']).'\', '.$pun_user['id'].', \''.get_remote_address().'\', \''.$db->escape($_POST['message']).'\', \'0\', '.$now.', '.$new_tid.')') or error('Unable to create post', __FILE__, __LINE__, $db->error());
		$new_pid = $db->insert_id();
		$db->query('UPDATE '.$db->prefix.'topics SET last_post_id='.$new_pid.' WHERE id='.$new_tid) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
		update_search_index('post', $new_pid, $_POST['message'], $_POST['subject']);
		update_forum($_POST['forums'][$i]);
		$i++;
	}
	Message('Topic Added');
}
else	// If not, we show the form
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div class="block">
		<h2><span>Global topic - v<?php echo PLUGIN_VERSION ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p>This Plugin allows you to add a topic to multiple forums.</p>
			</div>
		</div>
	</div>
	<div class="blockform">
		<h2 class="block2"><span>Add Topic</span></h2>
		<div class="box">
			<form id="post" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<div class="inform">
					<fieldset>
						<legend>Post Settings</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Subject</th>
								<td>
									<input type="text" name="subject" size="80" maxlength="70" tabindex="1" />
									<span>The subject of the topic.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Forums to post in.</th>
								<td>
									<select name="forums[]" multiple="multiple" size="5">
<?php
		$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=1) WHERE fp.read_forum IS NULL OR fp.read_forum=1 ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());

		$cur_category = 0;
		while ($cur_forum = $db->fetch_assoc($result))
		{
			if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
			{
				if ($cur_category)
					echo '</optgroup>';

				echo '<optgroup label="'.pun_htmlspecialchars($cur_forum['cat_name']).'">';
				$cur_category = $cur_forum['cid'];
			}
			if (!$cur_forum['redirect_url'])
				echo '<option value="'.$cur_forum['fid'].'" selected="selected">'.pun_htmlspecialchars($cur_forum['forum_name']).'</option>';
		}

		echo '</optgroup>';
?>								
									</select>
									<span>Select the forums to post in here.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Message</th>
								<td>
									<textarea name="message" rows="15" cols="70" tabindex="1"></textarea>
									<span>The message of the topic.</span>
								</td>
							</tr>
							<tr>
								<th scope="row">Make Sticky?</th>
								<td>
									<input type="radio" name="sticky" value="1" checked="checked" />&nbsp;<strong>Yes</strong>&nbsp;&nbsp;&nbsp;<input type="radio" name="sticky" value="0" />&nbsp;<strong>No</strong>
									<span>If Yes, topic(s) will be sticky.</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
			<p class="submitend"><input type="submit" name="post" value="Go!" tabindex="2" /></p>
			</form>
		</div>
	</div>
	
<?php
}
?>