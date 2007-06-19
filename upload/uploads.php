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

define('PUN_ROOT','./');

require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

require PUN_ROOT.'include/parser.php';

$page_title='&nbsp;&raquo;&nbsp; Uploader | '.pun_htmlspecialchars($pun_config['o_board_title']);
// Load the viewtopic.php language file
require PUN_ROOT.'lang/'.$pun_user['language'].'/'.'topic.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/'.'uploads.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/search.php';


// FIXME
$files_per_page = 30;

$filename = (isset($_GET['filename'])) ? $_GET['filename'] : '';
$sort_by = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'user' ) ? 'file' : $_GET['sort_by'];
$sort_dir = (!isset($_GET['sort_dir']) || $_GET['sort_dir'] != 'ASC' && $_GET['sort_dir'] != 'DESC') ? 'ASC' : strtoupper($_GET['sort_dir']);

// Create any SQL for the WHERE clause
$where_sql = array();
$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

if ($pun_user['g_search_users'] == '1' && $filename != '') {
	$where_sql[] = 'file '.$like_command.' \''.$db->escape(str_replace('*', '%', $filename)).'\'';
	$where_sql[] = 'user '.$like_command.' \''.$db->escape(str_replace('*', '%', $filename)).'\'';
}

// Get uploads count
$result = $db->query('SELECT count(*) FROM '.$db->prefix.'uploaded '.(!empty($where_sql) ? ' WHERE '.implode(' OR ', $where_sql) : '').'') or error('Unable to count files', __FILE__, __LINE__, $db->error());
list($num_files) = $db->fetch_row($result);

