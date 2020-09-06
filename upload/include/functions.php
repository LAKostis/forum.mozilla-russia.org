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

//
// Cookie stuff!
//
function check_cookie(&$pun_user)
{
	global $db, $db_type, $pun_config, $cookie_name, $cookie_path, $cookie_domain, $cookie_seed;

	$now = time();
	$expire = $now + 31536000;	// The cookie expires after a year

	// We assume it's a guest
	$cookie = ['user_id' => 1, 'password_hash' => 'Guest'];

	// If a cookie is set, we get the user_id and password hash from it
	if (isset($_COOKIE[$cookie_name]))
		list($cookie['user_id'], $cookie['password_hash']) = @unserialize($_COOKIE[$cookie_name]);

	if ($cookie['user_id'] > 1)
	{
		// Check if there's a user with the user ID and password hash from the cookie
		$result = $db->query('SELECT u.*, g.*, o.logged, o.idle FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$db->prefix.'online AS o ON o.user_id=u.id WHERE u.id='.intval($cookie['user_id'])) or error('Unable to fetch user information', __FILE__, __LINE__, $db->error());
		$pun_user = $db->fetch_assoc($result);

		// If user authorisation failed
		if (!isset($pun_user['id']) || md5($cookie_seed.$pun_user['password']) !== $cookie['password_hash'])
		{
			pun_setcookie(1, md5(uniqid((string)rand(), true)), $expire);
			set_default_user();

			return;
		}

		// Set a default style if the user selected style no longer exists
		if (!@file_exists(PUN_ROOT.'style/'.$pun_user['style'].'.css'))
			$pun_user['style'] = $pun_config['o_default_style'];

		if (!$pun_user['disp_topics'])
			$pun_user['disp_topics'] = $pun_config['o_disp_topics_default'];
		if (!$pun_user['disp_posts'])
			$pun_user['disp_posts'] = $pun_config['o_disp_posts_default'];

		if ($pun_user['save_pass'] == '0')
			$expire = 0;

		// MOD: MARK TOPICS AS READ - 4 LINES NEW CODE FOLLOW
			if ($pun_user['read_topics'])
			$pun_user['read_topics'] = unserialize($pun_user['read_topics']);
		else
			$pun_user['read_topics'] = [];

		// Define this if you want this visit to affect the online list and the users last visit data
		if (!defined('PUN_QUIET_VISIT'))
		{
			// Update the online list
			if (!$pun_user['logged'])
			{
				$show_online = $pun_user['show_online'];
				$pun_user['logged'] = $now;

				// With MySQL/MySQLi, REPLACE INTO avoids a user having two rows in the online table
				switch ($db_type)
				{
					case 'mysql':
					case 'mysqli':
						$db->query('REPLACE INTO '.$db->prefix.'online (user_id, ident, logged, show_online) VALUES('.$pun_user['id'].', \''.$db->escape($pun_user['username']).'\', '.$pun_user['logged'].', \''.$db->escape($show_online).'\')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
						break;

					default:
						$db->query('INSERT INTO '.$db->prefix.'online (user_id, ident, logged, show_online) VALUES('.$pun_user['id'].', \''.$db->escape($pun_user['username']).'\', '.$pun_user['logged'].', \''.$db->escape($show_online).'\')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
						break;
				}
			}
			else
			{
				// Special case: We've timed out, but no other user has browsed the forums since we timed out
				if ($pun_user['logged'] < $now-$pun_config['o_timeout_visit'])
				{
					// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
					$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$pun_user['logged'].', read_topics=NULL WHERE id='.$pun_user['id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
					$pun_user['last_visit'] = $pun_user['logged'];
				}

				$idle_sql = $pun_user['idle'] == '1' ? ', idle=0' : '';
				$db->query('UPDATE '.$db->prefix.'online SET logged='.$now.$idle_sql.' WHERE user_id='.$pun_user['id']) or error('Unable to update online list', __FILE__, __LINE__, $db->error());
			}
		}

		$pun_user['is_guest'] = false;
	}
	else
		set_default_user();

	if (!empty($_GET['language']))
	{
		$_COOKIE['language'] = $_GET['language'];
		if ($cookie['user_id'] == 1) // Save cookie for guests
			setcookie('language', $_COOKIE['language'], time() + 31536000, $cookie_path, $cookie_domain);
	}

	if (!empty($_COOKIE['language']))
	{
		$pun_user['language'] = $_COOKIE['language'];
		if ($cookie['user_id'] != 1) // Remove cookie for logined users
			setcookie('language');
	}

	// Set a default language if the user selected language no longer exists
	if (!@file_exists(PUN_ROOT.'lang/'.$pun_user['language']))
		$pun_user['language'] = $pun_config['o_default_lang'];

	// Update config for logined users
	elseif ($cookie['user_id'] != 1 && (!empty($_GET['language']) || !empty($_COOKIE['language'])))
		$db->query('UPDATE '.$db->prefix.'users SET language=\''.$db->escape($pun_user['language']).'\' WHERE id='.$pun_user['id']) or error('Unable to update user language', __FILE__, __LINE__, $db->error());

}


//
// Fill $pun_user with default values (for guests)
//
function set_default_user()
{
	global $db, $db_type, $pun_user, $pun_config;

	$remote_addr = get_remote_address();

	// Fetch guest user
	$result = $db->query('SELECT u.*, g.*, o.logged FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$db->prefix.'online AS o ON o.ident=\''.$remote_addr.'\' WHERE u.id=1') or error('Unable to fetch guest information', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		exit('Unable to fetch guest information. The table \''.$db->prefix.'users\' must contain an entry with id = 1 that represents anonymous users.');

	$pun_user = $db->fetch_assoc($result);

	// Update online list
	if (!$pun_user['logged'])
	{
		$pun_user['logged'] = time();

		// With MySQL/MySQLi, REPLACE INTO avoids a user having two rows in the online table
		switch ($db_type)
		{
			case 'mysql':
			case 'mysqli':
				$db->query('REPLACE INTO '.$db->prefix.'online (user_id, ident, logged) VALUES(1, \''.$db->escape($remote_addr).'\', '.$pun_user['logged'].')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
				break;

			default:
				$db->query('INSERT INTO '.$db->prefix.'online (user_id, ident, logged) VALUES(1, \''.$db->escape($remote_addr).'\', '.$pun_user['logged'].')') or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
				break;
		}
	}
	else
		$db->query('UPDATE '.$db->prefix.'online SET logged='.time().' WHERE ident=\''.$db->escape($remote_addr).'\'') or error('Unable to update online list', __FILE__, __LINE__, $db->error());

	$pun_user['disp_topics'] = $pun_config['o_disp_topics_default'];
	$pun_user['disp_posts'] = $pun_config['o_disp_posts_default'];
	$pun_user['timezone'] = $pun_config['o_server_timezone'];
	$pun_user['language'] = $pun_config['o_default_lang'];
	$pun_user['style'] = $pun_config['o_default_style'];
	$pun_user['is_guest'] = true;
}


//
// Set a cookie, PunBB style!
//
function pun_setcookie($user_id, $password_hash, $expire)
{
	global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $cookie_seed;

	// Enable sending of a P3P header by removing // from the following line (try this if login is failing in IE6)
	@header('P3P: CP="CUR ADM"');

	if (version_compare(PHP_VERSION, '5.2.0', '>='))
		setcookie($cookie_name, serialize([$user_id, md5($cookie_seed.$password_hash)]), $expire, $cookie_path, $cookie_domain, (bool)$cookie_secure, true);
	else
		setcookie($cookie_name, serialize([$user_id, md5($cookie_seed.$password_hash)]), $expire, $cookie_path.'; HttpOnly', $cookie_domain, $cookie_secure);
}


//
// Check whether the connecting user is banned (and delete any expired bans while we're at it)
//
function check_bans()
{
	global $db, $pun_config, $lang_common, $pun_user, $pun_bans;

	// Admins aren't affected
	if ($pun_user['g_id'] == PUN_ADMIN || !$pun_bans)
		return;

	// Add a dot at the end of the IP address to prevent banned address 192.168.0.5 from matching e.g. 192.168.0.50
	$user_ip = get_remote_address().'.';
	$bans_altered = false;

	foreach ($pun_bans as $cur_ban)
	{
		// Has this ban expired?
		if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
		{
			$db->query('DELETE FROM '.$db->prefix.'bans WHERE id='.$cur_ban['id']) or error('Unable to delete expired ban', __FILE__, __LINE__, $db->error());
			$bans_altered = true;
			continue;
		}

		if ($cur_ban['username'] != '' && !pun_strcasecmp($pun_user['username'], $cur_ban['username']))
		{
			$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.$db->escape($pun_user['username']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
			message($lang_common['Ban message'].' '.($cur_ban['expire'] != '' ? $lang_common['Ban message 2'].' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').($cur_ban['message'] != '' ? $lang_common['Ban message 3'].'<br /><br /><strong>'.pun_htmlspecialchars($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$lang_common['Ban message 4'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.', true);
		}

		if ($cur_ban['ip'] != '')
		{
			$cur_ban_ips = explode(' ', $cur_ban['ip']);

			for ($i = 0; $i < count($cur_ban_ips); ++$i)
			{
				$cur_ban_ips[$i] .='.';

				if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
				{
					$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.$db->escape($pun_user['username']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
					message($lang_common['Ban message'].' '.($cur_ban['expire'] != '' ? $lang_common['Ban message 2'].' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').($cur_ban['message'] != '' ? $lang_common['Ban message 3'].'<br /><br /><strong>'.pun_htmlspecialchars($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$lang_common['Ban message 4'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.', true);
				}
			}
		}
	}

	// If we removed any expired bans during our run-through, we need to regenerate the bans cache
	if ($bans_altered)
	{
		require_once PUN_ROOT.'include/cache.php';
		generate_bans_cache();
	}
}


//
// Update "Users online"
//
function update_users_online()
{
	global $db, $pun_config, $pun_user;

	$now = time();

	// Fetch all online list entries that are older than "o_timeout_online"
	$result = $db->query('SELECT * FROM '.$db->prefix.'online WHERE logged<'.($now-$pun_config['o_timeout_online']).' AND show_online = 1') or error('Unable to fetch old entries from online list', __FILE__, __LINE__, $db->error());
	while ($cur_user = $db->fetch_assoc($result))
	{
		// If the entry is a guest, delete it
		if ($cur_user['user_id'] == '1')
			$db->query('DELETE FROM '.$db->prefix.'online WHERE ident=\''.$db->escape($cur_user['ident']).'\'') or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
		else
		{
			// If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
			if ($cur_user['logged'] < $now-$pun_config['o_timeout_visit'])
			{
				// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
				$db->query('UPDATE '.$db->prefix.'users SET last_visit='.$cur_user['logged'].', read_topics=NULL WHERE id='.$cur_user['user_id']) or error('Unable to update user visit data', __FILE__, __LINE__, $db->error());
				$db->query('DELETE FROM '.$db->prefix.'online WHERE user_id='.$cur_user['user_id']) or error('Unable to delete from online list', __FILE__, __LINE__, $db->error());
			}
			else if ($cur_user['idle'] == '0')
				$db->query('UPDATE '.$db->prefix.'online SET idle=1 WHERE user_id='.$cur_user['user_id']) or error('Unable to insert into online list', __FILE__, __LINE__, $db->error());
		}
	}
}


//
// Generate the "navigator" that appears at the top of every page
//
function generate_navlinks()
{
	global $pun_config, $lang_common, $pun_user;

	// Index and Userlist should always be displayed
	$links[] = '<li id="navindex"><a href="index.php">'.$lang_common['Index'].'</a>';
	$links[] = '<li id="navuserlist"><a href="userlist.php">'.$lang_common['User list'].'</a>';

	if ($pun_config['o_rules'] == '1')
		$links[] = '<li id="navrules"><a href="misc.php?action=rules">'.$lang_common['Rules'].'</a>';

	if ($pun_user['is_guest'])
	{
		if ($pun_user['g_search'] == '1')
			$links[] = '<li id="navsearch"><a href="search.php">'.$lang_common['Search'].'</a>';

		$links[] = '<li id="navregister"><a href="register.php">'.$lang_common['Register'].'</a>';
		$links[] = '<li id="navlogin"><a href="login.php">'.$lang_common['Login'].'</a>';

		$info = $lang_common['Not logged in'];
	}
	else
	{
		if ($pun_user['g_id'] > PUN_MOD)
		{
			if ($pun_user['g_search'] == '1')
				$links[] = '<li id="navsearch"><a href="search.php">'.$lang_common['Search'].'</a>';

			$links[] = '<li id="navprofile"><a href="profile.php?id='.$pun_user['id'].'">'.$lang_common['Profile'].'</a>';
			require(PUN_ROOT.'include/pms/functions_navlinks.php');
			$links[] = '<li id="navlogout"><a href="login.php?action=out&amp;id='.$pun_user['id'].'&amp;csrf_token='.sha1($pun_user['id'].sha1(get_remote_address())).'">'.$lang_common['Logout'].'</a>';
		}
		else
		{
			$links[] = '<li id="navsearch"><a href="search.php">'.$lang_common['Search'].'</a>';
			$links[] = '<li id="navprofile"><a href="profile.php?id='.$pun_user['id'].'">'.$lang_common['Profile'].'</a>';
			$links[] = '<li id="navadmin"><a href="admin_index.php">'.$lang_common['Admin'].'</a>';
			require(PUN_ROOT.'include/pms/functions_navlinks.php');
			$links[] = '<li id="navlogout"><a href="login.php?action=out&amp;id='.$pun_user['id'].'&amp;csrf_token='.sha1($pun_user['id'].sha1(get_remote_address())).'">'.$lang_common['Logout'].'</a>';
		}
	}

	// Are there any additional navlinks we should insert into the array before imploding it?
	if ($pun_config['o_additional_navlinks'] != '')
	{
		if (preg_match_all('#([0-9]+)\s*=\s*(.*?)\n#s', $pun_config['o_additional_navlinks']."\n", $extra_links))
		{
			// Insert any additional links into the $links array (at the correct index)
			for ($i = 0; $i < count($extra_links[1]); ++$i)
				array_splice($links, (int)$extra_links[1][$i], 0, ['<li id="navextra'.($i + 1).'">'.$extra_links[2][$i]]);
		}
	}

	return '<ul>'."\n\t\t\t\t".implode($lang_common['Link separator'].'</li>'."\n\t\t\t\t", $links).'</li>'."\n\t\t\t".'</ul>';
}


//
// Display the profile navigation menu
//
function generate_profile_menu($page = '')
{
	global $lang_profile, $pun_config, $pun_user, $id;

?>
<div id="profile" class="block2col">
	<div class="blockmenu">
		<h2><span><?php echo $lang_profile['Profile menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?php if ($page == 'view') echo ' class="isactive"'; ?>><a href="profile.php?section=view&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section view'] ?></a></li>
					<li<?php if ($page == 'essentials') echo ' class="isactive"'; ?>><a href="profile.php?id=<?php echo $id ?>"><?php echo $lang_profile['Section essentials'] ?></a></li>
					<li<?php if ($page == 'personal') echo ' class="isactive"'; ?>><a href="profile.php?section=personal&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section personal'] ?></a></li>
					<li<?php if ($page == 'messaging') echo ' class="isactive"'; ?>><a href="profile.php?section=messaging&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section messaging'] ?></a></li>
					<li<?php if ($page == 'personality') echo ' class="isactive"'; ?>><a href="profile.php?section=personality&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section personality'] ?></a></li>
					<li<?php if ($page == 'display') echo ' class="isactive"'; ?>><a href="profile.php?section=display&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section display'] ?></a></li>
					<li<?php if ($page == 'privacy') echo ' class="isactive"'; ?>><a href="profile.php?section=privacy&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section privacy'] ?></a></li>
<?php if ($pun_user['g_id'] == PUN_ADMIN || ($pun_user['g_id'] == PUN_MOD && $pun_config['p_mod_ban_users'] == '1')): ?>					<li<?php if ($page == 'admin') echo ' class="isactive"'; ?>><a href="profile.php?section=admin&amp;id=<?php echo $id ?>"><?php echo $lang_profile['Section admin'] ?></a></li>
<?php endif; ?>				</ul>
			</div>
		</div>
	</div>
<?php

}


//
// Update posts, topics, last_post, last_post_id and last_poster for a forum
//
function update_forum($forum_id)
{
	global $db;

	$result = $db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id) or error('Unable to fetch forum topic count', __FILE__, __LINE__, $db->error());
	list($num_topics, $num_posts) = $db->fetch_row($result);

	$num_posts += $num_topics;		// $num_posts is only the sum of all replies (we have to add the topic posts)

	$result = $db->query('SELECT last_post, last_post_id, last_poster FROM '.$db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))		// There are topics in the forum
	{
		list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);

		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\' WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
	}
	else	// There are no topics
		$db->query('UPDATE '.$db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$forum_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
}


//
// Delete a topic and all of it's posts
//
function delete_topic($topic_id)
{
	global $db;

	// Delete the topic and any redirect topics
	$db->query('DELETE FROM '.$db->prefix.'topics WHERE id='.$topic_id.' OR moved_to='.$topic_id) or error('Unable to delete topic', __FILE__, __LINE__, $db->error());

	// Create a list of the post ID's in this topic
	$post_ids = '';
	$result = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());
	while ($row = $db->fetch_row($result))
		$post_ids .= $post_ids != '' ? ','.$row[0] : $row[0];

	// Make sure we have a list of post ID's
	if ($post_ids != '')
	{
		strip_search_index($post_ids);

		// Delete posts in topic
		$db->query('DELETE FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to delete posts', __FILE__, __LINE__, $db->error());
		// MOD Poll
		$db->query('DELETE FROM '.$db->prefix.'polls WHERE pollid='.$topic_id) or error('Unable to delete polls', __FILE__, __LINE__, $db->error());
		// MOD END
	}

	// Delete any subscriptions for this topic
	$db->query('DELETE FROM '.$db->prefix.'subscriptions WHERE topic_id='.$topic_id) or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());
}


//
// Delete a single post
//
function delete_post($post_id, $topic_id)
{
	global $db;

	$result = $db->query('SELECT id, poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id.' ORDER BY id DESC LIMIT 2') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	list($last_id, ,) = $db->fetch_row($result);
	list($second_last_id, $second_poster, $second_posted) = $db->fetch_row($result);

	// Delete the post
	$db->query('DELETE FROM '.$db->prefix.'posts WHERE id='.$post_id) or error('Unable to delete post', __FILE__, __LINE__, $db->error());

	strip_search_index($post_id);

	// Count number of replies in the topic
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to fetch post count for topic', __FILE__, __LINE__, $db->error());
	$num_replies = $db->result($result, 0) - 1;

	// If the message we deleted is the most recent in the topic (at the end of the topic)
	if ($last_id == $post_id)
	{
		// If there is a $second_last_id there is more than 1 reply to the topic
		if (!empty($second_last_id))
			$db->query('UPDATE '.$db->prefix.'topics SET last_post='.$second_posted.', last_post_id='.$second_last_id.', last_poster=\''.$db->escape($second_poster).'\', num_replies='.$num_replies.' WHERE id='.$topic_id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
		else
			// We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
			$db->query('UPDATE '.$db->prefix.'topics SET last_post=posted, last_post_id=id, last_poster=poster, num_replies='.$num_replies.' WHERE id='.$topic_id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
	}
	else
		// Otherwise we just decrement the reply counter
		$db->query('UPDATE '.$db->prefix.'topics SET num_replies='.$num_replies.' WHERE id='.$topic_id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
}


//
// Delete a single user
//
function delete_user($user_id,$delete_posts)
{
	global $db;
	static $group_id, $username, $cur_forum, $cur_moderators, $cur_post, $result, $result2;

	// Get the username and group of the user we are deleting
	$result = $db->query('SELECT group_id, username FROM '.$db->prefix.'users WHERE id='.$user_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	list($group_id, $username) = $db->fetch_row($result);

	if ($group_id == PUN_ADMIN)
		message('Administrators cannot be deleted. In order to delete this user, you must first move him/her to a different user group.');

	// If the user is a moderator, we remove him/her from the moderator list in all forums as well
	if ($group_id < PUN_GUEST)
	{
		$result = $db->query('SELECT id, moderators FROM '.$db->prefix.'forums') or error('Unable to fetch forum list', __FILE__, __LINE__, $db->error());

		while ($cur_forum = $db->fetch_assoc($result))
		{
			$cur_moderators = $cur_forum['moderators'] != '' ? unserialize($cur_forum['moderators']) : [];

			if (in_array($user_id, $cur_moderators))
			{
				unset($cur_moderators[$username]);
				$cur_moderators = !empty($cur_moderators) ? '\''.$db->escape(serialize($cur_moderators)).'\'' : 'NULL';

				$db->query('UPDATE '.$db->prefix.'forums SET moderators='.$cur_moderators.' WHERE id='.$cur_forum['id']) or error('Unable to update forum', __FILE__, __LINE__, $db->error());
			}
		}
	}

	// Delete any subscriptions
	$db->query('DELETE FROM '.$db->prefix.'subscriptions WHERE user_id='.$user_id) or error('Unable to delete subscriptions', __FILE__, __LINE__, $db->error());

	// Remove him/her from the online list (if they happen to be logged in)
	$db->query('DELETE FROM '.$db->prefix.'online WHERE user_id='.$user_id) or error('Unable to remove user from online list', __FILE__, __LINE__, $db->error());

	// Should we delete all posts made by this user?
	if ($delete_posts == '1')
	{
		require_once PUN_ROOT.'include/search_idx.php';
		@set_time_limit(0);

		// Find all posts made by this user
		$result = $db->query('SELECT p.id, p.topic_id, t.forum_id FROM '.$db->prefix.'posts AS p INNER JOIN '.$db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id WHERE p.poster_id='.$user_id) or error('Unable to fetch posts', __FILE__, __LINE__, $db->error());
		if ($db->num_rows($result))
		{
			while ($cur_post = $db->fetch_assoc($result))
			{
				// Determine whether this post is the "topic post" or not
				$result2 = $db->query('SELECT id FROM '.$db->prefix.'posts WHERE topic_id='.$cur_post['topic_id'].' ORDER BY posted LIMIT 1') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());

				if ($db->result($result2) == $cur_post['id'])
					delete_topic($cur_post['topic_id']);
				else
					delete_post($cur_post['id'], $cur_post['topic_id']);

				update_forum($cur_post['forum_id']);
			}
		}
	}
	else
		// Set all his/her posts to guest
		$db->query('UPDATE '.$db->prefix.'posts SET poster_id=1 WHERE poster_id='.$user_id) or error('Unable to update posts', __FILE__, __LINE__, $db->error());

	// Delete the user
	$db->query('DELETE FROM '.$db->prefix.'users WHERE id='.$user_id) or error('Unable to delete user', __FILE__, __LINE__, $db->error());

	// Regenerate the users count cache
	require_once PUN_ROOT.'include/cache.php';
	generate_users_count_cache();

	// Regenerate the last user cache
	require_once PUN_ROOT.'include/cache.php';
	generate_last_user_cache();

	require(PUN_ROOT.'include/pms/profile_delete.php');

	// Delete user avatar
	if (file_exists($pun_config['o_avatars_dir'].'/'.$id.'.gif'))
		@unlink($pun_config['o_avatars_dir'].'/'.$id.'.gif');
	if (file_exists($pun_config['o_avatars_dir'].'/'.$id.'.jpg'))
		@unlink($pun_config['o_avatars_dir'].'/'.$id.'.jpg');
	if (file_exists($pun_config['o_avatars_dir'].'/'.$id.'.png'))
		@unlink($pun_config['o_avatars_dir'].'/'.$id.'.png');

}


//
// Replace censored words in $text
//
function censor_words($text)
{
	global $db;
	static $search_for, $replace_with;

	// If not already built in a previous call, build an array of censor words and their replacement text
	if (!isset($search_for))
	{
		$result = $db->query('SELECT search_for, replace_with FROM '.$db->prefix.'censoring') or error('Unable to fetch censor word list', __FILE__, __LINE__, $db->error());
		$num_words = $db->num_rows($result);

		$search_for = [];
		for ($i = 0; $i < $num_words; ++$i)
		{
			list($search_for[$i], $replace_with[$i]) = $db->fetch_row($result);
			$search_for[$i] = '/([^\pL])('.str_replace('\*', '\pL*', preg_quote($search_for[$i], '/')).')([^\pL])/iu';
			$replace_with[$i] = '\1'.$replace_with[$i].'\3';
		}
	}

	if (!empty($search_for))
		$text = substr(preg_replace($search_for, $replace_with, ' '.$text.' '), 1, -1);

	return $text;
}


//
// Determines the correct title for $user
// $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
//
function get_title($user)
{
	global $db, $pun_config, $pun_bans, $lang_common;
	static $ban_list, $pun_ranks;

	// If not already built in a previous call, build an array of lowercase banned usernames
	if (empty($ban_list))
	{
		$ban_list = [];

		foreach ($pun_bans as $cur_ban)
			$ban_list[] = (function_exists('utf8_strtolower') ? utf8_strtolower($cur_ban['username']) : strtolower($cur_ban['username']));
	}

	// If not already loaded in a previous call, load the cached ranks
	if ($pun_config['o_ranks'] == '1' && empty($pun_ranks))
	{
		@include PUN_ROOT.'cache/cache_ranks.php';
		if (!defined('PUN_RANKS_LOADED'))
		{
			require_once PUN_ROOT.'include/cache.php';
			generate_ranks_cache();
			require PUN_ROOT.'cache/cache_ranks.php';
		}
	}

	// If the user is banned
	if (in_array(pun_strtolower($user['username']), $ban_list))
		$user_title = $lang_common['Banned'];
	// If the user has a custom title
	else if ($user['title'] != '')
		$user_title = pun_htmlspecialchars($user['title']);
	// If the user group has a default user title
	else if ($user['g_user_title'] != '')
		$user_title = pun_htmlspecialchars($user['g_user_title']);
	// If the user is a guest
	else if ($user['g_id'] == PUN_GUEST)
		$user_title = $lang_common['Guest'];
	else
	{
		// Are there any ranks?
		if ($pun_config['o_ranks'] == '1' && !empty($pun_ranks))
		{
			@reset($pun_ranks);
			while (list(, $cur_rank) = @each($pun_ranks))
			{
				if (intval($user['num_posts']) >= $cur_rank['min_posts'])
					$user_title = pun_htmlspecialchars($cur_rank['rank']);
			}
		}

		// If the user didn't "reach" any rank (or if ranks are disabled), we assign the default
		if (!isset($user_title))
			$user_title = $lang_common['Member'];
	}

	return $user_title;
}


//
// Generate a string with numbered links (for multipage scripts)
//
function paginate($num_pages, $cur_page, $link_to)
{
	global $lang_common;

	$pages = [];
	$link_to_all = false;
	$nav_links = true;

	// If $cur_page == -1, we link to all pages (used in viewforum.php)
	if ($cur_page == -1)
	{
		$cur_page = 1;
		$link_to_all = true;
		$nav_links = false;
	}

	if ($num_pages <= 1)
		$pages = ['<strong>1</strong>'];
	else
	{
		if ($cur_page > 3)
		{
			$pages[] = '<a href="'.$link_to.'&amp;p=1">1</a>';

			if ($cur_page != 4)
			{
				$pages[] = '&hellip;';
				$manual = true;
			}
		}

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current = $cur_page - 2, $stop = $cur_page + 3; $current < $stop; ++$current)
		{
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $cur_page || $link_to_all)
				$pages[] = '<a href="'.$link_to.'&amp;p='.$current.'">'.$current.'</a>';
			else
				$pages[] = '<strong>'.$current.'</strong>';
		}

		if ($cur_page <= $num_pages-3)
		{
			if ($cur_page != $num_pages-3)
			{
				$pages[] = '&hellip;';
				$manual = true;
			}

			$pages[] = '<a href="'.$link_to.'&amp;p='.$num_pages.'">'.$num_pages.'</a>';
		}
	}

	if($nav_links)
	{
		if($cur_page > 1){
			$back_page_number = $cur_page-1;
			$back_page = '<a href="'.$link_to.'&amp;p='.$back_page_number.'">&laquo;</a>';
			array_splice($pages, 0, 0, $back_page);
		}
		if($manual)
			array_push($pages, '<a href="#" onclick="return manualPage(\''.$lang_common['Pages manual'].'\', '.$cur_page.', \''.$link_to.'\')">#</a>');
		if($cur_page < $num_pages){
			$next_page_number = $cur_page+1;
			$next_page = '<a href="'.$link_to.'&amp;p='.$next_page_number.'">&raquo;</a>';
			array_push($pages, $next_page);
		}
	}

	return implode('&nbsp;', $pages);
}


//
// Display a message
//
function message($message, $no_back_link = false)
{
	global $db, $lang_common, $pun_config, $pun_start, $tpl_main;

	if (!defined('PUN_HEADER'))
	{
		global $pun_user;

		$page_title = pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['Info'];
		require PUN_ROOT.'header.php';
	}

?>

<div id="msg" class="block">
	<h2><span><?php echo $lang_common['Info'] ?></span></h2>
	<div class="box">
		<div class="inbox">
		<p><?php echo $message ?></p>
<?php if (!$no_back_link): ?>		<p><a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></p>
<?php endif; ?>		</div>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


//
// Format a time string according to $time_format and timezones
//
function format_time($timestamp, $date_only = false)
{
	global $pun_config, $lang_common, $pun_user;

	if ($timestamp == '')
		return $lang_common['Never'];

	$diff = ($pun_user['timezone'] - $pun_config['o_server_timezone']) * 3600;
	(int)$timestamp += $diff;
	$now = time();

	$date = date($pun_config['o_date_format'], $timestamp);
	$today = date($pun_config['o_date_format'], $now+$diff);
	$yesterday = date($pun_config['o_date_format'], $now+$diff-86400);

	if ($date == $today)
		$date = $lang_common['Today'];
	else if ($date == $yesterday)
		$date = $lang_common['Yesterday'];

	if (!$date_only)
		return $date.' '.date($pun_config['o_time_format'], $timestamp);
	else
		return $date;
}


//
//
//
function format_time_interval($time)
{
	$str = '';
	$words = 2;
	if ($words && $time >= 86400)
	{
		$str .= floor($time / 86400) . ' days ';
		$time %= 86400;
		$words--;
	}
	if ($words && $time >= 3600)
	{
		$str .= floor($time / 3600) . ' hours ';
		$time %= 3600;
		$words--;
	}
	if ($words && $time >= 60)
	{
		$str .= floor($time / 60) . ' minutes ';
		$time %= 60;
		$words--;
	}
	if ($words)
	{
		$str .= $time . ' seconds ';
	}
	return $str;
}

//
// If we are running pre PHP 4.3.0, we add our own implementation of file_get_contents
//
if (!function_exists('file_get_contents'))
{
	function file_get_contents($filename, $use_include_path = 0)
	{
		$data = '';

		if ($fh = fopen($filename, 'rb', $use_include_path))
		{
			$data = fread($fh, filesize($filename));
			fclose($fh);
		}

		return $data;
	}
}


//
// Make sure that HTTP_REFERER matches $pun_config['o_base_url']/$script
//
function confirm_referrer($script)
{
	global $pun_config, $lang_common;

	if (!preg_match('#^'.preg_quote(str_replace('http://', '', $pun_config['o_base_url']).'/'.$script, '#').'#i', preg_replace('/^https?:\/\//', '', (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),1)))
		message($lang_common['Bad referrer']);
}


//
// Generate a random password of length $len
//
function random_pass($len)
{
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	$password = '';
	for ($i = 0; $i < $len; ++$i)
		$password .= substr($chars, mt_rand() % strlen($chars), 1);

	return $password;
}


//
// Compute a hash of $str
// Uses sha1() if available. If not, SHA1 through mhash() if available. If not, fall back on md5().
//
function pun_hash($str)
{
	if (function_exists('sha1'))	// Only in PHP 4.3.0+
		return sha1($str);
	else if (function_exists('mhash'))	// Only if Mhash library is loaded
		return bin2hex(mhash(MHASH_SHA1, $str));
	else
		return md5($str);
}


//
// validate ip correct ipv4 and ipv6 address
//
function is_valid_ip($ip,$ip_type='')
{

	if($ip_type=='ipv4')
		$result = filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
	elseif($ip_type=='ipv6')
		$result = filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_IPV6);
	else
		$result = filter_var($ip, FILTER_VALIDATE_IP);

	if($result == $ip)
		return true;
	else
		return false;
}


//
// Try to determine the correct remote IP-address
//
function get_remote_address()
{
	$remote_address = $_SERVER['REMOTE_ADDR'];

        // The general format of the field is:
	// X-Forwarded-For: client1, proxy1, proxy2
	// where the value is a comma+space separated list of IP addresses, the left-most being the farthest downstream client,
	// and each successive proxy that passed the request adding the IP address where it received the request from.
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_X_REAL_IP']))
	{
		$via_proxy = $via_proxy = explode(",",(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['HTTP_X_REAL_IP']));
		$via_proxy = trim($via_proxy[0]);
		// filter out private/reserved ranges
		$via_proxy = filter_var($via_proxy, FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE);
		$via_proxy = filter_var($via_proxy, FILTER_VALIDATE_IP,FILTER_FLAG_NO_RES_RANGE);
	}

	if ($via_proxy)
		$remote_address=$via_proxy;

	return $remote_address;
}

//
// Fetch the current protocol in use - http or https
//
function get_current_protocol()
{
	$protocol = 'http';

	// Check if the server is claiming to using HTTPS
	if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')
		$protocol = 'https';

	// If we are behind a reverse proxy try to decide which protocol it is using
	if (defined('FORUM_BEHIND_REVERSE_PROXY'))
	{
		// Check if we are behind a Microsoft based reverse proxy
		if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) != 'off')
			$protocol = 'https';

		// Check if we're behind a "proper" reverse proxy, and what protocol it's using
		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
			$protocol = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
	}

	return $protocol;
}


//
// Fetch the base_url, optionally support HTTPS and HTTP
//
function get_base_url($support_https = false)
{
	global $pun_config;
	static $base_url;

	if (!$support_https)
		return $pun_config['o_base_url'];

	if (!isset($base_url))
	{
		// Make sure we are using the correct protocol
		$base_url = str_replace(['http://', 'https://'], get_current_protocol().'://', $pun_config['o_base_url']);
	}

	return $base_url;
}


//
// Calls htmlspecialchars with a few options already set
//
function pun_htmlspecialchars($str)
{
	if (!is_null($str))
	{
		$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
	}
	return $str;
}


//
// Removes invalid characters from a UTF-8 XML string
// https://www.ryadel.com/en/php-skip-invalid-characters-utf-8-xml-file-string/
//
function sanitizeXML($str)
{
    if (!empty($str))
    {
        // remove EOT+NOREP+EOX|EOT+<char> sequence (FatturaPA)
        $str = preg_replace('/(\x{0004}(?:\x{201A}|\x{FFFD})(?:\x{0003}|\x{0004}).)/u', '', $str);

        $regex = '/(
            [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0[\x80-\x9F] # Overlong encoding of prior code point
            | \xF0[\x80-\x8F] # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
        $str = preg_replace($regex, '', $str);

        $result = "";
        $current;
        $length = strlen($str);
        for ($i=0; $i < $length; $i++)
        {
            $current = ord($str{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF)))
            {
                $result .= chr($current);
            }
            else
            {
                // $ret;    // use this to strip invalid character(s)
                $ret .= " ";    // use this to replace them with spaces
            }
        }
        $str = $result;
    }
    return $str;
}


//
// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
//
function forum_remove_bad_characters()
{
	$_GET = remove_bad_characters($_GET);
	$_POST = remove_bad_characters($_POST);
	$_COOKIE = remove_bad_characters($_COOKIE);
	$_REQUEST = remove_bad_characters($_REQUEST);
}

//
// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from the given string
// See: http://kb.mozillazine.org/Network.IDN.blacklist_chars
//
function remove_bad_characters($array)
{
	static $bad_utf8_chars;

	if (!isset($bad_utf8_chars))
	{
		$bad_utf8_chars = array(
			"\xcc\xb7"		=> '',		// COMBINING SHORT SOLIDUS OVERLAY		0337	*
			"\xcc\xb8"		=> '',		// COMBINING LONG SOLIDUS OVERLAY		0338	*
			"\xe1\x85\x9F"	=> '',		// HANGUL CHOSEONG FILLER				115F	*
			"\xe1\x85\xA0"	=> '',		// HANGUL JUNGSEONG FILLER				1160	*
			"\xe2\x80\x8b"	=> '',		// ZERO WIDTH SPACE						200B	*
			"\xe2\x80\x8c"	=> '',		// ZERO WIDTH NON-JOINER				200C
			"\xe2\x80\x8d"	=> '',		// ZERO WIDTH JOINER					200D
			"\xe2\x80\x8e"	=> '',		// LEFT-TO-RIGHT MARK					200E
			"\xe2\x80\x8f"	=> '',		// RIGHT-TO-LEFT MARK					200F
			"\xe2\x80\xaa"	=> '',		// LEFT-TO-RIGHT EMBEDDING				202A
			"\xe2\x80\xab"	=> '',		// RIGHT-TO-LEFT EMBEDDING				202B
			"\xe2\x80\xac"	=> '', 		// POP DIRECTIONAL FORMATTING			202C
			"\xe2\x80\xad"	=> '',		// LEFT-TO-RIGHT OVERRIDE				202D
			"\xe2\x80\xae"	=> '',		// RIGHT-TO-LEFT OVERRIDE				202E
			"\xe2\x80\xaf"	=> '',		// NARROW NO-BREAK SPACE				202F	*
			"\xe2\x81\x9f"	=> '',		// MEDIUM MATHEMATICAL SPACE			205F	*
			"\xe2\x81\xa0"	=> '',		// WORD JOINER							2060
			"\xe3\x85\xa4"	=> '',		// HANGUL FILLER						3164	*
			"\xef\xbb\xbf"	=> '',		// ZERO WIDTH NO-BREAK SPACE			FEFF
			"\xef\xbe\xa0"	=> '',		// HALFWIDTH HANGUL FILLER				FFA0	*
			"\xef\xbf\xb9"	=> '',		// INTERLINEAR ANNOTATION ANCHOR		FFF9	*
			"\xef\xbf\xba"	=> '',		// INTERLINEAR ANNOTATION SEPARATOR		FFFA	*
			"\xef\xbf\xbb"	=> '',		// INTERLINEAR ANNOTATION TERMINATOR	FFFB	*
			"\xef\xbf\xbc"	=> '',		// OBJECT REPLACEMENT CHARACTER			FFFC	*
			"\xef\xbf\xbd"	=> '',		// REPLACEMENT CHARACTER				FFFD	*
			"\xe2\x80\x80"	=> ' ',		// EN QUAD								2000	*
			"\xe2\x80\x81"	=> ' ',		// EM QUAD								2001	*
			"\xe2\x80\x82"	=> ' ',		// EN SPACE								2002	*
			"\xe2\x80\x83"	=> ' ',		// EM SPACE								2003	*
			"\xe2\x80\x84"	=> ' ',		// THREE-PER-EM SPACE					2004	*
			"\xe2\x80\x85"	=> ' ',		// FOUR-PER-EM SPACE					2005	*
			"\xe2\x80\x86"	=> ' ',		// SIX-PER-EM SPACE						2006	*
			"\xe2\x80\x87"	=> ' ',		// FIGURE SPACE							2007	*
			"\xe2\x80\x88"	=> ' ',		// PUNCTUATION SPACE					2008	*
			"\xe2\x80\x89"	=> ' ',		// THIN SPACE							2009	*
			"\xe2\x80\x8a"	=> ' ',		// HAIR SPACE							200A	*
			"\xE3\x80\x80"	=> ' ',		// IDEOGRAPHIC SPACE					3000	*
		);
	}

	if (is_array($array))
		return array_map('remove_bad_characters', $array);

	// Strip out any invalid characters
	$array = utf8_bad_strip($array);

	// Remove control characters
	$array = preg_replace('%[\x00-\x08\x0b-\x0c\x0e-\x1f]%', '', $array);

	// Replace some "bad" characters
	$array = str_replace(array_keys($bad_utf8_chars), array_values($bad_utf8_chars), $array);

	return $array;
}


//
// Calls htmlspecialchars_decode with a few options already set
//
function pun_htmlspecialchars_decode($str)
{
	if (function_exists('htmlspecialchars_decode'))
		return htmlspecialchars_decode($str, ENT_QUOTES);

	static $translations;
	if (!isset($translations))
	{
		$translations = get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES);
		$translations['&#039;'] = '\''; // get_html_translation_table doesn't include &#039; which is what htmlspecialchars translates ' to, but apparently that is okay?! http://bugs.php.net/bug.php?id=25927
		$translations = array_flip($translations);
	}

	return strtr($str, $translations);
}


//
// Equivalent to strlen(), but for unicode
//
function pun_strlen($str)
{
	return utf8_strlen($str);
}


//
// Convert \r\n and \r to \n
//
function pun_linebreaks($str)
{
	return str_replace("\r", "\n", str_replace("\r\n", "\n", $str));
}


//
// A more aggressive version of trim()
//
function pun_trim($str)
{
	global $lang_common;

	if (strpos($lang_common['lang_encoding'], '8859') !== false)
	{
		$fishy_chars = [chr(0x81), chr(0x8D), chr(0x8F), chr(0x90), chr(0x9D), chr(0xA0)];
		return trim(str_replace($fishy_chars, ' ', $str));
	}
	else
		return trim($str);
}

//
// Unicode-friendly unserialize
// http://snippets.dzone.com/posts/show/6592
//
function pun_unserialize($str)
{
	return unserialize((string)$str);
}

//
// Display a message when board is in maintenance mode
//
function maintenance_message()
{
	global $db, $pun_config, $lang_common, $pun_user;

	// Deal with newlines, tabs and multiple spaces
	$pattern = ["\t", '  ', '  '];
	$replace = ['&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;'];
	$message = str_replace($pattern, $replace, $pun_config['o_maintenance_message']);


	// Load the maintenance template
	$tpl_maint = trim(file_get_contents(PUN_ROOT.'include/template/maintenance.tpl'));


	// START SUBST - <pun_include "*">
	while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_maint, $cur_include))
	{
		if (!file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
			error('Unable to process user include '.htmlspecialchars($cur_include[0]).' from template maintenance.tpl. There is no such file in folder /include/user/');

		ob_start();
		include PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
		$tpl_temp = ob_get_contents();
		$tpl_maint = str_replace($cur_include[0], $tpl_temp, $tpl_maint);
		ob_end_clean();
	}
	// END SUBST - <pun_include "*">


	// START SUBST - <pun_content_direction>
	$tpl_maint = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_maint);
	// END SUBST - <pun_content_direction>


	// START SUBST - <pun_char_encoding>
	$tpl_maint = str_replace('<pun_char_encoding>', $lang_common['lang_encoding'], $tpl_maint);
	// END SUBST - <pun_char_encoding>


	// START SUBST - <pun_head>
	ob_start();

?>
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['Maintenance'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_maint = str_replace('<pun_head>', $tpl_temp, $tpl_maint);
	ob_end_clean();
	// END SUBST - <pun_head>


	// START SUBST - <pun_maint_heading>
	$tpl_maint = str_replace('<pun_maint_heading>', $lang_common['Maintenance'], $tpl_maint);
	// END SUBST - <pun_maint_heading>


	// START SUBST - <pun_maint_message>
	$tpl_maint = str_replace('<pun_maint_message>', $message, $tpl_maint);
	// END SUBST - <pun_maint_message>


	// End the transaction
	$db->end_transaction();


	// Close the db connection (and free up any result data)
	$db->close();

	exit($tpl_maint);
}


//
// Display $message and redirect user to $destination_url
//
function redirect($destination_url, $message)
{
	global $db, $pun_config, $lang_common, $pun_user;

	// Prefix with o_base_url (unless there's already a valid URI)
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = $pun_config['o_base_url'].'/'.$destination_url;
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = $pun_config['o_base_url'].'/'.$destination_url;

	// Do a little spring cleaning
	$destination_url = preg_replace('/([\r\n])|(%0[ad])|(;[\s]*data[\s]*:)/i', '', $destination_url);

	// If the delay is 0 seconds, we might as well skip the redirect all together
	if ($pun_config['o_redirect_delay'] == '0' || !$pun_user['show_redirect'])
		header('Location: '.str_replace('&amp;', '&', $destination_url));


	// Load the redirect template
	$tpl_redir = trim(file_get_contents(PUN_ROOT.'include/template/redirect.tpl'));


	// START SUBST - <pun_include "*">
	while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_redir, $cur_include))
	{
		if (!file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
			error('Unable to process user include '.htmlspecialchars($cur_include[0]).' from template redirect.tpl. There is no such file in folder /include/user/');

		ob_start();
		include PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
		$tpl_temp = ob_get_contents();
		$tpl_redir = str_replace($cur_include[0], $tpl_temp, $tpl_redir);
		ob_end_clean();
	}
	// END SUBST - <pun_include "*">


	// START SUBST - <pun_content_direction>
	$tpl_redir = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_redir);
	// END SUBST - <pun_content_direction>


	// START SUBST - <pun_char_encoding>
	$tpl_redir = str_replace('<pun_char_encoding>', $lang_common['lang_encoding'], $tpl_redir);
	// END SUBST - <pun_char_encoding>


	// START SUBST - <pun_head>
	ob_start();

	if (!defined('PUN_DEBUG')):

?>
<meta http-equiv="refresh" content="<?php echo $pun_config['o_redirect_delay'] ?>;URL=<?php echo str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], $destination_url) ?>" />
<?php endif; ?>
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']).' / '.$lang_common['Redirecting'] ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_head>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_head>


	// START SUBST - <pun_redir_heading>
	$tpl_redir = str_replace('<pun_redir_heading>', $lang_common['Redirecting'], $tpl_redir);
	// END SUBST - <pun_redir_heading>


	// START SUBST - <pun_redir_text>
	$tpl_temp = $message.'<br /><br />'.'<a href="'.$destination_url.'">'.$lang_common['Click redirect'].'</a>';
	$tpl_redir = str_replace('<pun_redir_text>', $tpl_temp, $tpl_redir);
	// END SUBST - <pun_redir_text>


	// START SUBST - <pun_footer>
	ob_start();

	// End the transaction
	$db->end_transaction();

	// Display executed queries (if enabled)
	if (defined('PUN_SHOW_QUERIES'))
		display_saved_queries();

	$tpl_temp = trim(ob_get_contents());
	$tpl_redir = str_replace('<pun_footer>', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <pun_footer>


	// Close the db connection (and free up any result data)
	$db->close();

	exit($tpl_redir);
}


//
// Display a simple error message
//
function error($message, $file, $line, $db_error = false)
{
	global $pun_config;

	if(empty($pun_user['language']))
		$pun_user['language'] = $pun_config['o_default_lang'];
	require PUN_ROOT.'lang/'.$pun_user['language'].'/error.php';

	// Set a default title if the script failed before $pun_config could be populated
	if (empty($pun_config))
		$pun_config['o_board_title'] = 'PunBB';

	// Empty output buffer and stop buffering
	@ob_end_clean();

	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
	if (!empty($pun_config['o_gzip']) && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?> / <?php echo $lang_error['Encountered'] ?></title>
<style type="text/css">
<!--
BODY {MARGIN: 10% 20% auto 20%; font: 10px Verdana, Arial, Helvetica, sans-serif}
#errorbox {BORDER: 1px solid #B84623}
H2 {MARGIN: 0; COLOR: #FFFFFF; BACKGROUND-COLOR: #B84623; FONT-SIZE: 1.1em; PADDING: 5px 4px}
#errorbox DIV {PADDING: 6px 5px; BACKGROUND-COLOR: #F1F1F1}
-->
</style>
</head>
<body>

<div id="errorbox">
	<h2><?php echo $lang_error['Encountered'] ?></h2>
	<div>
<?php

	if (defined('PUN_DEBUG'))
	{
		echo "\t\t".'<strong>'.$lang_error['File'].':</strong> '.$file.'<br />'."\n\t\t".'<strong>Line:</strong> '.$line.'<br /><br />'."\n\t\t".'<strong>'.$lang_error['Error'].'</strong>: '.$message."\n";

		if ($db_error)
		{
			echo "\t\t".'<br /><br /><strong>'.$lang_error['Database reported'].':</strong> '.pun_htmlspecialchars($db_error['error_msg']).($db_error['error_no'] ? ' (Errno: '.$db_error['error_no'].')' : '')."\n";

			if ($db_error['error_sql'] != '')
				echo "\t\t".'<br /><br /><strong>'.$lang_error['Failed query'].':</strong> '.pun_htmlspecialchars($db_error['error_sql'])."\n";
		}
	}
	else
		echo "\t\t".$lang_error['Error'].': <strong>'.$message.'.</strong>'."\n";

?>
	</div>
</div>

</body>
</html>
<?php

	// If a database connection was established (before this error) we close it
	if ($db_error)
		$GLOBALS['db']->close();

	exit;
}

// DEBUG FUNCTIONS BELOW

//
// Display executed queries (if enabled)
//
function display_saved_queries()
{
	global $db, $lang_common;

	// Get the queries so that we can print them out
	$saved_queries = $db->get_saved_queries();

?>

<div id="debug" class="blocktable">
	<h2><span><?php echo $lang_common['Debug table'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col">Time (s)</th>
					<th class="tcr" scope="col">Query</th>
				</tr>
			</thead>
			<tbody>
<?php

	$query_time_total = 0.0;
	while (list(, $cur_query) = @each($saved_queries))
	{
		$query_time_total += $cur_query[1];

?>
				<tr>
					<td class="tcl"><?php echo $cur_query[1] != 0 ? $cur_query[1] : '&nbsp;' ?></td>
					<td class="tcr"><?php echo pun_htmlspecialchars($cur_query[0]) ?></td>
				</tr>
<?php

	}

?>
				<tr>
					<td class="tcl" colspan="2">Total query time: <?php echo $query_time_total ?> s</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
</div>
<?php

}


//
// Unset any variables instantiated as a result of register_globals being enabled
//
function unregister_globals()
{
	$register_globals = @ini_get('register_globals');
	if ($register_globals === "" || $register_globals === "0" || $register_globals === "off")
		return;

	// Prevent script.php?GLOBALS[foo]=bar
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
		exit('I\'ll have a steak sandwich and... a steak sandwich.');

	// Variables that shouldn't be unset
	$no_unset = ['GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES'];

	// Remove elements in $GLOBALS that are present in any of the superglobals
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : []);
	foreach ($input as $k => $v)
	{
		if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
		{
			unset($GLOBALS[$k]);
			unset($GLOBALS[$k]);	// Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
		}
	}
}


//
// Dump contents of variable(s)
//
function dump()
{
	echo '<pre>';

	$num_args = func_num_args();

	for ($i = 0; $i < $num_args; ++$i)
	{
		print_r(func_get_arg($i));
		echo "\n\n";
	}

	echo '</pre>';
	exit;
}

// MOD: MARK TOPICS AS READ - NEW CODE TO END OF FILE

function get_all_new_topics() {

	global $db, $pun_user;
	$result = $db->query('SELECT forum_id, id, last_post FROM '.$db->prefix.'topics WHERE last_post>'. $pun_user['last_visit'] .' AND moved_to IS NULL ORDER BY last_post DESC') or error('Unable to fetch new topics from forum', __FILE__, __LINE__, $db->error());
	$new_topics = [];
	while($new_topics_row = $db->fetch_assoc($result))
		$new_topics[$new_topics_row['forum_id']][$new_topics_row['id']] = $new_topics_row['last_post'];
	return $new_topics;
}

function forum_is_new($forum_id, $last_post_time) { // this function scares me but I believe all the logic is good.

	global $db, $pun_user, $new_topics;

	// first we try to do this the easy way.
	if ($pun_user['last_visit'] >= $last_post_time) { // were there no posts since the user's last visit?
		return false;
	} else if (!empty($pun_user['read_topics']['f'][$forum_id]) && // has the user marked all topics in
		$pun_user['read_topics']['f'][$forum_id] >= $last_post_time) { // the forum read since the last post?
		return false;
	} else if (empty($pun_user['read_topics']['t']) && empty($pun_user['read_topics']['f'])) { // is it even possible that any of the new posts could be read?
		return true;
	} else {
		// now we must loop through all the "unread" topics in the forum and see if the user has read them.
		foreach($new_topics[$forum_id] as $topic_id => $last_post) {
			if ( // i'll be nice and explain this one for you. if:
				(empty($pun_user['read_topics']['f'][$forum_id]) || // the user hasn't marked the forum read, or
				$pun_user['read_topics']['f'][$forum_id] < $last_post) && // they have but the topic has been posted in since, AND
				(empty($pun_user['read_topics']['t'][$topic_id]) || // the user hasn't marked the topic read, or
				$pun_user['read_topics']['t'][$topic_id] < $last_post) // they have but the topic has been posted in since, then
			)
				return true; // the topic must be new
		}
		return false; // well, since every topic was marked read, then the forum must not have any new posts.
	}
}

function topic_is_new($topic_id, $forum_id, $last_post_time) {

	global $pun_user;

	if ($pun_user['last_visit'] >= $last_post_time) {
		return false;
	} else if (!empty($pun_user['read_topics']['f'][$forum_id]) &&
		$pun_user['read_topics']['f'][$forum_id] >= $last_post_time) {
		return false;
	} else if (!empty($pun_user['read_topics']['t'][$topic_id]) &&
		$pun_user['read_topics']['t'][$topic_id] >= $last_post_time) {
		return false;
	}
	return true;
}

function mark_topic_read($topic_id, $forum_id, $last_post) {

	global $db, $pun_user;

	if (topic_is_new($topic_id, $forum_id, $last_post)) {
		$pun_user['read_topics']['t'][$topic_id] = time();
		$db->query('UPDATE '.$db->prefix.'users SET read_topics=\''.$db->escape(serialize($pun_user['read_topics'])).'\' WHERE id='.$pun_user['id']) or error('Unable to update read-topic data', __FILE__, __LINE__, $db->error());
	}
}

// creates sql-query for multigroup mod
function multigrp_getSql($db) {
	global $pun_user;

	$retJoin = "LEFT JOIN ".$db->prefix."forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=".$pun_user['g_id'].")";
	$retWhere = " (fp.read_forum IS NULL OR fp.read_forum=1)";

	if ($pun_user["membergroupids"]) {
		$mgrps = explode(',', $pun_user["membergroupids"]);
		$count = 1;
		foreach($mgrps as $mgrp) {
			if(!is_null($mgrp) && $mgrp !== 0) {
				$retJoin  .= " LEFT JOIN ".$db->prefix."forum_perms AS fp".$count." ON (fp".$count.".forum_id=f.id AND fp".$count.".group_id=".$mgrp.")";
				$retWhere .= " OR (fp".$count.".read_forum IS NULL OR fp".$count.".read_forum=1)";
				$count++;
			}
		}
	}

	return $retJoin." WHERE (".$retWhere.") ";
}

//
// Try to determine the user-agent string for user
//
function get_user_ua() {
	$user_ua = '';
	$uastring = '';
	if (!empty($_SERVER['HTTP_USER_AGENT'])) {
		$uastring = filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_HIGH);
		$user_ua = pun_htmlspecialchars($uastring);
	}

	return $user_ua;
}

//
// More portable strtolower
//
function pun_strtolower($string) {
	global $lang_common;

	if (function_exists('utf8_strtolower'))
		$string = utf8_strtolower($string);
	else $string = strtolower($string);
	return $string;
}

//
// More portable strtoupper
//
function pun_strtoupper($string) {
	global $lang_common;

	if (function_exists('utf8_strtoupper'))
		$string = utf8_strtoupper($string);
	else $string = strtoupper($string);
	return $string;
}

//
// More portable ucwords
//
function pun_ucwords($string) {
	global $lang_common;

	if (function_exists('utf8_ucwords'))
		$string = utf8_ucwords($string);
	else $string = ucwords($string);
	return $string;
}

//
// More portable strcasecmp
//
function pun_strcasecmp($string1, $string2) {
	global $lang_common;
	$string = '';
	if (function_exists('utf8_strcasecmp'))
		$string = utf8_strcasecmp($string1,$string2);
	else $string = strcasecmp($string1,$string2);
	return $string;
}

//
// For show User Agent in users info
//
function pun_get_browser($uagent) {
	$known_bw = [
		'firefox',
		'opera',
		'ie',
		'edge',
		'android',
		'aurora',
		'amigo',
		'arora',
		'camino',
		'chrome',
		'chromium',
		'comodo_dragon',
		'cometbird',
		'demobrowser',
		'dillo',
		'elinks',
		'epic',
		'epiphany',
		'fennec',
		'firebird',
		'flock',
		'foxware',
		'google',
		'granparadiso',
		'icab',
		'iceape',
		'icecat',
		'icedragon',
		'iceweasel',
		'iron',
		'jigsaw',
		'k-meleon',
		'kazehakase',
		'konqueror',
		'links',
		'lolifox',
		'lorentz',
		'luakit',
		'lunascape',
		'lynx',
		'maxthon',
		'midori',
		'minefield',
		'mozilla',
		'mozilladeveloperpreview',
		'namoroka',
		'netpositive',
		'netscape',
		'netsurf',
		'nightly',
		'omniweb',
		'orca',
		'palemoon',
		'playstation',
		'phoenix',
		'qtweb',
		'qupzilla',
		'qutebrowser',
		'rekonq',
		'robin',
		's60',
		'safari',
		'seamonkey',
		'shiira',
		'shiretoko',
		'sleipnir',
		'soe-denver',
		'songbird',
		'splashtop',
		'stainless',
		'sunrise',
		'swift',
		'ucbrowser',
		'uzbl',
		'webkit',
		'webianshell',
		'w3m',
		'wyzo',
		'yandex'
	];
	$ua = get_browser($uagent,true);
	$ua_browser = (!is_null($ua['browser'])) ? pun_strtolower($ua['browser']) : "unknown";
	$ua_version = (!is_null($ua['version'])) ? $ua['version'] : "0.0";
	$ua_browser_alt = pun_ucwords($ua_browser);

	if (!in_array($ua_browser,$known_bw)) {
		$ua_browser = "unknown";
		$ua_version = "0.0";
		$ua_browser_alt = "unknown";
	}
	else {
		if ($ua_browser == "ie" && $ua_version >= "9.0") {
			$ua_browser = "ie9";
			$ua_browser_alt = "IE";
		}
		if($ua_browser == "ie")
			$ua_browser_alt = "IE";
	}
	return [$ua_browser,$ua_version,$ua_browser_alt];
}

//
// Format RE: RE: RE: RE: to Re(4):
// written by francev.nikolay @ gmail<.>com
//
function pun_increment_pm($str) {
	if (preg_match('/^Re\(\d+\): /i', $str)) {
		$arr = preg_split('/^Re\(|\): /i', $str, 3);
		return 'Re('.(++$arr[1]).'): '.$arr[2];
	}
	elseif (preg_match('/^(Re: )+/i', $str, $matches))
		return preg_replace('/^(Re: )+/i', 'Re('.sizeof(explode(' ',$matches[0])).'): ', $str);
	else
		return 'Re: '.$str;
}

function hidden_redirect($location) {
	if (defined('PUN_DEBUG'))
		redirect(htmlspecialchars($location), 'Debug redirect...');
	else
		header('Location:' . htmlspecialchars($location));
}

//
// Extract blocks from a text with a starting and ending string
// This function always matches the most outer block so nesting is possible
//
function extract_blocks($text, $start, $end, $retab = true)
{
	global $pun_config;

	$code = [];
	$start_len = strlen($start);
	$end_len = strlen($end);
	$regex = '%(?:'.preg_quote($start, '%').'|'.preg_quote($end, '%').')%';
	$matches = [];

	if (preg_match_all($regex, $text, $matches))
	{
		$counter = $offset = 0;
		$start_pos = $end_pos = false;

		foreach ($matches[0] as $match)
		{
			if ($match == $start)
			{
				if ($counter == 0)
					$start_pos = strpos($text, $start);
				$counter++;
			}
			elseif ($match == $end)
			{
				$counter--;
				if ($counter == 0)
					$end_pos = strpos($text, $end, $offset + 1);
				$offset = strpos($text, $end, $offset + 1);
			}

			if ($start_pos !== false && $end_pos !== false)
			{
				$code[] = substr($text, $start_pos + $start_len,
					$end_pos - $start_pos - $start_len);
				$text = substr_replace($text, "\1", $start_pos,
					$end_pos - $start_pos + $end_len);
				$start_pos = $end_pos = false;
				$offset = 0;
			}
		}
	}

	if ($pun_config['o_indent_num_spaces'] != 8 && $retab)
	{
		$spaces = str_repeat(' ', (int)$pun_config['o_indent_num_spaces']);
		$text = str_replace("\t", $spaces, $text);
	}

	return [$code, $text];
}


//
// function url_valid($url) {
//
// Return associative array of valid URI components, or FALSE if $url is not
// RFC-3986 compliant. If the passed URL begins with: "www." or "ftp.", then
// "http://" or "ftp://" is prepended and the corrected full-url is stored in
// the return array with a key name "url". This value should be used by the caller.
//
// Return value: FALSE if $url is not valid, otherwise array of URI components:
// e.g.
// Given: "http://www.jmrware.com:80/articles?height=10&width=75#fragone"
// Array(
//	  [scheme] => http
//	  [authority] => www.jmrware.com:80
//	  [userinfo] =>
//	  [host] => www.jmrware.com
//	  [IP_literal] =>
//	  [IPV6address] =>
//	  [ls32] =>
//	  [IPvFuture] =>
//	  [IPv4address] =>
//	  [regname] => www.jmrware.com
//	  [port] => 80
//	  [path_abempty] => /articles
//	  [query] => height=10&width=75
//	  [fragment] => fragone
//	  [url] => http://www.jmrware.com:80/articles?height=10&width=75#fragone
// )
function url_valid($url)
{
	if (strpos($url, 'www.') === 0) $url = 'http://'. $url;
	if (strpos($url, 'ftp.') === 0) $url = 'ftp://'. $url;
	if (!preg_match('/# Valid absolute URI having a non-empty, valid DNS host.
		^
		(?P<scheme>[A-Za-z][A-Za-z0-9+\-.]*):\/\/
		(?P<authority>
		  (?:(?P<userinfo>(?:[A-Za-z0-9\-._~!$&\'()*+,;=:]|%[0-9A-Fa-f]{2})*)@)?
		  (?P<host>
			(?P<IP_literal>
			  \[
			  (?:
				(?P<IPV6address>
				  (?:												 (?:[0-9A-Fa-f]{1,4}:){6}
				  |												   ::(?:[0-9A-Fa-f]{1,4}:){5}
				  | (?:							 [0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){4}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,1}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){3}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){2}
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})?::	[0-9A-Fa-f]{1,4}:
				  | (?:(?:[0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})?::
				  )
				  (?P<ls32>[0-9A-Fa-f]{1,4}:[0-9A-Fa-f]{1,4}
				  | (?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
					   (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)
				  )
				|	(?:(?:[0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})?::	[0-9A-Fa-f]{1,4}
				|	(?:(?:[0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})?::
				)
			  | (?P<IPvFuture>[Vv][0-9A-Fa-f]+\.[A-Za-z0-9\-._~!$&\'()*+,;=:]+)
			  )
			  \]
			)
		  | (?P<IPv4address>(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}
							   (?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))
		  | (?P<regname>(?:[A-Za-z0-9\-._~!$&\'()*+,;=]|%[0-9A-Fa-f]{2})+)
		  )
		  (?::(?P<port>[0-9]*))?
		)
		(?P<path_abempty>(?:\/(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@]|%[0-9A-Fa-f]{2})*)*)
		(?:\?(?P<query>		  (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
		(?:\#(?P<fragment>	  (?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\\/?]|%[0-9A-Fa-f]{2})*))?
		$
		/mx', $url, $m)) return FALSE;
	switch ($m['scheme'])
	{
	case 'https':
	case 'http':
		if ($m['userinfo']) return FALSE; // HTTP scheme does not allow userinfo.
		break;
	case 'ftps':
	case 'ftp':
		break;
	default:
		return FALSE;	// Unrecognised URI scheme. Default to FALSE.
	}
	// Validate host name conforms to DNS "dot-separated-parts".
	if ($m{'regname'}) // If host regname specified, check for DNS conformance.
	{
		if (!preg_match('/# HTTP DNS host name.
			^					   # Anchor to beginning of string.
			(?!.{256})			   # Overall host length is less than 256 chars.
			(?:					   # Group dot separated host part alternatives.
			  [0-9A-Za-z]\.		   # Either a single alphanum followed by dot
			|					   # or... part has more than one char (63 chars max).
			  [0-9A-Za-z]		   # Part first char is alphanum (no dash).
			  [\-0-9A-Za-z]{0,61}  # Internal chars are alphanum plus dash.
			  [0-9A-Za-z]		   # Part last char is alphanum (no dash).
			  \.				   # Each part followed by literal dot.
			)*					   # One or more parts before top level domain.
			(?:					   # Explicitly specify top level domains.
			  com|edu|gov|int|mil|net|org|biz|
			  info|name|pro|aero|coop|museum|
			  asia|cat|jobs|mobi|tel|travel|
			  [A-Za-z]{2})		   # Country codes are exqactly two alpha chars.
			$					   # Anchor to end of string.
			/ix', $m['host'])) return FALSE;
	}
	$m['url'] = $url;
	for ($i = 0; isset($m[$i]); ++$i) unset($m[$i]);
	return $m; // return TRUE == array of useful named $matches plus the valid $url.
}
