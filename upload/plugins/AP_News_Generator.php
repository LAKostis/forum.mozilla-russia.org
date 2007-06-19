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


// The forum from which we'll pull the news bits
$forum_id = 21;

// Number of news posts to include in the index
$num_posts_index = 5;

// Path to news item template
$template_path = PUN_ROOT.'plugins/AP_News_Generator/news.tpl';

// Directories in which plugin will save generated markup (must end with slash)
$output_dir_latest = PUN_ROOT.'news/';
$output_dir_archive = PUN_ROOT.'news/archive/';

/***********************************************************************
                     DO NOT EDIT BELOW THIS LINE
/***********************************************************************/

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);


if (isset($_POST['gen_news']))
{
	// Generate front page news
	$result = $db->query('SELECT id, subject FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' ORDER BY sticky DESC, posted DESC LIMIT 0, '.$num_posts_index) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		message('There are no topics to generate news based on in forum with ID = '.$forum_id.'.');

	require PUN_ROOT.'include/parser.php';

	$news_tpl = file_get_contents($template_path) or error('Unable to open new item template '.$template_path.'. Make sure $template_path is correct', __FILE__, __LINE__);

	$fh = @fopen($output_dir_latest.'news.html', 'wb');
	if (!$fh)
		error('Unable to write news to '.$output_dir_latest.'main.html. Please make sure PHP has write access to the directory '.$output_dir_latest, __FILE__, __LINE__);

	while ($cur_topic = $db->fetch_assoc($result))
	{
		$result2 = $db->query('SELECT posted, poster, message, hide_smilies FROM '.$db->prefix.'posts WHERE topic_id='.$cur_topic['id'].' ORDER BY posted ASC LIMIT 1') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
		$cur_post = $db->fetch_assoc($result2);

		$search = array('<news_subject>', '<news_posted>', '<news_poster>', '<news_message>', '<news_comments>');
        $replace = array(pun_htmlspecialchars($cur_topic['subject']), date('Y-m-d H:i', $cur_post['posted']), pun_htmlspecialchars($cur_post['poster']), parse_message($cur_post['message'], $cur_post['hide_smilies']), '<a href="'.$pun_config['o_base_url'].'/viewtopic.php?id='.$cur_topic['id'].'">Comments</a>');

		fwrite($fh, str_replace($search, $replace, $news_tpl));
	}

	$archive_link = '<a href="archive/">News Archive</a>';
	fwrite($fh,$archive_link);
	
	fclose($fh);


	// Generate monthly archives
	$year_end = intval(date('Y'));
	$month_end = intval(date('n'));

	$year_start = ($month_end != 1) ? $year_end : $year_end-1;
	$month_start = ($month_end != 1) ? $month_end-1 : 12;

	// How far back should we go?
	$result = $db->query('SELECT MIN(posted) FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.'') or error('Unable to fetch earliest topic', __FILE__, __LINE__, $db->error());
	$history_limit = $db->result($result);

	$year_limit = intval(date('Y', $history_limit));
	$month_limit = intval(date('n', $history_limit));

	while ($year_end > $year_limit || $month_end > $month_limit)
	{
		$result = $db->query('SELECT id, subject FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND posted>=UNIX_TIMESTAMP(\''.$year_start.'-'.$month_start.'-01\') AND posted<UNIX_TIMESTAMP(\''.$year_end.'-'.$month_end.'-01\') ORDER BY posted DESC') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result))
		{
			$news_tpl = file_get_contents($template_path) or error('Unable to open new item template '.$template_path.'. Make sure $template_path is correct', __FILE__, __LINE__);

			$fh = @fopen($output_dir_archive.$year_end.'-'.($month_end > 9 ? $month_end : '0'.$month_end).'.html', 'wb');
			if (!$fh)
				error('Unable to write news archive to '.$output_dir_archive.$year_end.'-'.($month_end > 9 ? $month_end : '0'.$month_end).'.htmlPlease make sure PHP has write access to the directory '.$output_dir_archive, __FILE__, __LINE__);

			while ($cur_topic = $db->fetch_assoc($result))
			{
				$result2 = $db->query('SELECT posted, poster, message, hide_smilies FROM '.$db->prefix.'posts WHERE topic_id='.$cur_topic['id'].' ORDER BY posted ASC LIMIT 1') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$cur_post = $db->fetch_assoc($result2);

				$search = array('<news_subject>', '<news_posted>', '<news_poster>', '<news_message>', '<news_comments>');
				$replace = array(pun_htmlspecialchars($cur_topic['subject']), date('Y-m-d H:i', $cur_post['posted']), pun_htmlspecialchars($cur_post['poster']), parse_message($cur_post['message'], $cur_post['hide_smilies']), '<a href="">Comments</a>');

				fwrite($fh, str_replace($search, $replace, $news_tpl));
			}

			fclose($fh);
		}

		$year_end = $year_start;
		$month_end = $month_start;
		$year_start = ($month_end != 1) ? $year_end : $year_end-1;
		$month_start = ($month_end != 1) ? $month_end-1 : 12;
	}

	generate_admin_menu($plugin);

?>
	<div class="block">
		<h2><span>News Generator results</span></h2>
		<div class="box">
			<div class="inbox">
				<p>News generated.</p>
			</div>
		</div>
	</div>
<?php

}
else
{
	generate_admin_menu($plugin);

?>
	<div class="blockform">
		<h2><span>News Generator</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>&amp;foo=bar">
				<div class="inform">
					<fieldset>
						<legend>Generate static news output</legend>
						<div class="infldset">
							<p>This plugin fetches posts from the specified forum and generates static markup for those posts based on the news item template. It outputs news.html containing the most recent news items and a month based archive (e.g. 2005-01.html) for all posts in the forum. <strong>Look at the top of the PHP source code for this plugin to change the settings you see below.</strong></p>
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Fetch news from forum</th>
									<td><span><?php echo $forum_id ?></span></td>
								</tr>
								<tr>
									<th scope="row">No of posts for latest</th>
									<td><span><?php echo $num_posts_index ?></span></td>
								</tr>
								<tr>
									<th scope="row">Using template</th>
									<td><span><?php echo $template_path ?></span></td>
								</tr>
								<tr>
									<th scope="row">Saving latest news to</th>
									<td><span><?php echo $output_dir_latest ?></span></td>
								</tr>
								<tr>
									<th scope="row">Saving archive to</th>
									<td><span><?php echo $output_dir_archive ?></span></td>
								</tr>

							</table>
							<div class="fsetsubmit"><input type="submit" name="gen_news" value="Generate news" tabindex="1" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
<?php

}
