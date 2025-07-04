<?php
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


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');		// For HTTP/1.0 compability


// Load the template
if (defined('PUN_ADMIN_CONSOLE'))
	$tpl_main = file_get_contents(PUN_ROOT.'include/template/admin.tpl');
else if (defined('PUN_HELP'))
	$tpl_main = file_get_contents(PUN_ROOT.'include/template/help.tpl');
else if (defined('PUN_WIKI'))
	$tpl_main = file_get_contents(PUN_ROOT.'include/template/wiki.tpl');
else
	$tpl_main = file_get_contents(PUN_ROOT.'include/template/main.tpl');


// START SUBST - <pun_include "*">
while (preg_match('#<pun_include "([^/\\\\]*?)\.(php[45]?|inc|html?|txt)">#', $tpl_main, $cur_include))
{
	if (!file_exists(PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2]))
		error('Unable to process user include '.htmlspecialchars($cur_include[0]).' from template main.tpl. There is no such file in folder /include/user/');

	ob_start();
	include PUN_ROOT.'include/user/'.$cur_include[1].'.'.$cur_include[2];
	$tpl_temp = ob_get_contents();
	$tpl_main = str_replace($cur_include[0], $tpl_temp, $tpl_main);
	ob_end_clean();
}
// END SUBST - <pun_include "*">


// START SUBST - <pun_content_direction>
$tpl_main = str_replace('<pun_content_direction>', $lang_common['lang_direction'], $tpl_main);
// END SUBST - <pun_content_direction>


// START SUBST - <pun_char_encoding>
if (defined('PUN_WIKI')) {
	if (!isset($pun_user['language']) || $pun_user['is_guest'])
		require PUN_ROOT.'lang/'.$pun_config['o_default_lang'].'/lang.php';
	else
		require PUN_ROOT.'lang/'.$pun_user['language'].'/lang.php';
	$tpl_main = str_replace('<pun_char_encoding>', $lang['encoding'], $tpl_main);
}
else {
	$tpl_main = str_replace('<pun_char_encoding>', $lang_common['lang_encoding'], $tpl_main);
}
// END SUBST - <pun_char_encoding>

// START SUBST - <rss ..> stuff
$tpl_main = str_replace('<rss new>',$lang_common['RSS Desc New'].' '.$pun_config['o_board_title'], $tpl_main);
$tpl_main = str_replace('<rss active>',$lang_common['RSS Desc Active'].' '.$pun_config['o_board_title'], $tpl_main);
$tpl_main = str_replace('<rss site news>',$lang_common['RSS Site News'].' '.$pun_config['o_board_title'], $tpl_main);
// END SUBST - <rss ..> stuff

// START SUBST - <board_url>
$tpl_main = str_replace('<board_url>',$pun_config['o_base_url'], $tpl_main);
// END SUBST

// START SUBST - <pun_head>
ob_start();

// Is this a page that we want search index spiders to index?
if (!defined('PUN_ALLOW_INDEX'))
	echo '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />'."\n";

if (defined('PUN_WIKI')) {
	require_once("wiki/common.php");
	$ID = str_replace('_',' ',$ID);
	$page_title = pun_htmlspecialchars($conf['title']).' | '.pun_htmlspecialchars($ID).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
?>
<title><?php echo $page_title ?></title>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />
<link rel="stylesheet" type="text/css" href="style/imports/<?php echo $pun_user['style'].'_wiki.css' ?>" />
<?php
	html_head();
}
else {
?>
<title><?php echo $page_title ?></title>
<?php // MOD AJAX post preview
	if(isset($xajax))
	{
		$xajax->printJavascript();
	}
?>
<script type="text/javascript" src="scripts.js?9"></script>
<?php

if (defined('PUN_GOOGLE_API'))
	echo '<script type="text/javascript" src="http://www.google.com/jsapi?autoload=%7B%22modules%22%3A%5B%7B%22name%22%3A%22search%22%2C%22version%22%3A%221%22%2C%22callback%22%3A%22googleSearch%22%2C%22language%22%3A%22ru%22%2C%22nocss%22%3A%22true%22%7D%5D%7D&key=ABQIAAAAWD4huxUNaVOdJ012YX-mnBSpPsyX8dqa9XBaF5Rze7w_stWU3xR2Z0IovGffrviWO3n6CghmFNclZw"></script>'."\n";

?>
<link rel="stylesheet" type="text/css" href="style/<?php echo $pun_user['style'].'.css' ?>" />
<?php
}