//What page are we on?
$num_pages = ceil($num_files / $files_per_page);
$p = (!isset($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
$start_from = $files_per_page * ($p - 1);
$limit = $start_from.','.$files_per_page;

require PUN_ROOT.'header.php';
?>

<div class="blockform">
	<h2><span><?php echo 'Uploader Search' ?></span></h2>
	<div class="box">
	<form id="userlist" method="get" action="uploads.php">
		<div class="inform">
			<fieldset>
				<div class="infldset">
					<label class="conl"><?php echo $lang_uploads['File'] ?><br /><input type="text" name="filename" value="<?php echo pun_htmlspecialchars($filename) ?>" size="25" maxlength="255" /><br /></label>
					<label class="conl"><?php echo $lang_search['Sort by']."\n" ?>
					<br /><select name="sort_by">
						<option value="file"<?php if ($sort_by == 'file') echo ' selected="selected"' ?>><?php echo $lang_uploads['File'] ?></option>
						<option value="user"<?php if ($sort_by == 'user') echo ' selected="selected"' ?>><?php echo $lang_common['User list'] ?></option>
						</select>
					<br /></label>
					<label class="conl"><?php echo $lang_search['Sort order']."\n" ?>
					<br /><select name="sort_dir">
						<option value="ASC"<?php if ($sort_dir == 'ASC') echo ' selected="selected"' ?>><?php echo $lang_search['Ascending'] ?></option>
						<option value="DESC"<?php if ($sort_dir == 'DESC') echo ' selected="selected"' ?>><?php echo $lang_search['Descending'] ?></option>
					</select>
					<br /></label>
					<p class="clearb"><?php echo $lang_uploads['File search info'] ?></p>
				</div>
			</fieldset>
		</div>
		<p><input type="submit" name="search" value="<?php echo $lang_common['Submit'] ?>" accesskey="s" /></p>
	</form>
	</div>
</div>
<div class="linkst">
	<div class="inbox">
		<p class="pagelink conl"><?php echo $lang_common['Pages'].': '.paginate($num_pages, $p, 'uploads.php?filename='.urlencode($filename).'&amp;sort_by='.$sort_by.'&amp;sort_dir='.strtoupper($sort_dir)); ?></p>
		<p class="postlink conr"><?php if ((!$pun_user['is_guest']) || $pun_user['g_id'] <PUN_GUEST) echo '<a href="upload.php">'.$lang_uploads['New file upload'].'</a>' ?></p>
		<ul><li><a href="index.php"><?php echo $lang_common['Index'] ?></a>&nbsp;</li><li>&raquo;&nbsp;<a href="uploads.php"><?php echo $lang_uploads['Uploader'] ?></a></li></ul>
		<div class="clearer"></div>
	</div>
</div>

<?php
    $result = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id='.$pun_user['g_id']); 
	$upl_conf= $db->fetch_assoc($result);
    if (!$upl_conf) {
    	$result = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id=0');    	
    	$upl_conf= $db->fetch_assoc($result);
    }	
    
   
 	$result = $db->query('SELECT g_id, g_title FROM '.$db->prefix.'groups') or error('Unable to get useergroups', __FILE__, __LINE__, $db->error()); 
 	$i=0;
	while ($i < $db->num_rows($result)) {

		$groups[$i] = $db->fetch_assoc($result);
		$result2 = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id='.$groups[$i]['g_id']) or error('Unable to upload persmissions', __FILE__, __LINE__, $db->error());
		$perms[$i]= $db->fetch_assoc($result2);
    	if (!$perms[$i]) {
    		$result2 = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id=0');    	
    		$perms[$i]= $db->fetch_assoc($result2);
   		}
	 	$i++;   			
	}
	

  $allowed = array(".txt",".gif",".jpg",".jpeg",".png", ".xpi", ".zip", ".src");
  $pics = array(".gif",".jpg",".jpeg",".png");

 ?>
 

<div class="inform">
<?php
	if (!$upl_conf['p_view']) {
	?>
	<div id="announce" class="block">
		<h2><span><b>Not allowed</b></span></h2>
		<div class="box">
			<div class="inbox">
				<div><?php      echo '<b>You do not have permissons to access upload module. Please, contact Administration.</b>'; ?>
				</div>
			</div>
		</div>
	</div>
<?php }
	elseif (!isset($_POST['act'])) { 
	
?>
		<h2><span><?php echo $lang_uploads['File list'] ?></span></h2>
		<fieldset>
			   <div class="infldset">
				<p class="clearb"><?php echo $lang_uploads['File list info'] ?></p>
				<table class="punmain" cellspacing="1" cellpadding="4">
 					<tr class="punhead">
    					<td class="punhead" style="width: 20%"><?php echo $lang_uploads['File']; ?></td>    
    					<td class="punhead" style="width: 5%"><?php echo $lang_uploads['Size']; ?></td>
					<td class="punhead" style="width: 14%"><?php echo $lang_uploads['Posted by']; ?></td>
    <?php
    		if($upl_conf['p_delete'])
	echo '	<td class="punhead" style="width: 14%; white-space: nowrap">'.$lang_uploads['Delete'].'</td>' ?>
		</tr>
		<?php	
   		if($upl_conf['p_globalview']) {
    			$result = $db->query('SELECT * FROM '.$db->prefix.'uploaded WHERE id>1'.(!empty($where_sql) ? ' AND '.implode(' OR ', $where_sql) : '').' ORDER BY '.$sort_by.' '.$sort_dir.' LIMIT '.$start_from.', '.$files_per_page) or error('Error getting file list', __FILE__, __LINE__, $db->error());
    		} else $result = $db->query('SELECT * FROM '.$db->prefix.'uploaded WHERE id ='.$pun_user['id'].''.(!empty($where_sql) ? ' AND '.implode(' OR ', $where_sql) : '').' ORDER BY '.$sort_by.' '.$sort_dir.' LIMIT '.$start_from.', '.$files_per_page) or error('Error getting file list', __FILE__, __LINE__, $db->error());
    		while($info = $db->fetch_assoc($result))    {
	?>
        			<tr class="puntopic">
    <?php
			$ext = strtolower(strrchr($info['file'],'.'));
			if(in_array($ext,$pics))
				echo'					<td class="puncon1"><a href="uploaded/'.$info['file'].'">'.$info['file'].'</td>';
            else
				echo'					<td class="puncon1"><a href="./uploaded/'.$info['file'].'">'.$info['file'].'</td>';
	?>
					<td class="puncon2"><?php echo round(filesize('./uploaded/'.$info['file']) / 1024).'KB'; ?></td>
					<td class="puncon1"><?php echo '<a href="profile.php?id='.$info['id'].'">'.$info['user'].'</a>'; ?></td>
	<?php
			if($upl_conf['p_globaldelete'])
				echo '					<td class="puncon1"><form method="POST" action="uploads.php" enctype="multipart/form-data"><input type="hidden" name="delfile" value="'.$info['file'].'"><input type="submit" name="act" value="Delete"></form></td>';
			elseif ($upl_conf['p_delete']){
				if ($info['id'] == $pun_user['id']) echo '<td class="puncon1"><form method="POST" action="uploads.php" enctype="multipart/form-data"><input type="hidden" name="delfile" value="'.$info['file'].'"><input type="submit" name="act" value="Delete"></form></td>';
				else echo '					<td class="puncon1">N/A</td>';
			}
	?>
					</tr>
	<?php
    		}
	?>
				</table>
			   </div>
		</fieldset>	
</div>
<?php } 
  
elseif (isset($_POST['act']) && pun_trim($_POST['act']) == 'Upload' && isset($_FILES))  {

    setlocale (LC_ALL, 'en_US');
    $temp_name = pun_trim($_FILES['file']['tmp_name']);
    $file_name = pun_trim($_FILES['file']['name']);
    $file_type = pun_trim($_FILES['file']['type']);
    $file_size = intval($_FILES['file']['size']);
    $result    = pun_trim($_FILES['file']['error']); 	
	if(($upl_conf['p_upload'] <> 1)) error('No permission', __FILE__, __LINE__, $db->error());
    $ext = strtolower(strrchr($file_name,'.'));
    if($file_name == "")
      error('No file selected for upload', __FILE__, __LINE__, $db->error());
    else if(file_exists('./uploaded/'.$file_name))
      error('File already exists', __FILE__, __LINE__, $db->error());
    else if($file_size > $upl_conf['u_fsize'])
      error('File was too big', __FILE__, __LINE__, $db->error());
    else if(!preg_match('/[A-Za-z0-9\+\-_\.]+$/',$file_name) || pun_strlen($file_name) > 255)
      error('Invalid file name!', __FILE__, __LINE__, $db->error());
    else if(!in_array($ext,$allowed))
      error('File is not a valid file type', __FILE__, __LINE__, $db->error());
    else {
    	$result = $db->query('INSERT INTO '.$db->prefix.'uploaded(`file`,`user`,`id`) VALUES(\''.$file_name.'\',\''.pun_trim($_POST['user_name']).'\',\''.intval($_POST['user_id']).'\')') or error('Unable to add upload data', __FILE__, __LINE__, $db->error());
    	@copy($temp_name, './uploaded/'.$file_name) or error('Could not copy file to server', __FILE__, __LINE__, $db->error());

?>    	
<div class="inform">
	<fieldset>
		<legend>Uploading...</legend>
		<div class="infldset">
			<div><?php	echo '<b>File has been uploaded to <a href="./uploaded/'.$file_name.'">'.$pun_config['o_base_url'].'/uploaded/'.$file_name.'</a></b>'; ?></div>
		</div>
	</fieldset>
</div>
<?php

    }
}
elseif(isset($_POST['act']) && pun_trim($_POST['act']) == 'Delete' && isset($_POST['delfile'])) {
	
	$delfile = pun_trim($_POST['delfile']);
	if(($upl_conf['p_delete'] <> 1)&&($upl_conf['p_globaldelete'] <> 1)) error('No permission', __FILE__, __LINE__, $db->error());

    if(!file_exists('./uploaded/'.$delfile))
      error('File doesn\'t exist', __FILE__, __LINE__, $db->error());
    else {
    	unlink('./uploaded/'.$delfile);
	$result = $db->query('DELETE FROM '.$db->prefix.'uploaded WHERE file=\''.$db->escape($delfile).'\'') or error('Unable to delete data', __FILE__, __LINE__, $db->error());
?>
<div class="inform">
	<fieldset>
		<legend>Deleting...</legend>
		<div class="infldset">
			<div><?php	echo $delfile.' removed from the uploader.'; ?></div>

		</div>
	</fieldset>
</div>
    
<?php
    }
}

?>

<?php

$footer_style = 'index';
require PUN_ROOT.'footer.php';
