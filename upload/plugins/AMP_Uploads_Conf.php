<?php


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

$result = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id='.$pun_user['g_id']); 
$upl_conf= $db->fetch_assoc($result);
if (!$upl_conf) {
	$result = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id=0');    	
	$upl_conf= $db->fetch_assoc($result);
}	


// If the "Show text" button was clicked
if (isset($_POST['save_options']))
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);
	


?>
	<div class="block">
		<h2><span>Upload module configuration</span></h2>
		<div class="box">
			<div class="inbox">
				<p></p>
				<p><a href="javascript: history.go(-1)">Go back</a></p>

	<div class="inform">
		<fieldset>
			<legend>Updating configuration...</legend>
			<div class="infldset">
				<table id="forumperms" cellspacing="0">
				<thead>
					<tr>
						<th class="atcl">&nbsp;</th>
						<th>Access to uploader</th>
						<th>Upload files</th>
						<th>View *all* uploaded files</th>
						<th>Delete files</th>
						<th>Global Moderation</th>
						<th>Set uploader options</th>
						<th>Max upload file size (KB)</th>
					</tr>
				</thead>
				<tbody>
				
				
<?php 	$k=1; while ($k <= $_POST['k']) {  
			if (isset($_POST['p_view_'.$k])) { $p_view[$k] = $_POST['p_view_'.$k]; } else $p_view[$k]=0;
			if (isset($_POST['p_upload_'.$k])) { $p_upload[$k] = $_POST['p_upload_'.$k]; } else $p_upload[$k]=0;
			if (isset($_POST['p_globalview_'.$k])) { $p_globalview[$k] = $_POST['p_globalview_'.$k]; } else $p_globalview[$k]=0;
			if (isset($_POST['p_delete_'.$k])) { $p_delete[$k] = $_POST['p_delete_'.$k]; } else $p_delete[$k]=0;
			if (isset($_POST['p_globaldelete_'.$k])) { $p_globaldelete[$k] = $_POST['p_globaldelete_'.$k]; } else $p_globaldelete[$k]=0;
			if (isset($_POST['p_setop_'.$k])) { $p_setop[$k] = $_POST['p_setop_'.$k]; } else $p_setop[$k]=0;
			if (isset($_POST['u_fsize_'.$k])) { $u_fsize[$k] = $_POST['u_fsize_'.$k]; } else $u_fsize[$k]=0;


?>
				
					<tr>
						<th class="atcl"><?php 	echo 'UPDATE '.$_POST['g_title_'.$k]; ?></th>
						<td>
							<?php 	if ($p_view[$k]==1)  echo '<b>Y</b>'; else echo '<b>N</b>' ?> 
						</td>
						<td>
							<?php 	if ($p_upload[$k]==1)  echo '<b>Y</b>'; else echo '<b>N</b>' ?> 
						</td>
						<td>
							<?php 	if ($p_globalview[$k]==1)  echo '<b>Y</b>'; else echo '<b>N</b>' ?> 
						</td>
						<td>
							<?php 	if ($p_delete[$k]==1)  echo '<b>Y</b>'; else echo '<b>N</b>' ?> 
						</td>
						<td>
							<?php 	if ($p_globaldelete[$k]==1)  echo '<b>Y</b>'; else echo '<b>N</b>' ?> 
						</td>
						<td>
							<?php 	if ($p_setop[$k]==1)  echo '<b>Y</b>'; else echo '<b>N</b>' ?> 						
						</td>
						<td>
							<?php 	echo $u_fsize[$k]; ?> 
						</td>
					</tr>					

<?php 	
		$result2 = $db->query('SELECT g_id FROM '.$db->prefix.'uploads_conf WHERE g_id='.$k); 
		if ($db->fetch_assoc($result2)) {
			$query=('UPDATE '.$db->prefix.'uploads_conf SET p_view = '.$p_view[$k].', p_upload ='.$p_upload[$k].', p_globalview = '.$p_globalview[$k].', p_delete = '.$p_delete[$k].', p_globaldelete = '.$p_globaldelete[$k].', p_setop = '.$p_setop[$k].', u_fsize = '.$u_fsize[$k].'  WHERE g_id='.$k.';');
		} else {
			$query=('INSERT INTO '.$db->prefix.'uploads_conf VALUES ('.$k.', '.$u_fsize[$k].',  '.$p_view[$k].', '.$p_globalview[$k].', '.$p_upload[$k].', '.$p_delete[$k].', '.$p_globaldelete[$k].', '.$p_setop[$k].')');
		}		
		$result = $db->query($query);
		$k++; } //while ?>
													
				</tbody>
		</table>
			</div>
		</fieldset>
	</div>
			</div>
		</div>
	</div>