if (defined('PUN_ADMIN_CONSOLE'))
	echo '<link rel="stylesheet" type="text/css" href="style/imports/base_admin.css" />'."\n";

if (isset($required_fields))
{
	// Output JavaScript to validate form (make sure required fields are filled out)

?>
<script type="text/javascript">
<!--
function process_form(the_form)
{
	var element_names = new Object()
<?php

	// Output a JavaScript array with localised field names
	foreach ($required_fields as $elem_orig => $elem_trans)
		echo "\t".'element_names["'.$elem_orig.'"] = "'.addslashes(str_replace('&nbsp;', ' ', $elem_trans)).'"'."\n";

?>

	if (document.all || document.getElementById)
	{
		for (i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i]
			if (elem.name && elem.name.substring(0, 4) == "req_")
			{
				if (elem.type && (elem.type=="text" || elem.type=="textarea" || elem.type=="password" || elem.type=="file") && elem.value=='')
				{
					alert("\"" + element_names[elem.name] + "\" <?php echo $lang_common['required field'] ?>")
					elem.focus()
					return false
				}
			}
		}
	}

	return true
}
// -->
</script>
<?php

}

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_head>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <pun_head>


// START SUBST - <body>
if (isset($focus_element))
{
	$tpl_main = str_replace('<body onload="', '<body onload="document.getElementById(\''.$focus_element[0].'\').'.$focus_element[1].'.focus();', $tpl_main);
	$tpl_main = str_replace('<body>', '<body onload="document.getElementById(\''.$focus_element[0].'\').'.$focus_element[1].'.focus()">', $tpl_main);
}
// END SUBST - <body>


// START SUBST - <pun_page>
$tpl_main = str_replace('<pun_page>', htmlspecialchars(basename($_SERVER['PHP_SELF'], '.php')), $tpl_main);
// END SUBST - <pun_page>


