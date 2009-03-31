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


// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] > PUN_MOD)
	message($lang_common['No permission']);


// Zap a report
if (isset($_POST['zap_id']))
{
	confirm_referrer('admin_reports.php');

	$zap_id = is_array ($_POST['zap_id']) ? $_POST['zap_id'] : array();

	foreach ($zap_id as $group)
		foreach ($group as $report)
			$zap_array[] = intval($report);

	$zapped = 0;
	$unzapped = sizeof($zap_array);

	if ($unzapped)
	{
		$db->query('UPDATE '.$db->prefix.'reports SET zapped='.time().', zapped_by='.$pun_user['id'].' WHERE id IN('.join(',', $zap_array).') AND zapped IS NULL') or error('Unable to zap report', __FILE__, __LINE__, $db->error());
		$zapped = $db->affected_rows();
		$unzapped -= $zapped;
	}

	redirect('admin_reports.php', ($unzapped ? ($zapped ? 'Some' : 'No') : 'All') . ' selected reports zapped. Redirecting &hellip;');
}


$page_title = 'Admin | Reports | '.pun_htmlspecialchars($pun_config['o_board_title']);
require PUN_ROOT.'header.php';

generate_admin_menu('reports');

?>
	<div class="blockform">
		<h2><span>New reports</span></h2>
		<div class="box">
			<form name="reports" method="post" action="admin_reports.php?action=zap">
<?php

$result = $db->query('SELECT r.id, r.post_id, r.topic_id, r.forum_id, r.reported_by, r.created, r.message, t.subject, f.forum_name, u.username AS reporter, p.id AS post_exists FROM '.$db->prefix.'reports AS r LEFT JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$db->prefix.'posts AS p ON r.post_id=p.id WHERE r.zapped IS NULL ORDER BY post_id DESC, created') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{

?>
				<p class="submittop">
					<input type="submit" value=" Zap selected reports " />
				</p>
<?php

	$group_id = $last_group_id = 0;
	while ($cur_report = $db->fetch_assoc($result))
	{
		$reporter = ($cur_report['reporter'] != '') ? '<a href="profile.php?id='.$cur_report['reported_by'].'">'.pun_htmlspecialchars($cur_report['reporter']).'</a>' : 'Deleted user';
		$forum = ($cur_report['forum_name'] != '') ? '<a href="viewforum.php?id='.$cur_report['forum_id'].'">'.pun_htmlspecialchars($cur_report['forum_name']).'</a>' : 'Deleted';
		$topic = ($cur_report['subject'] != '') ? '<a href="viewtopic.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($cur_report['subject']).'</a>' : 'Deleted';
		$post = ($cur_report['post_id'] != '') ? str_replace("\n", '<br />', pun_htmlspecialchars($cur_report['message'])) : 'Deleted';
		$postid = ($cur_report['post_exists'] != '') ? '<a href="viewtopic.php?pid='.$cur_report['post_id'].'#p'.$cur_report['post_id'].'">Post #'.$cur_report['post_id'].'</a>' : 'Deleted';

		$last_group_id = $cur_report['post_id'];

		if ($group_id != $last_group_id)
		{

			if ($group_id)
			{

?>
							</table>
						</div>
					</fieldset>
				</div>
<?php

			}

			$group_id = $last_group_id;

?>
				<div class="inform">
					<fieldset>
						<legend>First report: <?php echo format_time($cur_report['created']) ?></legend>
						<div class="infldset">
							<table cellspacing="0">
								<tr>
									<th scope="row">Forum&nbsp;&raquo;&nbsp;Topic&nbsp;&raquo;&nbsp;Post</th>
									<td><input type="checkbox" onclick="toggleReports(<?php echo $last_group_id ?>, this)" /></td>
									<td><?php echo $forum ?>&nbsp;&raquo;&nbsp;<?php echo $topic ?>&nbsp;&raquo;&nbsp;<?php echo $postid ?></td>
								</tr>
<?php

		}

		if ($group_id == $last_group_id)
		{

?>
								<tr>
									<th scope="row">Report by <?php echo $reporter ?><br /><?php echo format_time($cur_report['created']) ?></th>
									<td width="1"><input type="checkbox" name="zap_id[<?php echo $last_group_id ?>][]" value="<?php echo $cur_report['id'] ?>" /></td>
									<td><?php echo $post ?></td>
								</tr>
<?php

		}

	}

?>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submittop">
					<input type="submit" value=" Zap selected reports " />
				</p>
<?php

}
else
	echo "\t\t\t\t".'<p>There are no new reports.</p>'."\n";

