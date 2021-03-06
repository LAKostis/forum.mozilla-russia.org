<?php

define('PUN_ROOT', './');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/'.'uploads.php';
require PUN_ROOT.'lang/'.$pun_user['language'].'/'.'upload.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

$result = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id='.$pun_user['g_id']); 
$upl_conf= $db->fetch_assoc($result);
if (!$upl_conf) {
	$result = $db->query('SELECT * FROM '.$db->prefix.'uploads_conf WHERE g_id=0');    	
	$upl_conf= $db->fetch_assoc($result);
}	

require PUN_ROOT.'header.php';

if (!$upl_conf['p_view'] || $pun_user['is_guest']) {
	?>
	<div id="announce" class="block">
		<h2><span><b><?php echo $lang_upload['Not allowed'] ?></b></span></h2>
		<div class="box">
			<div class="inbox">
				<div><?php      echo '<b>You do not have permissions to access upload module. Please, contact Administration.</b>'; ?>
				</div>
			</div>
		</div>
	</div>
<?php } else {

?>
		<div class="inform">
		<fieldset>
	<legend><?php echo $lang_uploads['New file upload'] ?></legend>
			<div class="infldset">
				<form method="POST" action="uploads.php" enctype="multipart/form-data">
				<p><b><?php echo $lang_upload['Not allowed'] ?>:</b><br />
					- <?php echo $lang_upload['Rule one'] ?><br>
					- <?php echo $lang_upload['Rule two'] ?> <?php echo round($upl_conf['u_fsize'] / 1024).'KB'; ?> <br>
					- <?php echo $lang_upload['Rule three'] ?>  <br>
					- <?php echo $lang_upload['Rule four'] ?><br><br>
					<input type="file" name="file" size="30"><br><br>
					<input type="hidden" name="user_id" value="<?php echo $pun_user['id']; ?>">
					<input type="hidden" name="user_name" value="<?php echo $pun_user['username']; ?>">
					<input type="hidden" name="act" value="Upload">
					<input type="submit" value="<?php echo $lang_uploads['Upload'] ?>"</p>
				</form>
			</div>
		</fieldset>	
		</div>	
<?php
}
$footer_style = 'index';
require PUN_ROOT.'footer.php';