// START SUBST - <pun_search>
$tpl_main = str_replace('<pun_search>',

'<input type="button" value="'. $lang_common['Google search'] . '" id="search-google" onclick="return searchGoogle()"/>' .

(defined('TOPIC_ID') ? '<input type="hidden" name="show_as" value="posts" />
			<input type="hidden" name="topic" value="' . TOPIC_ID . '" />
			<input type="submit" value="'. $lang_common['Search topic'] . '" accesskey="g" id="search-submit" />' : (

defined('FORUM_ID') ? '<input type="hidden" name="show_as" value="topics" />
			<input type="hidden" name="forum" value="' . FORUM_ID . '" />
			<input type="submit" value="'. $lang_common['Search forum'] . '" accesskey="g" id="search-submit" />' :

'<input type="hidden" name="show_as" value="topics" />
			<input type="submit" value="'. $lang_common['Search forums'] . '" accesskey="g" id="search-submit" />'))

, $tpl_main);
// END SUBST - <pun_search>


// START SUBST - <pun_title>
$tpl_main = str_replace('<pun_title>', '<h1><span>'.pun_htmlspecialchars($pun_config['o_board_title']).'</span></h1>', $tpl_main);
// END SUBST - <pun_title>


// START SUBST - <pun_desc>
$tpl_main = str_replace('<pun_desc>', '<p><span>'.$pun_config['o_board_desc'].'</span></p>', $tpl_main);
// END SUBST - <pun_desc>


// START SUBST - <pun_navlinks>
$tpl_main = str_replace('<pun_navlinks>','<div id="brdmenu" class="inbox">'."\n\t\t\t". generate_navlinks()."\n\t\t".'</div>', $tpl_main);
// END SUBST - <pun_navlinks>


// START SUBST - <pun_status>
if ($pun_user['is_guest'])
{
	// Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to index.php after login)
	$redirect_url = isset($_SERVER['HTTP_REFERER']) && preg_match('#^'.preg_quote($pun_config['o_base_url']).'/(.*?)\.php#i', $_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php';

	$tpl_temp = '<div id="brdwelcome" class="inbox">'."\n\t\t\t".'<ul class="conl">'."\n\t\t\t\t".'<li><form method="post" action="login.php?action=in"><input type="hidden" name="form_sent" value="1" /> <input type="hidden" name="redirect_url" value="' . $redirect_url .'" /> <input type="text" id="qlogin-username" name="req_username" size="20" maxlength="25" tabindex="101" value="'. $lang_common['Username'] .'" onfocus="this.value=\'\'" /> <input type="password"  id="qlogin-password"name="req_password" size="15" maxlength="16" tabindex="102" value="'. $lang_common['Password'] .'" onfocus="this.value=\'\'" /> <input type="submit" id="qlogin-submit" name="login" value="'. $lang_common['Login'] .'" tabindex="103" /></form></li></ul>'."\n\t\t\t";
	if (basename($_SERVER['PHP_SELF']) == 'viewtopic.php')
		$tpl_temp .= "\n\t\t\t".'<ul class="conr"><li><a href="viewprintable.php?id='.$id.'" class="viewprintable">'.$lang_common['Print version'].'</a></li></ul>'."\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';
	elseif (in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'search.php']))
		$tpl_temp .= "\n\t\t\t".'<ul class="conr">'."\n\t\t\t\t".'<li><a href="search.php?action=show_24h" class="latest">'.$lang_common['Show recent posts'].'</a></li>'."\n\t\t\t".'</ul>'."\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';
	else
		$tpl_temp .= '<div class="clearer"></div></div>';
}
else
{
	$tpl_temp = '<div id="brdwelcome" class="inbox">'."\n\t\t\t".'<ul class="conl">'."\n\t\t\t\t".'<li>'.$lang_common['Logged in as'].' <a href="profile.php?id='.$pun_user['id'].'" class="user"><strong>'.pun_htmlspecialchars($pun_user['username']).'</strong></a></li>'."\n\t\t\t\t".'<li>'.$lang_common['Last visit'].': '.format_time($pun_user['last_visit']).'</li>';

	if ($pun_user['g_id'] < PUN_GUEST)
	{
		$result_header = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'reports WHERE zapped IS NULL') or error('Unable to fetch reports info', __FILE__, __LINE__, $db->error());

		if ($db->result($result_header))
			$tpl_temp .= "\n\t\t\t\t".'<li class="reportlink"><strong><a href="admin_reports.php">'.$lang_common['New reports'].'</a></strong></li>';

		if ($pun_config['o_maintenance'] == '1')
			$tpl_temp .= "\n\t\t\t\t".'<li class="maintenancelink"><strong><a href="admin_options.php#maintenance">Maintenance mode is enabled!</a></strong></li>';
	}
	require(PUN_ROOT.'include/pms/header_new_messages.php');
	if (in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'search.php']))
		$tpl_temp .= "\n\t\t\t".'</ul>'."\n\t\t\t".'<ul class="conr">'."\n\t\t\t\t".'<li><a href="search.php?action=show_new" class="new">'.$lang_common['Show new posts'].'</a></li>'."\n\t\t\t\t".'<li><a href="search.php?action=show_24h" class="latest">'.$lang_common['Show recent posts'].'</a></li>'."\n\t\t\t\t".'<li><a href="misc.php?action=markread" class="markread">'.$lang_common['Mark all as read'].'</a></li>'."\n\t\t\t".'</ul>'."\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';
	// MOD: MARK TOPICS AS READ - 2 LINES NEW CODE FOLLOW
		else if (in_array(basename($_SERVER['PHP_SELF']), ['viewforum.php']) && isset($id))
			$tpl_temp .= "\n\t\t\t".'</ul>'."\n\t\t\t".'<ul class="conr">'."\n\t\t\t\t".'<li><a href="search.php?action=show_new" class="new">'.$lang_common['Show new posts'].'</a></li>'."\n\t\t\t\t".'<li><a href="misc.php?action=markforumread&amp;id='.$id.'" class="markread">'.$lang_common['Mark forum as read'].'</a></li>'."\n\t\t\t".'</ul>'."\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';
	elseif (basename($_SERVER['PHP_SELF']) == 'viewtopic.php')
	{
		$tpl_temp .= "\n\t\t\t".'</ul>'."\n\t\t\t".'<ul class="conr">'."\n\t\t\t\t";

		if ($cur_topic['is_subscribed'])
			$tpl_temp .= '<li>'.$lang_topic['Is subscribed'].' / <a href="misc.php?unsubscribe='.$id.'" class="unsubscribe">'.$lang_topic['Unsubscribe'].'</a></li>';
		else
			$tpl_temp .= '<li>'.'<a href="misc.php?subscribe='.$id.'" class="subscribe">'.$lang_topic['Subscribe'].'</a></li>';

		$tpl_temp .= '<li><a href="viewprintable.php?id='.$id.'" class="viewprintable">'.$lang_common['Print version'].'</a></li></ul>'."\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';

	}
	else $tpl_temp .= '</ul>'."\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';
}
$tpl_main = str_replace('<pun_status>', $tpl_temp, $tpl_main);
// END SUBST - <pun_status>


