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


// The contents of this file are very much inspired by the file search.php
// from the phpBB Group forum software phpBB2 (http://www.phpbb.com).


define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
// create SQL for multigroup mod
$mgrp_extra = multigrp_getSql($db);

// Load the search.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';
// Load the forum.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/forum.php';
// Load poll language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/polls.php';
// Load the post.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

require_once PUN_ROOT.'include/parser.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);
else if ($pun_user['g_search'] == '0')
	message($lang_search['No search permission']);


// Detect two byte character sets
$multibyte = isset($lang_common['lang_multibyte']) && $lang_common['lang_multibyte'] ? true : false;


// Figure out what to do :-)
if (isset($_GET['action']) || isset($_GET['search_id']))
{
	$action = isset($_GET['action']) ? $_GET['action'] : null;
	$forum = isset($_GET['forum']) && preg_match('%^[\d,]+$%', $_GET['forum']) ? $_GET['forum'] : '-1';
	$topic = isset($_GET['topic']) && preg_match('%^[\d,]+$%', $_GET['topic']) ? $_GET['topic'] : '-1';
	$sort_dir = isset($_GET['sort_dir']) ? ($_GET['sort_dir'] == 'DESC' ? 'DESC' : 'ASC') : 'DESC';
	$search_in = 0;
	if (isset($search_id)) unset($search_id);

	// If a search_id was supplied
	if (isset($_GET['search_id']))
	{
		$search_id = intval($_GET['search_id']);
		if ($search_id < 1)
			message($lang_common['Bad request']);
	}
	// If it's a regular search (keywords and/or author)
	else if ($action == 'search')
	{
		$keywords = isset($_GET['keywords']) ? pun_strtolower(trim($_GET['keywords'])) : '';
		$author = isset($_GET['author']) ? pun_strtolower(trim($_GET['author'])) : '';

		if (preg_match('%^[\*\%]+$%', $keywords) || (pun_strlen(str_replace(['*', '%'], '', $keywords)) < PUN_SEARCH_MIN_WORD))
			$keywords = '';

		if (preg_match('%^[\*\%]+$%', $author) || pun_strlen(str_replace(['*', '%'], '', $author)) < 2)
			$author = '';

		if (!$keywords && !$author)
			message($lang_search['No terms']);

		if ($author)
			$author = str_replace(['*', '_'], ['%', '\\_'], $author);

		$after = isset($_GET['after']) && preg_match('#^(\d){4}\-(\d){2}\-(\d){2}$#', $_GET['after']) ? strtotime($_GET['after']) : null;
		$before = isset($_GET['before']) && preg_match('#^(\d){4}\-(\d){2}\-(\d){2}$#', $_GET['before']) ? strtotime($_GET['before']) : null;

		$show_as = isset($_GET['show_as']) ? $_GET['show_as'] : 'posts';
		$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;
		$search_in = !isset($_GET['search_in']) || $_GET['search_in'] == 'all' ? 0 : ($_GET['search_in'] == 'message' ? 1 : -1);
	}
	// If it's a user search (by id)
	else if ($action == 'show_user')
	{
		$user_id = intval($_GET['user_id']);
		if ($user_id < 2)
			message($lang_common['Bad request']);
	}
	else
	{
		if ($action != 'show_new' && $action != 'show_24h' && $action != 'show_unanswered' && $action != 'show_active' && $action != 'show_subscriptions')
			message($lang_common['Bad request']);
	}


	// If a valid search_id was supplied we attempt to fetch the search results from the db
	if (isset($search_id))
	{
		$ident = $pun_user['is_guest'] ? get_remote_address() : $pun_user['username'];

		$result = $db->query('SELECT search_data FROM '.$db->prefix.'search_cache WHERE id='.$search_id.' AND ident=\''.$db->escape($ident).'\'') or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());
		if ($row = $db->fetch_assoc($result))
		{
			$temp = pun_unserialize($row['search_data']);

			$search_results = $temp['search_results'];
			$num_hits = $temp['num_hits'];
			$sort_by = $temp['sort_by'];
			$sort_dir = $temp['sort_dir'];
			$show_as = $temp['show_as'];
			$highlight = $temp['highlight'];
			$search_in = $temp['search_in'];

			unset($temp);
		}
		else
			message($lang_search['No hits']);
	}
	else
	{
		$keyword_results = $author_results = $keywords_array = [];

		// Search a specific forum?
		$forum_sql = $forum != -1 || ($forum == -1 && $pun_config['o_search_all_forums'] == '0' && $pun_user['g_id'] >= PUN_GUEST) ? ' AND t.forum_id IN('.$forum.')' : '';
		$topic_sql = ($topic != -1 ? ' AND t.id='.$topic : '');

		if (!empty($author) || !empty($keywords))
		{
			// If it's a search for keywords
			if ($keywords)
			{
				$stopwords = (array)@file(PUN_ROOT.'lang/'.$pun_user['language'].'/stopwords.txt');
				$stopwords = array_map('trim', $stopwords);

				// Are we searching for multibyte charset text?
				if ($multibyte)
				{
					// Strip out excessive whitespace
					$keywords = trim(preg_replace('#\s+#', ' ', $keywords));

					$keywords_array = explode(' ', $keywords);
				}
				else
				{
					// Filter out non-alphabetical chars
					$noise_match = ['^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '[', ']', '{', '}', ':', '\\', '/', '=', '#', '\'', ';', '!', '€'];
					$noise_replace = [' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',   ' ', ' ', ' ', ' ', '',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  ' ', ' ', ' ', ' ',  ' ', ' ', ' '];
					$keywords = str_replace($noise_match, $noise_replace, $keywords);

					// Strip out excessive whitespace
					$keywords = trim(preg_replace('#\s+#', ' ', $keywords));

					// Fill an array with all the words
					$keywords_array = explode(' ', $keywords);

					if (empty($keywords_array))
						message($lang_search['No hits']);

					foreach ($keywords_array as $i => $word)
					{
						$num_chars = pun_strlen($word);

						if ($word !== 'or' && ($num_chars < 3 || $num_chars > 20 || in_array($word, $stopwords)))
							unset($keywords_array[$i]);
					}

					// Should we search in message body or topic subject specifically?
					$search_in_cond = $search_in ? ($search_in > 0 ? ' AND m.subject_match = 0' : ' AND m.subject_match = 1') : '';
				}

				$word_count = 0;
				$match_type = 'and';
				$result_list = [];

				foreach ($keywords_array as $cur_word)
				{
					switch ($cur_word)
					{
						case 'and':
						case 'or':
						case 'not':
							$match_type = $cur_word;
							break;

						default:

							// Are we searching for multibyte charset text?
							if ($multibyte)
							{
								$cur_word = $db->escape('%'.str_replace('*', '', $cur_word).'%');
								$cur_word_like = $db_type == 'pgsql' ? 'ILIKE \''.$cur_word.'\'' : 'LIKE \''.$cur_word.'\'';

								if ($search_in > 0)
									$sql = 'SELECT id FROM '.$db->prefix.'posts WHERE message '.$cur_word_like;
								else if ($search_in < 0)
									$sql = 'SELECT p.id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id WHERE t.subject '.$cur_word_like.' GROUP BY p.id, t.id';
								else
									$sql = 'SELECT p.id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id WHERE p.message '.$cur_word_like.' OR t.subject '.$cur_word_like.' GROUP BY p.id, t.id';
							}
							else
							{
								$cur_word = $db->escape(str_replace('*', '%', $cur_word));
								$sql = 'SELECT m.post_id FROM '.$db->prefix.'search_words AS w INNER JOIN '.$db->prefix.'search_matches AS m ON m.word_id = w.id WHERE w.word LIKE \''.$cur_word.'\''.$search_in_cond;
							}

							$result = $db->query($sql, true) or error('Unable to search for posts', __FILE__, __LINE__, $db->error());

							$row = [];
							while ($temp = $db->fetch_row($result))
							{
								$row[$temp[0]] = 1;

								if (!$word_count)
									$result_list[$temp[0]] = 1;
								else if ($match_type == 'or')
									$result_list[$temp[0]] = 1;
								else if ($match_type == 'not')
									$result_list[$temp[0]] = 0;
							}

							if ($match_type == 'and' && $word_count)
							{
								foreach (array_keys($result_list) as $post_id)
								{
									if (!isset($row[$post_id]))
										$result_list[$post_id] = 0;
								}
							}

							++$word_count;
							$db->free_result($result);

							break;

					}
				}

				foreach ($result_list as $post_id => $matches)
				{
					if ($matches)
						$keyword_results[] = $post_id;
				}

				unset($result_list);
			}

			// If it's a search for author name (and that author name isn't Guest)
			if ($author && strcasecmp($author, 'Guest') && pun_strcasecmp($author, $lang_common['Guest']))
			{
				switch ($db_type)
				{
					case 'pgsql':
						$result = $db->query('SELECT id FROM '.$db->prefix.'users WHERE username ILIKE \''.$db->escape($author).'\'') or error('Unable to fetch users', __FILE__, __LINE__, $db->error());
						break;

					default:
						$result = $db->query('SELECT id FROM '.$db->prefix.'users WHERE username LIKE \''.$db->escape($author).'\'') or error('Unable to fetch users', __FILE__, __LINE__, $db->error());
						break;
				}

				if ($db->num_rows($result))
				{
					$user_ids = '';
					while ($row = $db->fetch_row($result))
						$user_ids .= ($user_ids != '' ? ',' : '').$row[0];

					$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE poster_id IN('.$user_ids.')') or error('Unable to fetch matched posts list', __FILE__, __LINE__, $db->error());

					$search_ids = [];
					while ($row = $db->fetch_row($result))
						$author_results[] = $row[0];

					$db->free_result($result);
				}
			}


			if ($author && $keywords)
			{
				// If we searched for both keywords and author name we want the intersection between the results
				$search_ids = array_intersect($keyword_results, $author_results);
				unset($keyword_results, $author_results);
			}
			else if ($keywords)
				$search_ids = $keyword_results;
			else
				$search_ids = $author_results;

			$num_hits = count($search_ids);
			if (!$num_hits)
				message($lang_search['No hits']);


			if ($show_as == 'topics')
			{
				$result = $db->query('SELECT t.id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND p.id IN('.implode(',', $search_ids).')'.$forum_sql.
				($after ? ' AND t.posted > ' . $after : '') . ($before ? ' AND t.posted < ' . $before : '') .
				' GROUP BY t.id', true) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$search_ids = [];
				while ($row = $db->fetch_row($result))
					$search_ids[] = $row[0];

				$db->free_result($result);

				$num_hits = count($search_ids);
			}
			else
			{
				$result = $db->query('SELECT p.id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND p.id IN('.implode(',', $search_ids).')'.$forum_sql.$topic_sql.
				($after ? ' AND p.posted > ' . $after : '') . ($before ? ' AND p.posted < ' . $before : '')
				, true) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$search_ids = [];
				while ($row = $db->fetch_row($result))
					$search_ids[] = $row[0];

				$db->free_result($result);

				$num_hits = count($search_ids);
			}
		}
		else if ($action == 'show_new' || $action == 'show_24h' || $action == 'show_user' || $action == 'show_subscriptions' || $action == 'show_active' || $action == 'show_unanswered')
		{
			// If it's a search for new posts
			if ($action == 'show_new')
			{
				if ($pun_user['is_guest'])
					message($lang_common['No permission']);

				$result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND t.last_post>'.$pun_user['last_visit'].' AND t.moved_to IS NULL') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No new posts']);
			}
			// If it's a search for todays posts
			else if ($action == 'show_24h')
			{
				$result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND t.last_post>'.(time() - 86400).' AND t.moved_to IS NULL') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No recent posts']);
			}
			// If it's a search for posts by a specific user ID
			else if ($action == 'show_user')
			{
				$show_as = 'posts';
				$sort_by = 9;

				$result = $db->query('SELECT p.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'posts AS p ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND p.poster_id='.$user_id) or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No user posts']);
			}
			// If it's a search for subscribed topics
			else if ($action == 'show_subscriptions')
			{
				if ($pun_user['is_guest'])
					message($lang_common['Bad request']);

				$result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.'') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No subscriptions']);
			}
			// Show recent discussions - from extern.php
			else if ($action == 'show_active')
			{
				$order_by = 't.last_post';
				$forum_sql = '';

				// Fetch 15 topics
				$result = $db->query('SELECT t.id, t.poster, t.subject, t.posted, t.last_post, f.id AS fid, f.forum_name FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=3) WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL'.$forum_sql.' ORDER BY '.$order_by.' DESC LIMIT 15') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No user posts']);
			}
			// If it's a search for unanswered posts
			else
			{
				$result = $db->query('SELECT t.id FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND t.num_replies=0 AND t.moved_to IS NULL') or error('Unable to fetch topic list', __FILE__, __LINE__, $db->error());
				$num_hits = $db->num_rows($result);

				if (!$num_hits)
					message($lang_search['No unanswered']);
			}

			// We want to sort things after last post
			if(empty($sort_by))
				$sort_by = 4;

			$search_ids = [];
			while ($row = $db->fetch_row($result))
				$search_ids[] = $row[0];

			$db->free_result($result);

			if(empty($show_as))
				$show_as = 'topics';
		}
		else
			message($lang_common['Bad request']);


		// Prune "old" search results
		$old_searches = [];
		$result = $db->query('SELECT ident FROM '.$db->prefix.'online') or error('Unable to fetch online list', __FILE__, __LINE__, $db->error());

		if ($db->num_rows($result))
		{
			while ($row = $db->fetch_row($result))
				$old_searches[] = '\''.$db->escape($row[0]).'\'';

			$db->query('DELETE FROM '.$db->prefix.'search_cache WHERE ident NOT IN('.implode(',', $old_searches).')') or error('Unable to delete search results', __FILE__, __LINE__, $db->error());
		}

		// Final search results
		$search_results = implode(',', $search_ids);

		// Fill an array with our results and search properties
		$temp['search_results'] = $search_results;
		$temp['num_hits'] = $num_hits;
		$temp['sort_by'] = $sort_by;
		$temp['sort_dir'] = $sort_dir;
		$temp['show_as'] = $show_as;
		$temp['highlight'] = $keywords_array;
		$temp['search_in'] = $search_in;
		$temp = serialize($temp);
		$search_id = mt_rand(1, 2147483647);

		$ident = $pun_user['is_guest'] ? get_remote_address() : $pun_user['username'];

		$db->query('INSERT INTO '.$db->prefix.'search_cache (id, ident, search_data) VALUES('.$search_id.', \''.$db->escape($ident).'\', \''.$db->escape($temp).'\')') or error('Unable to insert search results', __FILE__, __LINE__, $db->error());

		if ($action != 'show_new' && $action != 'show_24h')
		{
			// Redirect the user to the cached result page
			hidden_redirect('search.php?search_id='.$search_id);

			$db->end_transaction();
			$db->close();

			exit;
		}
	}


	// Fetch results to display
	if ($search_results != '')
	{
		switch ($sort_by)
		{
			case 1:
				$sort_by_sql = $show_as == 'topics' ? 't.poster' : 'p.poster';
				break;

			case 2:
				$sort_by_sql = 't.subject';
				break;

			case 3:
				$sort_by_sql = 't.forum_id';
				break;

			case 4:
				$sort_by_sql = 't.last_post';
				break;

			default:
				$sort_by_sql = $show_as == 'topics' ? 't.posted' : 'p.posted';
				break;
		}

		if ($show_as == 'posts')
		{
			$substr_sql = $db_type != 'sqlite' ? 'SUBSTRING' : 'SUBSTR';
			$sql = 'SELECT p.id AS pid, p.poster AS pposter, p.posted AS pposted, p.poster_id, '.$substr_sql.'(p.message, 1, 1000) AS message, t.id AS tid, t.poster, t.subject, t.question, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.forum_id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id WHERE p.id IN('.$search_results.') ORDER BY '.$sort_by_sql;
		}
		else
			$sql = 'SELECT t.id AS tid, t.poster, t.subject, t.question, t.last_post, t.last_post_id, t.last_poster, t.num_replies, t.closed, t.forum_id, t.num_views FROM '.$db->prefix.'topics AS t WHERE t.id IN('.$search_results.') ORDER BY '.$sort_by_sql;


		// Determine the topic or post offset (based on $_GET['p'])
		$per_page = $show_as == 'posts' ? $pun_user['disp_posts'] : $pun_user['disp_topics'];
		$num_pages = ceil($num_hits / $per_page);

		$p = !isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages ? 1 : $_GET['p'];
		$start_from = $per_page * ($p - 1);

		// Generate paging links
		$paging_links = $lang_common['Pages'].': '.paginate($num_pages, $p, 'search.php?search_id='.$search_id);


		$sql .= ' '.$sort_dir.' LIMIT '.$start_from.', '.$per_page;

		$result = $db->query($sql) or error('Unable to fetch search results', __FILE__, __LINE__, $db->error());

		$search_set = [];
		while ($row = $db->fetch_assoc($result))
			$search_set[] = $row;

		$db->free_result($result);

		$page_title = pun_htmlspecialchars($lang_search['Search results']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
		require PUN_ROOT.'header.php';


?>
<div class="linkst">
	<div class="inbox">
		<p class="postlink conr"><?php echo $paging_links ?></p>
		<div class="clearer"></div>
	</div>
</div>

<?php

		//Set background switching on for show as posts
		$bg_switch = true;

		if ($show_as == 'topics')
		{

?>
<div id="vf" class="blocktable">
	<h2><span><?php echo $lang_search['Search results']; ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_common['Topic']; ?></th>
					<th class="tc2" scope="col"><?php echo $lang_common['Forum'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_common['Replies'] ?></th>
					<th class="tc4" scope="col"><?php echo $lang_forum['Views'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_common['Last post'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php

		}

		// Finally, lets loop through the results and output them
		for ($i = 0; $i < count($search_set); ++$i)
		{
			foreach ($pun_forums as $temp)
			{
				if ($temp[0] == $search_set[$i]['forum_id'])
					$forum = '<a href="viewforum.php?id='.$temp[0].'">'.pun_htmlspecialchars($temp[1]).'</a>';
			}

			if ($pun_config['o_censoring'] == '1')
				$search_set[$i]['subject'] = censor_words($search_set[$i]['subject']);


			if ($show_as == 'posts')
			{
				$icon = '<div class="icon"><div class="nosize">'.$lang_common['Normal icon'].'</div></div>'."\n";

				$subject_highlight = pun_htmlspecialchars($search_set[$i]['subject']);
				if (!empty($highlight) && $search_in != 1)
				{
					foreach ($highlight as $highword)
						if ($highword != 'and' && $highword != 'or' && $highword != 'not')
								$subject_highlight = preg_replace('#('.str_replace('*', '[^\s]+', str_replace('#', '\#', $highword)).')#i', '<span style="background-color: #FFFF00; color: #000000">$1</span>', $subject_highlight);
				}

				$subject = ($search_set[$i]['question'] != '' ? '<b>'.$lang_polls['Poll'].'</b>: ' : '').'<a href="viewtopic.php?id='.$search_set[$i]['tid'].'">'.$subject_highlight.'</a>';
				if (!$pun_user['is_guest'] && $search_set[$i]['last_post'] > $pun_user['last_visit'])
					$icon = '<div class="icon inew"><div class="nosize">'.$lang_common['New icon'].'</div></div>'."\n";


				if ($pun_config['o_censoring'] == '1')
					$search_set[$i]['message'] = censor_words($search_set[$i]['message']);

				$message = $search_set[$i]['message'];
				if (!empty($highlight) && $search_in != -1)
				{
					foreach ($highlight as $highword)
						if ($highword != 'and' && $highword != 'or' && $highword != 'not')
								$message = preg_replace('#('.str_replace('*', '[^\s]+', str_replace('#', '\#', $highword)).')#i', '[h]$1[/h]', $message);
				}
				$message = parse_message($message, 1);
				$pposter = pun_htmlspecialchars($search_set[$i]['pposter']);

				if ($search_set[$i]['poster_id'] > 1)
					$pposter = '<strong><a href="profile.php?id='.$search_set[$i]['poster_id'].'">'.$pposter.'</a></strong>';

				if (pun_strlen($message) >= 1000)
					$message .= ' &hellip;';

				$vtpost1 = $i == 0 ? ' vtp1' : '';

				// Switch the background color for every message.
				$bg_switch = $bg_switch ? $bg_switch = false : $bg_switch = true;
				$vtbg = $bg_switch ? ' rowodd' : ' roweven';


?>
<div class="blockpost searchposts<?php echo $vtbg ?>">
	<h2><span><?php echo $forum ?>&nbsp;&raquo;&nbsp;<?php echo $subject ?>&nbsp;&raquo;&nbsp;<a href="viewtopic.php?pid=<?php echo $search_set[$i]['pid'].'#p'.$search_set[$i]['pid'] ?>"><?php echo format_time($search_set[$i]['pposted']) ?></a></span></h2>
	<div class="box">
		<div class="inbox">
			<div class="postleft">
				<dl>
					<dt><?php echo $pposter ?></dt>
					<dd>Replies: <?php echo $search_set[$i]['num_replies'] ?></dd>
					<dd><?php echo $icon; ?></dd>
					<dd><p class="clearb">
						<a href="viewtopic.php?pid=<?php echo $search_set[$i]['pid'].'#p'.$search_set[$i]['pid'] ?>"><?php echo $lang_search['Go to post'] ?></a><br/>
<?php if ($pun_user['g_id'] <= PUN_MOD): ?>
						<a href="edit.php?id=<?php echo $search_set[$i]['pid'] ?>"><?php echo $lang_search['Edit'] ?></a><br/>
						<a href="delete.php?id=<?php echo $search_set[$i]['pid'] ?>"><?php echo $lang_search['Delete'] ?></a>
<?php endif; ?>
					</p></dd>
				</dl>
			</div>
			<div class="postright">
				<div class="postmsg">
					<p><?php echo $message ?></p>
				</div>
			</div>
			<div class="clearer"></div>
		</div>
	</div>
</div>
<?php

			}
			else
			{
				$icon = '<div class="icon"><div class="nosize">'.$lang_common['Normal icon'].'</div></div>'."\n";

				$icon_text = $lang_common['Normal icon'];
				$item_status = '';
				$icon_type = 'icon';

				if ($search_set[$i]['question'] == "")
					$subject = '<a href="viewtopic.php?id='.$search_set[$i]['tid'].'">'.iconize_topic(pun_htmlspecialchars($search_set[$i]['subject']), $search_set[$i]['forum_id']).'</a> <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($search_set[$i]['poster']).'</span>';
				else
					$subject = '<b>'.$lang_polls['Poll'].'</b>: <a href="viewtopic.php?id='.$search_set[$i]['tid'].'"><b>'.iconize_topic(pun_htmlspecialchars($search_set[$i]['question']), $search_set[$i]['forum_id']).'</b><BR>'.pun_htmlspecialchars($search_set[$i]['subject']).'</a> <span class="byuser">'.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($search_set[$i]['poster']).'</span>';
				if ($search_set[$i]['closed'] != '0')
				{
					$icon_text = $lang_common['Closed icon'];
					$item_status = 'iclosed';
				}

				// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
				if (!$pun_user['is_guest'] && topic_is_new($search_set[$i]['tid'], $search_set[$i]['forum_id'],  $search_set[$i]['last_post']))
				{
					$icon_text .= ' '.$lang_common['New icon'];
					$item_status .= ' inew';
					$icon_type = 'icon inew';
					$subject = '<strong>'.$subject.'</strong>';
					$subject_new_posts = '<span class="newtext">[&nbsp;<a href="viewtopic.php?id='.$search_set[$i]['tid'].'&amp;action=new" title="'.$lang_common['New posts info'].'">'.$lang_common['New posts'].'</a>&nbsp;]</span>';
				}
				else
					$subject_new_posts = null;

				if ($search_set[$i]['question'])
					$item_status .= ' ipool';
				if ($search_set[$i]['num_replies'] > 50)
					$item_status .= ' ihot';
				if ($search_set[$i]['num_replies'] == 0)
					$item_status .= ' iempty';

				$num_pages_topic = ceil(($search_set[$i]['num_replies'] + 1) / $pun_user['disp_posts']);

				if ($num_pages_topic > 1)
				{
					if ($search_set[$i]['question'] == "")
						$subject_multipage = '[&nbsp;'.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$search_set[$i]['tid']).'&nbsp;]';
					else
						$subject_multipage = '[&nbsp;'.paginate($num_pages_topic, -1, 'viewtopic.php?id='.$search_set[$i]['tid']).'&nbsp;]';
				}
				else
					$subject_multipage = null;

				// Should we show the "New posts" and/or the multipage links?
				if (!empty($subject_new_posts) || !empty($subject_multipage))
				{
					$subject .= '&nbsp; '.(!empty($subject_new_posts) ? $subject_new_posts : '');
					$subject .= !empty($subject_multipage) ? ' '.$subject_multipage : '';
				}

?>
				<tr<?php if ($item_status != '') echo ' class="'.trim($item_status).'"'; ?>>
					<td class="tcl">
						<div class="intd">
							<div class="<?php echo $icon_type ?>"><div class="nosize"><?php echo trim($icon_text) ?></div></div>
							<div class="tclcon">
								<?php echo $subject."\n" ?>
							</div>
						</div>
					</td>
					<td class="tc2"><?php echo $forum ?></td>
					<td class="tc3"><?php echo $search_set[$i]['num_replies'] ?></td>
					<td class="tc4"><?php echo $search_set[$i]['num_views'] ?></td>
					<?php
					if ($search_set[$i]['question'] == "")
					{
						?><td class="tcr"><?php echo '<a href="viewtopic.php?pid='.$search_set[$i]['last_post_id'].'#p'.$search_set[$i]['last_post_id'].'" class="last">'.format_time($search_set[$i]['last_post']).'</a> '.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($search_set[$i]['last_poster']) ?></td><?php
					}
					else
					{
						?><td class="tcr"><?php echo '<a href="viewtopic.php?pid='.$search_set[$i]['last_post_id'].'#p'.$search_set[$i]['last_post_id'].'" class="last">'.format_time($search_set[$i]['last_post']).'</a> '.$lang_common['by'].'&nbsp;'.pun_htmlspecialchars($search_set[$i]['last_poster']) ?></td></tr><?php
					} ?>
				</tr>
		<?php

			}
		}

		if ($show_as == 'topics')
			echo "\t\t\t".'</tbody>'."\n\t\t\t".'</table>'."\n\t\t".'</div>'."\n\t".'</div>'."\n".'</div>'."\n\n";

?>
<div class="<?php echo $show_as == 'topics' ? 'linksb' : 'postlinksb'; ?>">
	<div class="inbox">
		<p class="postlink conr"><?php echo $paging_links ?></p>
		<div class="clearer"></div>
	</div>
</div>
<?php

		$footer_style = 'search';
		require PUN_ROOT.'footer.php';
	}
	else
		message($lang_search['No hits']);
}


$page_title = pun_htmlspecialchars($lang_search['Search']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
$focus_element = ['search', 'keywords'];
require PUN_ROOT.'header.php';

?>
<div id="google" class="blockform">
	<h2><span><?php echo $lang_common['Google search'] ?></span></h2>
	<div class="box">

		<div id="cse"><div style="padding:10px"><?php echo $lang_common['Google loading'] ?></div></div>
		<script src="https://www.google.com/jsapi" type="text/javascript"></script>
		<script type="text/javascript">
			google.load('search', '1', {language : '<?php echo $pun_user['language'] == 'Russian' ? 'ru' : 'en' ?>'});
			google.setOnLoadCallback(function() {
				var customSearchControl = new google.search.CustomSearchControl('002000707098631292328:agyedqlqcg0');
				customSearchControl.setResultSetSize(google.search.Search.FILTERED_CSE_RESULTSET);
				customSearchControl.draw('cse');
<?php if(!empty($_GET['google']))
	echo '				customSearchControl.execute(\'' . addslashes($_GET['google']) . '\');';
?>

			}, true);
		</script>

	</div>
</div>

<div id="searchform" class="blockform">
	<h2><span><?php echo $lang_search['Extended search'] ?></span></h2>
	<div class="box">
		<form id="search" method="get" action="search.php">
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_search['Search criteria legend'] ?></legend>
					<div class="infldset">
						<input type="hidden" name="action" value="search" />
						<label class="conl"><?php echo $lang_search['Keyword search'] ?><br /><input type="text" name="keywords" size="40" maxlength="100" /><br /></label>
						<label class="conl"><?php echo $lang_search['Author search'] ?><br /><input id="author" type="text" name="author" size="25" maxlength="25" /><br /></label>
<?php if(!$pun_user['is_guest']): ?>
						<label class="conl"><br /><input type="button" value="<?php echo $lang_search['My posts'] ?>" onclick='document.getElementById("author").value="<?php echo pun_htmlspecialchars($pun_user['username']); ?>"' /><br /></label>
<?php endif; ?>
						<p class="clearb"><?php echo $lang_search['Search info'] ?></p>
						<label class="conl"><?php echo $lang_search['Date after'] ?><br /><input id="after" type="text" name="after" size="18" maxlength="10" /><br /></label>
						<label class="conl"><?php echo $lang_search['Date before'] ?><br /><input id="before" type="text" name="before" size="18" maxlength="10" /><br /></label>
						<label class="conl"><br /><input type="button" value="<?php echo $lang_search['Last week'] ?>" onclick="document.getElementById('after').value='<?php echo date('Y-m-d', strtotime('-1 week')); ?>';document.getElementById('before').value='';" /><br /></label>
						<label class="conl"><br /><input type="button" value="<?php echo $lang_search['Last month'] ?>" onclick="document.getElementById('after').value='<?php echo date('Y-m-d', strtotime('-1 month')); ?>';document.getElementById('before').value='';" /><br /></label>
						<p class="clearb"><?php echo $lang_search['Date info'] ?></p>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_search['Search in legend'] ?></legend>
					<div class="infldset">
						<label class="conl"><?php echo $lang_search['Forum search'] ?>
						<br /><select id="forum" name="forum">
<?php

if ($pun_config['o_search_all_forums'] == '1' || $pun_user['g_id'] < PUN_GUEST)
	echo "\t\t\t\t\t\t\t".'<option value="-1">'.$lang_search['All forums'].'</option>'."\n";

	$result = $db->query('SELECT c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url FROM '.$db->prefix.'categories AS c INNER JOIN '.$db->prefix.'forums AS f ON c.id=f.cat_id '.$mgrp_extra.' AND f.redirect_url IS NULL ORDER BY c.disp_position, c.id, f.disp_position', true) or error('Unable to fetch category/forum list', __FILE__, __LINE__, $db->error());
$cur_category = 0;
$categories =  ['', '', ''];
while ($cur_forum = $db->fetch_assoc($result))
{
	if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
	{
		if ($cur_category)
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$categories[0].'">'.$categories[1].'</option>'."\n".$categories[2];
			$categories =  ['', '', ''];
	}

		$categories[1] = pun_htmlspecialchars($cur_forum['cat_name']);
		$cur_category = $cur_forum['cid'];
	}

	$categories[2] .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'"> &nbsp; &nbsp; '.pun_htmlspecialchars($cur_forum['forum_name']).'</option>'."\n";
	$categories[0] .= $categories[0] ? ','.$cur_forum['fid'] : $cur_forum['fid'];
}
echo "\t\t\t\t\t\t\t".'<option value="'.$categories[0].'">'.$categories[1].'</option>'."\n".$categories[2];

?>
						</select>
						<br /></label>
						<label class="conl"><?php echo $lang_search['Search in'] ?>
						<br /><select id="search_in" name="search_in">
							<option value="all"><?php echo $lang_search['Message and subject'] ?></option>
							<option value="message"><?php echo $lang_search['Message only'] ?></option>
							<option value="topic"><?php echo $lang_search['Topic only'] ?></option>
						</select>
						<br /></label>
						<p class="clearb"><?php echo $lang_search['Search in info'] ?></p>
					</div>
				</fieldset>
			</div>
			<div class="inform">
				<fieldset>
					<legend><?php echo $lang_search['Search results legend'] ?></legend>
					<div class="infldset">
						<label class="conl"><?php echo $lang_search['Sort by'] ?>
						<br /><select name="sort_by">
							<option value="0"><?php echo $lang_search['Sort by post time'] ?></option>
							<option value="1"><?php echo $lang_search['Sort by author'] ?></option>
							<option value="2"><?php echo $lang_search['Sort by subject'] ?></option>
							<option value="3"><?php echo $lang_search['Sort by forum'] ?></option>
							<option value="4"><?php echo $lang_search['Sort by last post'] ?></option>
						</select>
						<br /></label>
						<label class="conl"><?php echo $lang_search['Sort order'] ?>
						<br /><select name="sort_dir">
							<option value="DESC"><?php echo $lang_search['Descending'] ?></option>
							<option value="ASC"><?php echo $lang_search['Ascending'] ?></option>
						</select>
						<br /></label>
						<label class="conl"><?php echo $lang_search['Show as'] ?>
						<br /><select name="show_as">
							<option value="posts"><?php echo $lang_search['Show as posts'] ?></option>
							<option value="topics"><?php echo $lang_search['Show as topics'] ?></option>
						</select>
						<br /></label>
						<p class="clearb"><?php echo $lang_search['Search results info'] ?></p>
					</div>
				</fieldset>
			</div>
			<p><input type="submit" name="search" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /></p>
		</form>
	</div>
</div>
<?php

require PUN_ROOT.'footer.php';
