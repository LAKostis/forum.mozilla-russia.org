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
// Tell common.php that we don't want output buffering
define('PUN_DISABLE_BUFFERING', 1);

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';

if ($pun_user['g_id'] > PUN_MOD)
	message($lang_common['No permission']);


// Show IP statistics for a certain user ID
if (isset($_GET['ip_stats']))
{
	$ip_stats = intval($_GET['ip_stats']);
	if ($ip_stats < 1)
		message($lang_common['Bad request']);


	$page_title = 'Admin | Users | '.pun_htmlspecialchars($pun_config['o_board_title']);

	require PUN_ROOT.'header.php';

?>
<div class="linkst">
	<div class="inbox">
		<div><a href="javascript:history.go(-1)">Go back</a></div>
	</div>
</div>

<div id="users1" class="blocktable">
	<h2><span>Users</span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col">IP address</th>
					<th class="tc2" scope="col">Last used</th>
					<th class="tc3" scope="col">Times found</th>
					<th class="tcr" scope="col">Action</th>
				</tr>
			</thead>
			<tbody>
<?php

	$result = $db->query('SELECT poster_ip, MAX(posted) AS last_used, COUNT(id) AS used_times FROM '.$db->prefix.'posts WHERE poster_id='.$ip_stats.' GROUP BY poster_ip ORDER BY last_used DESC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
	{
		while ($cur_ip = $db->fetch_assoc($result))
		{

?>
				<tr>
					<td class="tcl"><a href="moderate.php?get_host=<?php echo $cur_ip['poster_ip'] ?>"><?php echo $cur_ip['poster_ip'] ?></a></td>
					<td class="tc2"><?php echo format_time($cur_ip['last_used']) ?></td>
					<td class="tc3"><?php echo $cur_ip['used_times'] ?></td>
					<td class="tcr"><a href="admin_users.php?show_users=<?php echo $cur_ip['poster_ip'] ?>">Find more users for this ip</a></td>
				</tr>
<?php

		}
	}
	else
		echo "\t\t\t\t".'<tr><td class="tcl" colspan="4">There are currently no posts by that user in the forum.</td></tr>'."\n";

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox">
		<div><a href="javascript:history.go(-1)">Go back</a></div>
	</div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}


if (isset($_GET['show_users']))
{
	$ip = $_GET['show_users'];

	if (!is_valid_ip($ip))
		message('The supplied IP address is not correctly formatted.');


	$page_title = 'Admin | Users | '.pun_htmlspecialchars($pun_config['o_board_title']);
	require PUN_ROOT.'header.php';

?>
<div class="linkst">
	<div class="inbox">
		<div><a href="javascript:history.go(-1)">Go back</a></div>
	</div>
</div>

<div id="users2" class="blocktable">
	<h2><span>Users</span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col">Username</th>
					<th class="tc2" scope="col">E-mail</th>
					<th class="tc3" scope="col">Title/Status</th>
					<th class="tc4" scope="col">Posts</th>
					<th class="tc5" scope="col">Admin note</th>
					<th class="tcr" scope="col">Actions</th>
				</tr>
			</thead>
			<tbody>
<?php

	$result = $db->query('SELECT DISTINCT poster_id, poster FROM '.$db->prefix.'posts WHERE poster_ip=\''.$db->escape($ip).'\' ORDER BY poster DESC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
	$num_posts = $db->num_rows($result);

	if ($num_posts)
	{
		// Loop through users and print out some info
		for ($i = 0; $i < $num_posts; ++$i)
		{
			list($poster_id, $poster) = $db->fetch_row($result);

			$result2 = $db->query('SELECT u.id, u.username, u.email, u.title, u.registration_ip, u.num_posts, u.admin_note, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u INNER JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id>1 AND u.id='.$poster_id) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

			if (($user_data = $db->fetch_assoc($result2)))
			{
				$user_title = get_title($user_data);


					$actions = '<a href="admin_users.php?ip_stats='.$user_data['id'].'">View IP stats</a> - <a href="search.php?action=show_user&amp;user_id='.$user_data['id'].'">Show posts</a>';

?>
				<tr>
					<td class="tcl"><?php echo '<a href="profile.php?id='.$user_data['id'].'">'.pun_htmlspecialchars($user_data['username']).'</a>' ?></td>
					<td class="tc2"><a href="mailto:<?php echo $user_data['email'] ?>"><?php echo $user_data['email'] ?></a></td>
					<td class="tc3"><?php echo $user_title ?></td>
					<td class="tc4"><?php echo $user_data['num_posts'] ?></td>
					<td class="tc5"><?php echo $user_data['admin_note'] != '' ? $user_data['admin_note'] : '&nbsp;' ?></td>
					<td class="tcr"><?php echo $actions ?></td>
				</tr>
<?php

			}
			else
			{

?>
				<tr>
					<td class="tcl"><?php echo pun_htmlspecialchars($poster) ?></td>
					<td class="tc2">&nbsp;</td>
					<td class="tc3">Guest</td>
					<td class="tc4">&nbsp;</td>
					<td class="tc5">&nbsp;</td>
					<td class="tcr">&nbsp;</td>
				</tr>
<?php

				}
		}
	}
	else
		echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">The supplied IP address could not be found in the database.</td></tr>'."\n";

?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox">
		<div><a href="javascript:history.go(-1)">Go back</a></div>
	</div>
</div>
<?php
	require PUN_ROOT.'footer.php';
}

if (isset($_POST['delete_users_comply']))
{
	//Check this is legit
	confirm_referrer('admin_users.php');

	$idlist = $_POST['users_array'];

	@reset($idlist);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?> / Mass users deleting in progress &hellip;</title>
<style type="text/css">
body {
	font: 10px Verdana, Arial, Helvetica, sans-serif;
	color: #333333;
	background-color: #FFFFFF
}
</style>
</head>
<body>

Mass users deleting in progress&hellip; This might be a good time to put on some coffee :-)<br /><br />

<?php

	foreach ($idlist as $user)
	{
		echo 'Processing user <strong>'.$user.'</strong><br />'."\n";
		delete_user($user,1);
	}

		exit('<script type="text/javascript">history.go(-2)</script><br />JavaScript redirect unsuccessful. Click <a href="admin_users.php">here</a> to continue.');
}

else if (isset($_POST['action']) || isset($_POST['find_user']))
{
	$form = $_POST['form'];
	$form['username'] = $_POST['username'];

	// trim() all elements in $form
	$form = array_map('trim', $form);

	$posts_greater = trim($_POST['posts_greater']);
	$posts_less = trim($_POST['posts_less']);
	$last_post_after = trim($_POST['last_post_after']);
	$last_post_before = trim($_POST['last_post_before']);
	$registered_after = trim($_POST['registered_after']);
	$registered_before = trim($_POST['registered_before']);
	$order_by = $_POST['order_by'];
	$direction = $_POST['direction'];
	$user_group = $_POST['user_group'] === 'all' ? 'all' : intval($_POST['user_group']);
	$search_limit = intval($_POST['search_limit']) > 0 ? $_POST['search_limit'] : 0;
	$spam_email_match = isset($_POST['spam_email_match']) ? intval($_POST['spam_email_match']) : 0;
	$spam_ip_match = isset($_POST['spam_ip_match']) ? intval($_POST['spam_ip_match']) : 0;
	$spam_online_match = isset($_POST['spam_online_match']) ? intval($_POST['spam_online_match']) : 0;
	$email_validate = isset($_POST['email_validate']) ? intval($_POST['email_validate']) : 0;

	if (preg_match('/[^0-9]/', $posts_greater.$posts_less))
		message('You entered a non-numeric value into a numeric only column.');

	// Try to convert date/time to timestamps
        if ($last_post_after != '')
        	$last_post_after = strtotime($last_post_after);
        if ($last_post_before != '')
        	$last_post_before = strtotime($last_post_before);
        if ($registered_after != '')
        	$registered_after = strtotime($registered_after);
        if ($registered_before != '')
        	$registered_before = strtotime($registered_before);

	if ($last_post_after == -1 || $last_post_before == -1 || $registered_after == -1 || $registered_before == -1)
		message('You entered an invalid date/time.');

	if ($last_post_after != '')
		$conditions[] = 'u.last_post>'.$last_post_after;
	if ($last_post_before != '')
		$conditions[] = 'u.last_post<'.$last_post_before;
	if ($registered_after != '')
		$conditions[] = 'u.registered>'.$registered_after;
	if ($registered_before != '')
		$conditions[] = 'u.registered<'.$registered_before;

	$like_command = $db_type == 'pgsql' ? 'ILIKE' : 'LIKE';
	foreach ($form as $key => $input)
	{
		if ($input != '' && in_array($key, ['username', 'email', 'title', 'realname', 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location', 'signature', 'admin_note']))
			$conditions[] = 'u.'.$db->escape($key).' '.$like_command.' \''.$db->escape(str_replace('*', '%', $input)).'\'';
	}

	if ($posts_greater != '')
		$conditions[] = 'u.num_posts>'.$posts_greater;
	if ($posts_less != '')
		$conditions[] = 'u.num_posts<'.$posts_less;

	if ($user_group != 'all')
		$conditions[] = 'u.group_id='.intval($user_group).' OR membergroupids LIKE \'%,'.intval($user_group).',%\' OR membergroupids LIKE \''.intval($user_group).',%\' OR membergroupids LIKE \'%,'.intval($user_group).'\'';

	if (!isset($conditions) && ($spam_email_match=='0' && $spam_ip_match=='0' && $spam_online_match=='0' && $email_validate=='0'))
		message('You didn\'t enter any search terms.');

	// Fetch user count
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'users AS u WHERE u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : '')) or error('Unable to fetch user list count', __FILE__, __LINE__, $db->error());
	$num_users = $db->result($result);

	$start_from = 0;
	if (($search_limit == '0') || ($search_limit > $num_users))
		$search_limit = $num_users;

	$percent_shows = $num_users > 0 ? $search_limit / $num_users * 100 : 100;

	$page_title = 'Admin | Users | '.pun_htmlspecialchars($pun_config['o_board_title']);
	require PUN_ROOT.'header.php';

?>
<div class="linkst">
	<div class="inbox">
	<div><a href="javascript:history.go(-1)">Go back</a> Total searches found = <?php echo $num_users.', '; printf("%.2f",$percent_shows); ?>% displayed.</div>
	</div>
</div>

<form id="users2" method="post" action="admin_users.php?action=find_user">
<div class="blocktable">
	<h2><span>Users</span></h2>
	<div class="box">
		<div class="inbox">
			<table cellspacing="0">
			<thead>
				<tr>
					<th class="tcl" scope="col">Username</th>
					<th class="tc2" scope="col">E-mail</th>
					<th class="tc3" scope="col">Title/Status</th>
					<th class="tc4" scope="col">Posts</th>
					<th class="tc5" scope="col">Admin note</th>
					<th class="tcr" scope="col">Actions</th>
				</tr>
			</thead>
			<tbody>
<?php

	$result = $db->query('SELECT u.id, u.username, u.email, u.title, u.registration_ip, u.num_posts, u.admin_note, g.g_id, g.g_user_title FROM '.$db->prefix.'users AS u LEFT JOIN '.$db->prefix.'groups AS g ON g.g_id=u.group_id WHERE u.id>1'.(!empty($conditions) ? ' AND '.implode(' AND ', $conditions) : '').' ORDER BY '.$db->escape($order_by).' '.$db->escape($direction).' LIMIT '.$start_from.', '.$search_limit) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
	{
		$button_status = '';

		while ($user_data = $db->fetch_assoc($result))
		{
			$user_title = get_title($user_data);

			// This script is a special case in that we want to display "Not verified" for non-verified users
			if (($user_data['g_id'] == '' || $user_data['g_id'] == PUN_UNVERIFIED) && $user_title != $lang_common['Banned'])
				$user_title = '<span class="warntext">Not verified</span>';

				if ($email_validate == '1')
				{
					// Validate e-mail
					require PUN_ROOT.'include/email.php';

					if (!is_valid_email($user_data['email']))
							$spam_status[$user_data['id']]='Invalid email found!';

				}

				if ($spam_email_match == '1')
				{
					$listed_emails = file(PUN_ROOT.'cache/listed_email_1.txt', FILE_IGNORE_NEW_LINES);
					if($listed_emails)
					{
						foreach ($listed_emails as $listed_email) {
							if ($user_data['email'] == $listed_email)
								$spam_status[$user_data['id']]='Spam email found!';

						}
					}
				}


				if ($spam_ip_match == '1')
				{
					$listed_ips = file(PUN_ROOT.'cache/listed_ip_1.txt', FILE_IGNORE_NEW_LINES);
					if($listed_ips)
					{
						foreach ($listed_ips as $listed_ip) {
							if ($user_data['registration_ip'] == $listed_ip)
								$spam_status[$user_data['id']]='Spam ip found!';

						}
					}
				}

				if(!isset($spam_status[$user_data['id']]) && $spam_online_match == '1')
				{
					require_once(PUN_ROOT.'include/stopforumspam.php');
					$sfs = new StopForumSpam();
					$args = ['email' => $user_data['email'], 'ip' => $user_data['registration_ip'], 'username' => $user_data['username'], 'notorexit'];
					$spamcheck = $sfs->is_spammer( $args );
					if ($spamcheck['spammer']=='1' && $spamcheck['known']=='1')
						$spam_status[$user_data['id']]='Spam found online!';
				}

				if (isset($spam_status[$user_data['id']]))
				{
					$actions = '<a href="admin_users.php?ip_stats='.$user_data['id'].'">View IP stats</a> - <a href="search.php?action=show_user&amp;user_id='.$user_data['id'].'">Show posts</a>';

?>

				<tr>
					<input type="hidden" name="users_array[]" value="<?php echo $user_data['id']; ?>">
					<td class="tcl"><?php echo '<a href="profile.php?id='.$user_data['id'].'">'.pun_htmlspecialchars($user_data['username']).'</a>' ?></td>
					<td class="tc2"><a href="mailto:<?php echo $user_data['email'] ?>"><?php echo $user_data['email'] ?></a></td>
					<td class="tc3"><?php echo $user_title ?></td>
					<td class="tc4"><?php echo $user_data['num_posts'] ?></td>
					<td class="tc5"><?php echo $spam_status[$user_data['id']].'&nbsp;'?></td>
					<td class="tcr"><?php echo $actions ?></td>
				</tr>
<?php

				}
				elseif ($spam_email_match=='0' && $spam_ip_match=='0' && $spam_online_match=='0' && $email_validate=='0')
				{

			$actions = '<a href="admin_users.php?ip_stats='.$user_data['id'].'">View IP stats</a> - <a href="search.php?action=show_user&amp;user_id='.$user_data['id'].'">Show posts</a>';

?>
				<tr>
					<input type="hidden" name="users_array[]" value="<?php echo $user_data['id']; ?>">
					<td class="tcl"><?php echo '<a href="profile.php?id='.$user_data['id'].'">'.pun_htmlspecialchars($user_data['username']).'</a>' ?></td>
					<td class="tc2"><a href="mailto:<?php echo $user_data['email'] ?>"><?php echo $user_data['email'] ?></a></td>
					<td class="tc3"><?php echo $user_title ?></td>
					<td class="tc4"><?php echo $user_data['num_posts'] ?></td>
					<td class="tc5"><?php echo $user_data['admin_note'] != '' ? $user_data['admin_note'] : '&nbsp;' ?></td>
					<td class="tcr"><?php echo $actions ?></td>
				</tr>
<?php
		}
		}
	}
	else
	{
		echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">No match.</td></tr>'."\n";
		$button_status = ' disabled';
	}
?>
			</tbody>
			</table>
		</div>
	</div>
</div>
<div class="postlinksb">
<div class="inbox">
<p class="conr"><input type="submit" name="delete_users_comply" value="Mass Delete" <?php echo $button_status ?>/></p>
</div>
</div>
</form>

<div class="linksb">
	<div class="inbox">
		<div><a href="javascript:history.go(-1)">Go back</a></div>
	</div>
</div>


<?php

	require PUN_ROOT.'footer.php';
}


else
{
	$page_title = 'Admin | Users | '.pun_htmlspecialchars($pun_config['o_board_title']);
	$focus_element = ['find_user', 'username'];
	require PUN_ROOT.'header.php';

	generate_admin_menu('users');

?>
	<div class="blockform">
		<h2><span>User search</span></h2>
		<div class="box">
			<form id="find_user" method="post" action="admin_users.php?action=find_user">
				<p class="submittop"><input type="submit" name="find_user" value="Submit search" tabindex="1" /></p>
				<div class="inform">
					<fieldset>
						<legend>Enter search criteria</legend>
						<div class="infldset">
							<p>Search for users in the database. You can enter one or more terms to search for. Wildcards in the form of asterisks (*) are accepted.</p>
							<table  class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">Username</th>
									<td><input type="text" name="username" size="25" maxlength="25" tabindex="2" /></td>
								</tr>
								<tr>
									<th scope="row">E-mail address</th>
									<td><input type="text" name="form[email]" size="30" maxlength="50" tabindex="3" /></td>
								</tr>
								<tr>
									<th scope="row">Title</th>
									<td><input type="text" name="form[title]" size="30" maxlength="50" tabindex="4" /></td>
								</tr>
								<tr>
									<th scope="row">Real name</th>
									<td><input type="text" name="form[realname]" size="30" maxlength="40" tabindex="5" /></td>
								</tr>
								<tr>
									<th scope="row">Website</th>
									<td><input type="text" name="form[url]" size="35" maxlength="100" tabindex="6" /></td>
								</tr>
								<tr>
									<th scope="row">ICQ</th>
									<td><input type="text" name="form[icq]" size="12" maxlength="12" tabindex="7" /></td>
								</tr>
								<tr>
									<th scope="row">MSN Messenger</th>
									<td><input type="text" name="form[msn]" size="30" maxlength="50" tabindex="8" /></td>
								</tr>
								<tr>
									<th scope="row">AOL IM</th>
									<td><input type="text" name="form[aim]" size="20" maxlength="20" tabindex="9" /></td>
								</tr>
								<tr>
									<th scope="row">Yahoo! Messenger</th>
									<td><input type="text" name="form[yahoo]" size="20" maxlength="20" tabindex="10" /></td>
								</tr>
								<tr>
									<th scope="row">Location</th>
									<td><input type="text" name="form[location]" size="30" maxlength="30" tabindex="11" /></td>
								</tr>
								<tr>
									<th scope="row">Signature</th>
									<td><input type="text" name="form[signature]" size="35" maxlength="512" tabindex="12" /></td>
								</tr>
								<tr>
									<th scope="row">Admin note</th>
									<td><input type="text" name="form[admin_note]" size="30" maxlength="30" tabindex="13" /></td>
								</tr>
								<tr>
									<th scope="row">Number of posts greater than</th>
									<td><input type="text" name="posts_greater" size="5" maxlength="8" tabindex="14" /></td>
								</tr>
								<tr>
									<th scope="row">Number of posts less than</th>
									<td><input type="text" name="posts_less" size="5" maxlength="8" tabindex="15" /></td>
								</tr>
								<tr>
									<th scope="row">Last post is after</th>
									<td><input type="text" name="last_post_after" size="24" maxlength="19" tabindex="16" />
									<span>(yyyy-mm-dd hh:mm:ss)</span></td>
								</tr>
								<tr>
									<th scope="row">Last post is before</th>
									<td><input type="text" name="last_post_before" size="24" maxlength="19" tabindex="17" />
									<span>(yyyy-mm-dd hh:mm:ss)</span></td>
								</tr>
								<tr>
									<th scope="row">Registered after</th>
									<td><input type="text" name="registered_after" size="24" maxlength="19" tabindex="18" />
									<span>(yyyy-mm-dd hh:mm:ss)</span></td>
								</tr>
								<tr>
									<th scope="row">Registered before</th>
									<td><input type="text" name="registered_before" size="24" maxlength="19" tabindex="19" />
									<span>(yyyy-mm-dd hh:mm:ss)</span></td>
								</tr>
								<tr>
									<th scope="row">Matched spam email</th>
									<td><input type="checkbox" name="spam_email_match" value="1"/></td>
								</tr>
								<tr>
									<th scope="row">Matched spam ip</th>
									<td><input type="checkbox" name="spam_ip_match" value="1"/></td>
								</tr>
								<tr>
									<th scope="row">Validate spam online</th>
									<td><input type="checkbox" name="spam_online_match" value="1"/></td>
								</tr>
								<tr>
									<th scope="row">Re-validate email</th>
									<td><input type="checkbox" name="email_validate" value="1"/></td>
								</tr>
								<tr>
									<th scope="row">Order by</th>
									<td>
										<select name="order_by" tabindex="20">
											<option value="username" selected="selected">username</option>
											<option value="email">e-mail</option>
											<option value="num_posts">posts</option>
											<option value="last_post">last post</option>
											<option value="registered">registered</option>
										</select>&nbsp;&nbsp;&nbsp;<select name="direction" tabindex="21">
											<option value="ASC" selected="selected">ascending</option>
											<option value="DESC">descending</option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">User group</th>
									<td>
										<select name="user_group" tabindex="22">
												<option value="all">All groups</option>
<?php

	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups WHERE g_id!='.PUN_GUEST.' ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

	while ($cur_group = $db->fetch_assoc($result))
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.pun_htmlspecialchars($cur_group['g_title']).'</option>'."\n";
	echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.PUN_UNVERIFIED.'" selected="selected">Not Verified</option>'."\n";

?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">Search limit</th>
									<td><input type="text" name="search_limit" size="5" maxlength="8" tabindex="14" value="50" />
									<span>(blank - no limit, not recommended)</span></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="find_user" value="Submit search" tabindex="23" /></p>
			</form>
		</div>

		<h2 class="block2"><span>IP search</span></h2>
		<div class="box">
			<form method="get" action="admin_users.php">
				<div class="inform">
					<fieldset>
						<legend>Enter IP to search for</legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row">IP address<div><input type="submit" value=" Find " tabindex="25" /></div></th>
									<td><input type="text" name="show_users" size="18" maxlength="15" tabindex="24" />
									<span>The IP address to search for in the post database.</span></td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>
<?php

	require PUN_ROOT.'footer.php';
}