// START SUBST - <pun_announcement>
if ($pun_config['o_announcement'] == '1')
{
	ob_start();

?>
<div id="announce" class="block">
	<h2><span><?php echo $lang_common['Announcement'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div><?php echo $pun_config['o_announcement_message'] ?></div>
		</div>
	</div>
</div>
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_main = str_replace('<pun_announcement>', $tpl_temp, $tpl_main);
	ob_end_clean();
}
elseif ($pun_config['o_rannouncement'] == '1')
{
	ob_start();

	$pun_rannouncements = explode("\n", $pun_config['o_rannouncement_message']);
	$pun_rannouncement = $pun_rannouncements[mt_rand(0, count($pun_rannouncements) - 1)];

?>
<div id="announce" class="block">
	<h2><span><?php echo $lang_common['Helpful info'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<div><?php echo $pun_rannouncement ?></div>
		</div>
	</div>
</div>
<?php

	$tpl_temp = trim(ob_get_contents());
	$tpl_main = str_replace('<pun_announcement>', $tpl_temp, $tpl_main);
	ob_end_clean();
}
else
	$tpl_main = str_replace('<pun_announcement>', '', $tpl_main);
// END SUBST - <pun_announcement>


// START SUBST - <pun_logo>
	if (defined('PUN_WIKI')){
		$logo_img = 'style/img/'.$pun_user['style'].'/pun_'.$pun_user['style'].'_wiki_logo.png';
		$logo_link = 'doku.php';
	}
else {
	$logo_img = 'style/img/'.$pun_user['style'].'/pun_'.$pun_user['style'].'_forum_logo.png';
	$logo_link = 'index.php';
}

// echo $logo_img;

if (!file_exists(PUN_ROOT.$logo_img))
	$logo_img = 'img/pun_default_logo.png';

$logo_size = @getimagesize(PUN_ROOT.$logo_img);
$tpl_main = str_replace('<pun_logo>','<a href="'.$logo_link.'"><img src="'.$logo_img.'" '.$logo_size[3].' alt="'.$pun_config['o_board_title'].'" /></a>',$tpl_main);

// END SUBST - <pun_logo>

// START SUBST - <pun_main>
ob_start();
//if (defined('PUN_WIKI') && $lang_common['lang_encoding'] != 'iso-8859-1' && $lang_common['lang_encoding'] != 'utf-8')
//	msg('PunDokuWiki cannot support this language as is cannot be converted to utf-8',-1);
//elseif (defined('PUN_WIKI') && $lang['encoding'] == 'utf-8')
//	$tpl_main = utf8_encode($tpl_main);

define('PUN_HEADER', 1);
