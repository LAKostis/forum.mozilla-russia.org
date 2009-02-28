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
require PUN_ROOT.'include/common.php';

$mgrp_extra = multigrp_getSql($db);

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

// Load the viewtopic.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/topic.php';



// Fetch some info about the topic
if (!$pun_user['is_guest'])
	// MOD: MARK TOPICS AS READ - 1 LINE MODIFIED CODE FOLLOWS
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, t.last_post, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, s.user_id AS is_subscribed FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$db->prefix.'subscriptions AS s ON (t.id=s.topic_id AND s.user_id='.$pun_user['id'].') '.$mgrp_extra.' AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
else
	$result = $db->query('SELECT t.subject, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies, 0 FROM '.$db->prefix.'topics AS t INNER JOIN '.$db->prefix.'forums AS f ON f.id=t.forum_id '.$mgrp_extra.' AND t.id='.$id.' AND t.moved_to IS NULL') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());

if (!$db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $db->fetch_assoc($result);

$page_title = pun_htmlspecialchars($cur_topic['subject']).' | '.pun_htmlspecialchars($pun_config['o_board_title']);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html dir="<?php echo $lang_common['lang_direction']?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_common['lang_encoding']?>" />
<link rel="stylesheet" href="style/imports/printable.css" type="text/css" />
<title><?php echo $page_title ?></title>
</head>
<body>

<table class="links">
<tr><td>
<b>&gt;<?php echo $pun_config['o_board_title'] ?></b><br />&nbsp;&nbsp;&nbsp;<?php echo $pun_config['o_base_url']?>/index.php<br />
<b>&gt;<?php echo $cur_topic['forum_name'] ?></b><br />&nbsp;&nbsp;&nbsp;<?php echo $pun_config['o_base_url']?>/viewforum.php?id=<?php echo $cur_topic['forum_id'] ?><br />
<b>&gt;<?php echo $cur_topic['subject'] ?></b><br />&nbsp;&nbsp;&nbsp;<?php echo $pun_config['o_base_url']?>/viewtopic.php?id=<?php echo $id ?>
</td>
</tr>
</table><br />

<table class="posts">
<tbody>
<?php

require PUN_ROOT.'include/parser.php';

$result = $db->query('SELECT p.poster AS username, p.message, p.hide_smilies, p.posted FROM '.$db->prefix.'posts AS p WHERE p.topic_id='.$id.' ORDER BY p.id ASC') or error('Unable to fetch post info', __FILE__, __LINE__, $db->error());
while ($cur_post = $db->fetch_assoc($result))
{
	$username = pun_htmlspecialchars($cur_post['username']);

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

?>
<tr><td class="links"><b><?php echo $username ?>&nbsp;&gt;&nbsp;<?php echo format_time($cur_post['posted']) ?></b></td></tr>
<tr><td class="border"><?php echo $cur_post['message'] ?></td></tr>
<?php

}

?>

</tbody>
</table>
</body>
</html>
