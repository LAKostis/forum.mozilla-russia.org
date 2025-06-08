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


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_main>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <pun_main>


// START SUBST - <pun_footer>
ob_start();

?>
<div id="brdfooter" class="block">
	<h2><span><?php echo $lang_common['Board footer'] ?></span></h2>
	<div class="box">
		<div class="inbox">
<?php

// If no footer style has been specified, we use the default (only copyright/debug info)
$footer_style = isset($footer_style) ? $footer_style : NULL;
require(PUN_ROOT.'include/pms/footer_links.php');
if ($footer_style == 'index' || $footer_style == 'search')
{
	if (!$pun_user['is_guest'])
	{
		echo "\n\t\t\t".'<dl id="searchlinks" class="conl">'."\n\t\t\t\t".'<dt><strong>'.$lang_common['Search links'].'</strong></dt>'."\n";
		echo "\t\t\t\t".'<dd><a href="search.php?action=show_unanswered" class="unanswered">'.$lang_common['Show unanswered posts'].'</a></dd>'."\n";
		echo "\t\t\t\t".'<dd><a href="search.php?action=show_active" class="active">'.$lang_common['Show active posts'].'</a></dd>'."\n";

		if ($pun_config['o_subscriptions'] == '1')
			echo "\t\t\t\t".'<dd><a href="search.php?action=show_subscriptions" class="subscribed">'.$lang_common['Show subscriptions'].'</a></dd>'."\n";

		echo "\t\t\t\t".'<dd><a href="search.php?action=show_user&amp;user_id='.$pun_user['id'].'" class="own">'.$lang_common['Show your posts'].'</a></dd>'."\n\t\t\t".'</dl>'."\n";
	}
	else
	{
		if ($pun_user['g_search'] == '1')
		{
			echo "\n\t\t\t".'<dl id="searchlinks" class="conl">'."\n\t\t\t\t".'<dt><strong>'.$lang_common['Search links'].'</strong></dt>'."\n";
			echo "\t\t\t\t".'<dd><a href="search.php?action=show_unanswered" class="unanswered">'.$lang_common['Show unanswered posts'].'</a></dd>'."\n";
			echo "\t\t\t\t".'<dd><a href="search.php?action=show_active" class="active">'.$lang_common['Show active posts'].'</a></dd>'."\n\t\t\t".'</dl>'."\n";
		}
	}
}

else if ($footer_style == 'viewforum' || $footer_style == 'viewtopic')
{
	echo "\n\t\t\t".'<div class="conl">'."\n";

	// Display the "Jump to" drop list
	if ($pun_config['o_quickjump'] == '1')
	{
		// Load cached quickjump
		@include PUN_ROOT.'cache/cache_quickjump_'.$pun_user['g_id'].'.php';
		if (!defined('PUN_QJ_LOADED'))
		{
			require_once PUN_ROOT.'include/cache.php';
			generate_quickjump_cache($pun_user['g_id']);
			require PUN_ROOT.'cache/cache_quickjump_'.$pun_user['g_id'].'.php';
		}
	}

	if ($footer_style == 'viewforum' && $is_admmod)
		echo "\t\t\t".'<p id="modcontrols"><a href="moderate.php?fid='.$forum_id.'&amp;p='.$p.'" class="admin">'.$lang_common['Moderate forum'].'</a></p>'."\n";
	else if (!$pun_user['is_guest'] && $footer_style == 'viewtopic' && ($is_admmod || $cur_topic['poster'] == $pun_user['username']))
	{
		echo "\t\t\t".'<dl id="modcontrols">';

		if ($is_admmod && $cur_topic['announcement'] != '1')
		{
			echo "\t\t\t".'<dd>';

			if ($cur_topic['closed'] == '1')
				echo '<a href="moderate.php?fid='.$forum_id.'&amp;open='.$id.'" class="open">'.$lang_common['Open topic'].'</a> | ';
			else
				echo '<a href="moderate.php?fid='.$forum_id.'&amp;close='.$id.'" class="close">'.$lang_common['Close topic'].'</a> | ';

			echo '<a href="moderate.php?fid='.$forum_id.'&amp;move_topics='.$id.'" class="move">'.$lang_common['Move topic'].'</a>';

			if ($cur_topic['question'] != '')
				echo ' | <a href="moderate.php?fid='.$forum_id.'&amp;totopic='.$id.'" class="totopic">'.$lang_common['Poll to topic'].'</a>';

			echo '</dd>'."\n";

			if ($cur_topic['sticky'] == '1')
				echo "\t\t\t".'<dd><a href="moderate.php?fid='.$forum_id.'&amp;unstick='.$id.'" class="unstick">'.$lang_common['Unstick topic'].'</a> | '."\n";
			else
				echo "\t\t\t".'<dd><a href="moderate.php?fid='.$forum_id.'&amp;stick='.$id.'" class="stick">'.$lang_common['Stick topic'].'</a> | '."\n";

			if ($cur_topic['post_sticky'] == '1')
				echo "\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;unstick_post='.$id.'" class="unstick">'.$lang_common['Unstick post'].'</a></dd>'."\n";
			else
				echo "\t\t\t".'<a href="moderate.php?fid='.$forum_id.'&amp;stick_post='.$id.'" class="stick">'.$lang_common['Stick post'].'</a></dd>'."\n";

			echo "\t\t\t".'<dt><strong>'.$lang_topic['Mod controls'].'</strong></dt><dd><a href="moderate.php?fid='.$forum_id.'&amp;tid='.$id.'&amp;p='.$p.'" class="admin">'.$lang_common['Delete posts'].'</a></dd>'."\n";
		}
		elseif ($cur_topic['closed'] == '0')
			echo "\t\t\t".'<dd><a href="moderate.php?fid='.$forum_id.'&amp;close='.$id.'" class="close">'.$lang_common['Close topic'].'</a></dd>'."\n";

		echo "\t\t\t".'</dl>';
	}

	echo "\t\t\t".'</div>'."\n";
}

?>
			<p class="conr">Powered by <a href="http://punbb.informer.com/">PunBB</a><?php if ($pun_config['o_show_version'] == '1') echo ' '.$pun_config['o_cur_version']; ?><br />Modified by <a href="http://mozilla-russia.org/">Mozilla Russia</a><br />Copyright © 2004–2025 Mozilla Russia <a href="https://github.com/LAKostis/forum.mozilla-russia.org"><img src="img/GitHub-Mark-32px.png" alt='GitHub mark'/></a><br /><?php

echo $lang_common['Forum language'].': ';
if ($pun_user['language'] == 'Russian')
	echo '<b>[Русский]</b> <a href="index.php?language=English">[English]</a>';
else
	echo '<a href="index.php?language=Russian">[Русский]</a> <b>[English]</b>';

?></p><?php

// Display debug info (if enabled/defined)
if (defined('PUN_DEBUG'))
{
	// Calculate script generation time
	list($usec, $sec) = explode(' ', microtime());
	$time_diff = sprintf('%.3f', (float)$usec + (float)$sec - $pun_start);
	echo "\t\t\t".'<p class="conr">[ Generated in '.$time_diff.' seconds, '.$db->get_num_queries().' queries executed ]</p>'."\n";
}

?>
			<div class="clearer"></div>
		</div>
	</div>
</div>
<?php


// End the transaction
$db->end_transaction();

// Display executed queries (if enabled)
if (defined('PUN_SHOW_QUERIES'))
	display_saved_queries();

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<pun_footer>', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <pun_footer>


// Close the db connection (and free up any result data)
$db->close();

// Spit out the page
exit($tpl_main);