?>
			</form>
		</div>
	</div>

	<div class="blockform block2">
		<h2><span>30 last zapped reports</span></h2>
		<div class="box">
			<div class="fakeform">
<?php

$result = $db->query('SELECT r.id, r.post_id, r.topic_id, r.forum_id, r.reported_by, r.message, r.zapped, r.zapped_by AS zapped_by_id, r.created, t.subject, f.forum_name, u.username AS reporter, u2.username AS zapped_by, p.id AS post_exists FROM '.$db->prefix.'reports AS r LEFT JOIN '.$db->prefix.'topics AS t ON r.topic_id=t.id LEFT JOIN '.$db->prefix.'forums AS f ON r.forum_id=f.id LEFT JOIN '.$db->prefix.'users AS u ON r.reported_by=u.id LEFT JOIN '.$db->prefix.'users AS u2 ON r.zapped_by=u2.id LEFT JOIN '.$db->prefix.'posts AS p ON r.post_id=p.id WHERE r.zapped IS NOT NULL ORDER BY zapped DESC LIMIT 30') or error('Unable to fetch report list', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{
	$zap_time = $last_zap_time = 0;
	while ($cur_report = $db->fetch_assoc($result))
	{
		$reporter = ($cur_report['reporter'] != '') ? '<a href="profile.php?id='.$cur_report['reported_by'].'">'.pun_htmlspecialchars($cur_report['reporter']).'</a>' : 'Deleted user';
		$forum = ($cur_report['forum_name'] != '') ? '<a href="viewforum.php?id='.$cur_report['forum_id'].'">'.pun_htmlspecialchars($cur_report['forum_name']).'</a>' : 'Deleted';
		$topic = ($cur_report['subject'] != '') ? '<a href="viewtopic.php?id='.$cur_report['topic_id'].'">'.pun_htmlspecialchars($cur_report['subject']).'</a>' : 'Deleted';
		$post = ($cur_report['post_id'] != '') ? str_replace("\n", '<br />', pun_htmlspecialchars($cur_report['message'])) : 'Post deleted';
		$post_id = ($cur_report['post_exists'] != '') ? '<a href="viewtopic.php?pid='.$cur_report['post_id'].'#p'.$cur_report['post_id'].'">Post #'.$cur_report['post_id'].'</a>' : 'Deleted';
		$zapped_by = ($cur_report['zapped_by'] != '') ? '<a href="profile.php?id='.$cur_report['zapped_by_id'].'">'.pun_htmlspecialchars($cur_report['zapped_by']).'</a>' : 'N/A';

		$last_zap_time = $cur_report['zapped'];

		if ($zap_time != $last_zap_time)
		{

			if ($zap_time)
			{

?>
							</table>
						</div>
					</fieldset>
				</div>
<?php

			}

			$zap_time = $last_zap_time;

?>
				<div class="inform">
					<fieldset>
						<legend>Zapped: <?php echo format_time($cur_report['zapped']) ?> | Zap speed: <?php echo format_time_interval($cur_report['zapped'] - $cur_report['created']) ?>| Zap initiator: <?php echo $zapped_by ?></legend>
						<div class="infldset">
							<table cellspacing="0">
								<tr>
									<th scope="row">Forum&nbsp;&raquo;&nbsp;Topic&nbsp;&raquo;&nbsp;Post</th>
									<td><?php echo $forum ?>&nbsp;&raquo;&nbsp;<?php echo $topic ?>&nbsp;&raquo;&nbsp;<?php echo $post_id ?></td>
								</tr>
<?php

		}

		if ($zap_time == $cur_report['zapped'])
		{

?>
								<tr>
									<th scope="row">Reported by <?php echo $reporter ?><br /><?php echo format_time($cur_report['created']) ?></th>
									<td><?php echo $post ?></td>
								</tr>
<?php

		}

	}

?>
							</table>
						</div>
					</fieldset>
				</div>
<?php

}
else
	echo "\t\t\t\t".'<p>There are no zapped reports.</p>'."\n";

?>
			</div>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php

require PUN_ROOT.'footer.php';
