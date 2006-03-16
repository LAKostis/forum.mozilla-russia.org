<?php
/***********************************************************************

  Copyright (C) 2002-2005 Connorhd

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

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);


if (isset($_POST['save']))
{

foreach ($_POST['wikiperm'] as $group => $wiki_level) 
   $db->query('UPDATE '.$db->prefix.'groups SET g_wiki_level=\''.intval($wiki_level).'\' WHERE g_id='.$group) or error('Unable to update group', __FILE__, __LINE__, $db->error());

redirect('admin_loader.php?plugin=AP_Wiki.php','Permissions Updated');

}
else
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div class="block">
		<h2><span>Wiki Plugin</span></h2>
		<div class="box">
			<div class="inbox">
				<p>This plugin allows you to set Wiki permissions.</p>
			</div>
		</div>
	</div>
	<div class="blockform">
		<h2 class="block2"><span>Permissions</span></h2>
		<div class="box">
			<form id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>&amp;foo=bar">
				<div class="inform">
					<fieldset>
						<legend>Set Permissions for each usergroup.</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
<?php

$result = $db->query('SELECT g_id, g_title, g_wiki_level FROM '.$db->prefix.'groups ORDER BY g_id') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());

while ($cur_group = $db->fetch_assoc($result))
{
?>
	<tr><th scope="row"><?php echo pun_htmlspecialchars($cur_group['g_title']) ?></th><td>
	<input type="radio" name="wikiperm[<?php echo $cur_group['g_id'] ?>]" value="0"<?php if ($cur_group['g_wiki_level'] == '0') echo " checked=\"checked\"" ?> />&nbsp;<strong>None</strong>&nbsp;&nbsp;&nbsp;
	<input type="radio" name="wikiperm[<?php echo $cur_group['g_id'] ?>]" value="1"<?php if ($cur_group['g_wiki_level'] == '1') echo " checked=\"checked\"" ?> />&nbsp;<strong>Read</strong>&nbsp;&nbsp;&nbsp;
	<input type="radio" name="wikiperm[<?php echo $cur_group['g_id'] ?>]" value="2"<?php if ($cur_group['g_wiki_level'] == '2') echo " checked=\"checked\"" ?> />&nbsp;<strong>Edit</strong>&nbsp;&nbsp;&nbsp;
	<input type="radio" name="wikiperm[<?php echo $cur_group['g_id'] ?>]" value="4"<?php if ($cur_group['g_wiki_level'] == '4') echo " checked=\"checked\"" ?> />&nbsp;<strong>Create</strong>&nbsp;&nbsp;&nbsp;
	<input type="radio" name="wikiperm[<?php echo $cur_group['g_id'] ?>]" value="8"<?php if ($cur_group['g_wiki_level'] == '8') echo " checked=\"checked\"" ?> />&nbsp;<strong>Upload</strong>&nbsp;&nbsp;&nbsp;
	<input type="radio" name="wikiperm[<?php echo $cur_group['g_id'] ?>]" value="255"<?php if ($cur_group['g_wiki_level'] == '255') echo " checked=\"checked\"" ?> />&nbsp;<strong>Admin</strong></td></tr>
<?php
}
?>
						</table>
						
						</div>
					</fieldset>
				</div>
			<p class="submitend"><input type="submit" name="save" value="Save changes" /></p>
			</form>
		</div>
	</div>
<?php

}