<?php

}
else	// If no data
{
	// Display the admin navigation menu
	generate_admin_menu($plugin);

?>
	<div id="exampleplugin" class="blockform">
		<h2><span>Upload module options</span></h2>
		<div class="box">
			<div class="inbox">
<?php 	if (!$upl_conf['p_setop'])
		 	{ echo '<p>You do not have permissions to set configuration of this module. Please contact Administration.</p>';}
		else { 


?>

				<p>This plugin edits settings for Upload module.</p>

		
		
<?php
 	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups') or error('Unable to get useergroups', __FILE__, __LINE__, $db->error()); 
 	$i=0;
	while ($i < $db->num_rows($result)) {

		$groups[$i] = $db->fetch_assoc($result);
		$result2 = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id='.$groups[$i]['g_id']) or error('Unable to read upload persmissions', __FILE__, __LINE__, $db->error());
		$perms[$i]= $db->fetch_assoc($result2);
    	if (!$perms[$i]) {
    		$result2 = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id=0');    	
    		$perms[$i]= $db->fetch_assoc($result2);
   		}
	 	$i++;   			
	}

?>

	<div class="inform">
		<form id="example" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
		<fieldset>
			<legend>Edit group permissions for uploads module</legend>
			<div class="infldset">
				<table id="forumperms" cellspacing="0">
				<thead>
					<tr>
						<th class="atcl">&nbsp;</th>
						<th>Access to uploader</th>
						<th>Upload files</th>
						<th>View *all* uploaded files</th>
						<th>Delete files</th>
						<th>Global Moderation</th>
						<th>Set uploader options</th>
						<th>Max upload file size</th>
					</tr>
				</thead>
				<tbody>
				
<?php 	$k=0; foreach ($groups as $group) { ?>
				
					<tr>
						<th class="atcl">
							<?php 	echo $group['g_title'] ?>
							<input type="hidden" name="g_title_<?php 	echo $group['g_id']; echo '" value="'.$group['g_title'].'"'; ?> />
						</th>
						<td><?php  ?>
							<input type="checkbox" name="p_view_<?php 	echo $group['g_id'] ?>" value="1" <?php 	if ($perms[$k]['p_view']==1) echo 'checked="checked"'; ?> />
						</td>
						<td>
							<input type="checkbox" name="p_upload_<?php 	echo $group['g_id'] ?>" value="1" <?php 	if ($perms[$k]['p_upload']==1) echo 'checked="checked"'; ?> />
						</td>
						<td>
							<input type="checkbox" name="p_globalview_<?php 	echo $group['g_id'] ?>" value="1" <?php 	if ($perms[$k]['p_globalview']==1) echo 'checked="checked"'; ?> />
						</td>
						<td>
							<input type="checkbox" name="p_delete_<?php 	echo $group['g_id'] ?>" value="1" <?php 	if ($perms[$k]['p_delete']==1) echo 'checked="checked"'; ?> />
						</td>
						<td>
							<?php 	if ($group['g_id']<>3) { ?>
							<input type="checkbox" name="p_globaldelete_<?php 	echo $group['g_id'] ?>" value="1" <?php 	if ($perms[$k]['p_globaldelete']==1) echo 'checked="checked"'; ?> />
							<?php 	} else { echo '<b>N/A</b>'; ?><input type="hidden" name="p_setop_<?php 	echo $group['g_id']; ?> value="0" /><?php } ?>
						</td>
						<td>
							<?php 	if (($group['g_id']==1)||($group['g_id']==2)) { ?>
							<input type="checkbox" name="p_setop_<?php 	echo $group['g_id'] ?>" value="1" <?php 	if ($perms[$k]['p_setop']==1) echo 'checked="checked"'; ?> />
							<?php 	} else { echo '<b>N/A</b>'; ?><input type="hidden" name="p_setop_<?php 	echo $group['g_id']; ?> value="0" /><?php } ?>							
						</td>
						<td>
							<input type="text" size="7" name="u_fsize_<?php 	echo $group['g_id'] ?>" value="<?php 	echo $perms[$k]['u_fsize'] ?>" />
						</td>
					</tr>					

<?php 	$k++; } //foreach ?>
													
				</tbody>
		</table>
				<div class="fsetsubmit"><input type="submit" name="save_options" value="Save options" /></div>
		</div>
		</fieldset>
			<input type="hidden" name="k" value="<?php 	echo $k; ?>" />
		</form>
	</div>
	
	
	</div>
</div>
<?php
	} //$upl_conf['p_setop']
}

// Note that the script just ends here. The footer will be included by admin_loader.php.
