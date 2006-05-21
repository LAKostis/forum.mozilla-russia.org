<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

	require $pun_root.'lang/'.$language.'/'.$language.'_pms.php';

	if($pun_config['o_pms_enabled']){ ?>
	<form method="post" action="message_send.php?tid=<?php echo $id ?>">
		<input type="hidden" name="form_sent" value="1">
		<input type="hidden" name="from_profile" value="<?php echo $_GET['id'] ?>">
		<input type="hidden" name="req_username" value="<?php echo $user['username'] ?>">
		<table class="punmain" cellspacing="1" cellpadding="4">
			<tr class="punhead">
				<td class="punhead" colspan="2"><?php echo $lang_pms['Quick message'] ?></td>
			</tr>
			<tr>
				<td class="puncon1right" style="width: 140px; white-space: nowrap">
					<b><?php echo $lang_pms['Subject'] ?></b>&nbsp;&nbsp;
				</td>
				<td class="puncon2">&nbsp;<input type="text" name="req_subject" style="width: 420px" maxlength="100"></td>
			</tr>
			<tr>
				<td class="puncon1right" style="width: 140px; white-space: nowrap">
					<b><?php echo $lang_common['Message'] ?></b>&nbsp;&nbsp;<br><br>
					<a href="help.php#bbcode" target="_blank"><?php echo $lang_common['BBCode'] ?></a>: <?php echo ($pun_config['p_message_bbcode'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
					<a href="help.php#img" target="_blank"><?php echo $lang_common['img tag'] ?></a>: <?php echo ($pun_config['p_message_img_tag'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;<br>
					<a href="help.php#smilies" target="_blank"><?php echo $lang_common['Smilies'] ?></a>: <?php echo ($pun_config['o_smilies'] == '1') ? $lang_common['on'] : $lang_common['off']; ?>&nbsp;&nbsp;
				</td>
				<td class="puncon2">&nbsp;<textarea name="req_message" rows="7" style="width: 420px"></textarea></td>
			</tr>
<?php

	if ($pun_config['o_smilies'] == '1')
	{
		if ($cur_user['smilies'] == '1')
			$checkboxes[] = '<input type="checkbox" name="smilies" value="1" tabindex="'.($cur_index++).'" checked>&nbsp;'.$lang_pms['Show smilies'];
		else
			$checkboxes[] = '<input type="checkbox" name="smilies" value="1" tabindex="'.($cur_index++).'">&nbsp;'.$lang_pms['Show smilies'];
	}

	$checkboxes[] = '<input type="checkbox" name="savemessage" value="1" tabindex="'.($cur_index++).'">&nbsp;'.$lang_pms['Save message'];

	if (isset($checkboxes))
		$checkboxes = implode('<br>'."\n\t\t\t\t", $checkboxes)."\n";
?>	
			<tr>
				<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php echo $lang_common['Options'] ?>&nbsp;&nbsp;</td>
				<td class="puncon2">
<?php echo $checkboxes ?>
				</td>
			</tr>
			<tr>
				<td class="puncon1right" style="width: 140px; white-space: nowrap"><?php echo $lang_common['Actions'] ?>&nbsp;&nbsp;</td>
				<td class="puncon2"><br>&nbsp;&nbsp;<input type="submit" name="submit" value="<?php echo $lang_pms['Send'] ?>" accesskey="s"><br><br></td>
			</tr>
		</table>
	</form>

<?php 	} ?>
