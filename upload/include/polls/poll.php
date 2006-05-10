<?php
if (!defined('PUN_ROOT'))
	exit;

if ($fid)
{
	if ($ptype == 0) {
		$form = '<form id="poll" method="post" action="post.php?&amp;fid=' . $fid . '">';

		require PUN_ROOT . 'header.php';
	?>
	<div class="blockform">
<h2><span><?php echo $action ?></span></h2>
	<div class="box">
<?php echo $form . "\n" ?>
	<div class="inform">
	<fieldset>
<legend><?php echo $lang_polls['Poll select'] ?></legend>
	<div class="infldset">
<center><select tabindex="<?php echo $cur_index++ ?>" name="ptype">
<option value="1"><?php echo $lang_polls['Regular'] ?>
<option value="2"><?php echo $lang_polls['Multiselect'] ?>
<option value="3"><?php echo $lang_polls['Yesno'] ?>
	</select></center>
	</div>
	</fieldset>
	</div>
<p><center><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" tabindex="<?php echo $cur_index++ ?>" accesskey="s" />&nbsp;<a href="javascript:history.go(-1)"><?php echo $lang_common['Go back'] ?></a></center></p>
	</form>
	</div>
	</div>

	<?php
	} elseif ($ptype == 1 || $ptype == 2 || $ptype == 3) {

                require PUN_ROOT.'include/polls/postpoll.php';

		$required_fields = array('req_email' => $lang_common['E-mail'], 'req_question' => $lang_polls['Question'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
		$focus_element = array('post');

		if (!$pun_user['is_guest'])
		$focus_element[] = 'req_question';
		else {
			$required_fields['req_username'] = $lang_post['Guest name'];
			$focus_element[] = 'req_question';
		} 

		require PUN_ROOT . 'header.php';

	?>
	<div class="linkst">
	<div class="inbox">
	<ul><li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li><li>&nbsp;&raquo;&nbsp;<?php echo $forum_name ?>
	</li></ul>
	</div>
	</div>

	<?php 
		// If there are errors, we display them
		if (!empty($errors)) {

		?>
		<div id="posterror" class="block">
	<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
		<div class="box">
		<div class="inbox">
	<p><?php echo $lang_post['Post errors info'] ?></p>
		<ul>
		<?php

			while (list(, $cur_error) = each($errors))
			echo "\t\t\t\t" . '<li><strong>' . $cur_error . '</strong></li>' . "\n";

		?>
		</ul>
		</div>
		</div>
		</div>

		<?php

		} else if (isset($_POST['preview'])) {
			require_once PUN_ROOT . 'include/parser.php';
			$preview_message = parse_message($message, $hide_smilies);

		?>
		<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_polls['Poll preview'] ?></span></h2>
		<div class="box">
		<div class="inbox">
		<div class="postright">
		<div class="postmsg">
		<?php
			if ($ptype == 1) {

			?><strong>
			<?php echo pun_htmlspecialchars($question);

			?>
			</strong>	<br /><br />
			<form action="" method="POST">
			<?php
				while (list($key, $value) = each($option)) {
					if (!empty($value)) {

					?>
					<input type="radio" /> <?php echo pun_htmlspecialchars($value);

					?> <br />
					<?php
					} 
				} 

			?>
			</form>
			<?php
			} elseif ($ptype == 2) {

			?><strong>
			<?php echo pun_htmlspecialchars($question);

			?>
			</strong><br /><br />
			<form action="" method="POST">
			<?php
				while (list($key, $value) = each($option)) {
					if (!empty($value)) {

					?>
					<input type="checkbox" /> <?php echo pun_htmlspecialchars($value);

					?> <br />
					<?php
					} 
				} 

			?>
			</form>
			<?php
			} elseif ($ptype == 3) {

			?><strong>
			<?php echo pun_htmlspecialchars($question);

			?></strong>
			<br /><br />
			<form action="" method="POST">
			<?php
				while (list($key, $value) = each($option)) {
					if (!empty($value)) {

					?>
					<strong>
					<?php echo pun_htmlspecialchars($value);

					?></strong><br /><input type="radio" /> <?php echo pun_htmlspecialchars($yesval);

					?><input type="radio" /> <?php echo pun_htmlspecialchars($noval);

					?><br />
					<?php
					} 
				} 

			?>
			</form>
			<?php
			} 

		?>
		</div>
		</div>
		</div>
		</div>
		</div>
		<div id="postpreview" class="blockpost">
	<h2><span><?php echo $lang_post['Post preview'] ?></span></h2>
		<div class="box">
		<div class="inbox">
		<div class="postright">
		<div class="postmsg">
	<?php echo $preview_message . "\n" ?>
		</div>
		</div>
		</div>
		</div>
		</div>

		<?php

		} 

		// Regular Poll Type
		if ($ptype == 1) {

		?>
		<div class="blockform">
	<h2><span><?php echo $action ?></span></h2>
		<div class="box">
	<?php echo $form . "\n" ?>
		<div class="inform">
		<fieldset>
	<legend><?php echo $lang_polls['New poll legend'] ?></legend>
		<div class="infldset">
		<input type="hidden" name="ptype" value="1" />
	<label><strong><?php echo $lang_polls['Question'] ?></strong><br /><input type="text" name="req_question" value="<?php if (isset($_POST['req_question'])) echo pun_htmlspecialchars($question);

	?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
		<?php
			for ($x = 1; $x <= $pun_config['poll_max_fields'] ;$x++) {

			?>
		<label><strong><?php echo $lang_polls['Option'] ?></strong><br /> <input type="text" name="poll_option[<?php echo $x;

		?>]" value="<?php if (isset($_POST['poll_option'][$x])) echo pun_htmlspecialchars($option[$x]);

		?>" size="60" maxlength="55" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
			<?php
			} 

		?> </div> </fieldset> </div></div></div> <?php 
			// Multiselect poll type
		} elseif ($ptype == 2) {

		?>


		<div class="blockform">
	<h2><span><?php echo $action ?></span></h2>
		<div class="box">
	<?php echo $form . "\n" ?>
		<div class="inform">
		<fieldset>
	<legend><?php echo $lang_polls['New poll legend multiselect'] ?></legend>
		<div class="infldset">
		<input type="hidden" name="ptype" value="2" />
	<label><strong><?php echo $lang_polls['Question'] ?></strong><br /><input type="text" name="req_question" value="<?php if (isset($_POST['req_question'])) echo pun_htmlspecialchars($question);

	?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
		<?php
			for ($x = 1;
			$x <= $pun_config['poll_max_fields']; $x++) {

			?>
		<label><strong><?php echo $lang_polls['Option'] ?></strong><br /> <input type="text" name="poll_option[<?php echo $x;

		?>]" value="<?php if (isset($_POST['poll_option'][$x])) echo pun_htmlspecialchars($option[$x]);

		?>" size="60" maxlength="55" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
			<?php
			} 

		?> </div> </fieldset> </div></div></div> <?php 
			// Multiselect Yes/No poll type
		} elseif ($ptype == 3) {

		?>


		<div class="blockform">
	<h2><span><?php echo $action ?></span></h2>
		<div class="box">
	<?php echo $form . "\n" ?>
		<div class="inform">
		<fieldset>
	<legend><?php echo $lang_polls['New poll legend yesno'] ?></legend>
		<div class="infldset">
		<input type="hidden" name="ptype" value="3" />
	<label><strong><?php echo $lang_polls['Question'] ?></strong><br /><input type="text" name="req_question" value="<?php if (isset($_POST['req_question'])) echo pun_htmlspecialchars($question);

	?>" size="80" maxlength="70" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
	<label><strong><?php echo $lang_polls['Yes'] ?></strong><br /> <input type="text" name="poll_yes" value="<?php if (isset($_POST['poll_yes'])) echo pun_htmlspecialchars($yesval);

	?>" size="40" maxlength="35" tabindex="<?php echo $cur_index++ ?>" /></label>
	<label><strong><?php echo $lang_polls['No'] ?></strong><br /> <input type="text" name="poll_no" value="<?php if (isset($_POST['poll_no'])) echo pun_htmlspecialchars($noval);

	?>" size="40" maxlength="35" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
		<?php
			for ($x = 1; $x <= $pun_config['poll_max_fields']; $x++) {

			?>
		<label><strong><?php echo $lang_polls['Option'] ?></strong><br /> <input type="text" name="poll_option[<?php echo $x;

		?>]" value="<?php if (isset($_POST['poll_option'][$x])) echo pun_htmlspecialchars($option[$x]);

		?>" size="60" maxlength="55" tabindex="<?php echo $cur_index++ ?>" /><br /></label>
			<?php
			} 

		?> </div> </fieldset> </div></div></div> <?php
		} else
		message($lang_common['Bad request']);

	} else
	message($lang_common['Bad request']);
} else {
	$page_title = pun_htmlspecialchars($action).' | '.pun_htmlspecialchars($pun_config['o_board_title']);
	$required_fields = array('req_email' => $lang_common['E-mail'], 'req_question' => $lang_polls['Question'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
	$focus_element = array('post');
	if (!$pun_user['is_guest'])
	$focus_element[] = ($fid) ? 'req_subject' : 'req_message';
	else
	{
		$required_fields['req_username'] = $lang_post['Guest name'];
		$focus_element[] = 'req_username';
	}

	require PUN_ROOT.'header.php';

?>
<div class="linkst">
<div class="inbox">
		<ul><li><a href="index.php"><?php echo $lang_common['Index'] ?></a></li><li>&nbsp;&raquo;&nbsp;<?php echo $forum_name ?><?php if (isset($cur_posting['subject'])) echo '</li><li>&nbsp;&raquo;&nbsp;'.pun_htmlspecialchars($cur_posting['subject']) ?></li></ul>
			</div>
			</div>

			<?php

				// If there are errors, we display them
				if (!empty($errors))
				{

				?>
				<div id="posterror" class="block">
			<h2><span><?php echo $lang_post['Post errors'] ?></span></h2>
				<div class="box">
				<div class="inbox">
			<p><?php echo $lang_post['Post errors info'] ?></p>
				<ul>
				<?php

					while (list(, $cur_error) = each($errors))
					echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
				?>
				</ul>
				</div>
				</div>
				</div>

				<?php

				}
				else if (isset($_POST['preview']))
				{
					require_once PUN_ROOT.'include/parser.php';
					$message = parse_message($message, $hide_smilies);

				?>
				<div id="postpreview" class="blockpost">
			<h2><span><?php echo $lang_post['Post preview'] ?></span></h2>
				<div class="box">
				<div class="inbox">
				<div class="postright">
				<div class="postmsg">
			<?php echo $preview_message."\n" ?>
				</div>
				</div>
				</div>
				</div>
				</div>
				<?php } 


}

?>
