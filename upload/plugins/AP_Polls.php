<?php
/***********************************************************************

  Caleb Champlin (med_mediator@hotmail.com)

************************************************************************/

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);


// Did someone just hit "Submit"
if (isset($_POST['form_sent']))
{
	$options = intval(pun_trim($_POST['max_options']));
	$db->query('UPDATE '.$db->prefix.'config SET conf_value='.$options.' WHERE conf_name=\'poll_max_fields\'') or error('Unable to update configuration', __FILE__, __LINE__, $db->error());
	$d = dir(PUN_ROOT.'cache');
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(PUN_ROOT.'cache/'.$entry);
	}
	redirect('admin_loader.php?plugin=AP_Polls.php', 'Poll Options Updated');
}
else if (isset($_POST['save']))
{
	// Permission Updating Code here
}
else	// If not, we show the "Show text" form
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div id="pollplugin" class="blockform">
		<h2><span>Poll Options Plugin</span></h2>
		<div class="box">
			<div class="inbox">
				<p>This plugin gives administrators the ability to modify certain aspects of their poll modification.</p>
			</div>
		</div>
		<h2 class="block2"><span>Options</span></h2>
		<div class="box">
		<form id="post" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<div class="inform">
					<fieldset>
						<legend>General</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row">Number of Fields<div><input type="submit" name="form_sent" value="Submit" tabindex="2" /></div></th>
								<td>
									<input type="text" name="max_options" size="4" value="<?php echo $pun_config['poll_max_fields'] ?>" tabindex="1" />
									<span>The number of options you want avaliable for new polls.</span>
								</td>
							</tr>
						</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div> <?php /* POSTPONED
		<h2 class="block2"><span>Disallow new polls</span></h2>
		<div class="box">
		<form id="post" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<p class="submittop"><input type="submit" name="save" value="Save changes" /></p>
				<div class="inform">
					<fieldset>
						<legend>Disallow groups from creating new polls</legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<?php
							$result = $db->query('SELECT g_id, g_title, g_no_polls FROM '.$db->prefix.'groups WHERE g_id>'.PUN_MOD.' ORDER BY g_title') or error('Unable to fetch user group list', __FILE__, __LINE__, $db->error());
							while ($cur_group = $db->fetch_assoc($result))
							{
							?>
							<tr> 
								<th scope="row"><?php echo $cur_group['g_title'] ?></th>
								<td>
									<input type="checkbox" name="disallow[<?php echo $cur_group['g_id'] ?>]" <?php if ($cur_group['g_no_polls'] == 1) echo "CHECKED"; ?>/>
									<span>Prevent this group from creating polls.</span>
								</td>
							</tr>
							<?
							}
							?>
							
						</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
		*/ ?>
		
</div>
<?php

}

// Note that the script just ends here. The footer will be included by admin_loader.php.
