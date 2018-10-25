<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

require PUN_ROOT . 'lang/' . $pun_user['language'] . '/polls.php'; 
// get the poll data
$result = $db->query('SELECT ptype,options,voters,votes FROM ' . $db->prefix . 'polls WHERE pollid=' . $id . '') or error('Unable to fetch poll info', __FILE__, __LINE__, $db->error());

if ($db->num_rows($result))
{

	$cur_poll = $db->fetch_assoc($result);

	$options = pun_unserialize($cur_poll['options']);
	if (!is_array($options))
		$options = array();
	if (!empty($cur_poll['voters'])) {
		$voters = unserialize($cur_poll['voters']);
		if (!is_array($voters))
			$voters = array();
	} else
		$voters = array();

	$ptype = $cur_poll['ptype']; 
	// yay memory!
	// $cur_poll = null;
	$firstcheck = false;
	?>
	<div class="blockform">
	<h2><span><?php echo $lang_polls['Poll'] ?></span></h2>
	<div class="box">

	<?php
	if ((!$pun_user['is_guest']) && (!in_array($pun_user['id'], $voters)) && ($cur_topic['closed'] == '0')) {
		$showsubmit = true;
		?>
			<form id="poll" method="post" action="vote.php">
			<div class="inform">
			<div class="rbox" align="center">
			<input type="hidden" name="poll_id" value="<?php echo $id; ?>" />
			<input type="hidden" name="form_sent" value="1" />
			<input type="hidden" name="form_user" value="<?php echo (!$pun_user['is_guest']) ? pun_htmlspecialchars($pun_user['username']) : 'Guest';

		?>" /><strong><?php echo pun_htmlspecialchars($cur_topic['question']) ?></strong><br /><br />
			<table style="WIDTH: auto; TABLE-LAYOUT: auto; TEXT-ALIGN: left; BORDER: 0; CELLSPACING: 0; CELLPADDING: 0;">
			<?php
			if ($ptype == 1) {
				while (list($key, $value) = each($options)) {

					?>
						<tr><td style="WIDTH: 10; BORDER: 0;"><input name="vote" <?php if (!$firstcheck) { echo "checked"; $firstcheck = true; }; ?> type="radio" value="<?php echo $key ?>" /></td><td style="BORDER: 0; WIDTH: auto;"><span><?php echo pun_htmlspecialchars($value);

					?></span></td></tr>
						<?php
				} 
			} elseif ($ptype == 2) {
				while (list($key, $value) = each($options)) {
					?>
						<tr><td style="WIDTH: 10; BORDER: 0;"><input name="options[<?php echo $key ?>]" type="checkbox" value="1" /></td><td style="BORDER: 0; WIDTH: auto;"><span><?php echo pun_htmlspecialchars($value);

					?></span></td></tr>
						<?php
				} 
			} elseif ($ptype == 3) {
				while (list($key, $value) = each($options)) {
					?>
						<tr><td style="WIDTH: auto; BORDER: 0;"><?php echo pun_htmlspecialchars($value); ?></td><td style="BORDER: 0; WIDTH: auto;"><input name="options[<?php echo $key ?>]" checked type="radio" value="yes" /> <?php echo $cur_topic['yes']; ?></td><td style="BORDER: 0; WIDTH: auto;"><input name="options[<?php echo $key ?>]" type="radio" value="no" /> <?php echo $cur_topic['no']; ?></td></tr>
						<?php
				} 
			} else
				message($lang_common['Bad request']);
	} else {
		$showsubmit = false;
		?>
			<div class="inform">
			<div class="rbox" align="center">
			<strong><?php echo pun_htmlspecialchars($cur_topic['question']) ?></strong><br /><br />
			<table style="WIDTH: auto; TABLE-LAYOUT: auto; TEXT-ALIGN: left; BORDER: 0; PADDING: 0;">
			<?php
		        if (!empty($cur_poll['votes'])) {
				$votes = unserialize($cur_poll['votes']);
				if (!is_array($votes))
					$votes = array();
			} else
				$votes = array();

		if ($ptype == 1 || $ptype == 2) 
		{
			$total = 0;
			$percent = 0;
			$percent_int = 0;
			while (list($key, $val) = each($options)) 
			{
				if (isset($votes[$key]))
					$total += $votes[$key];
			}
			reset($options);
		}

		while (list($key, $value) = each($options)) {    

			if ($ptype == 1 || $ptype == 2)
			{ 
				if (isset($votes[$key]))
				{
					$percent =  $votes[$key] * 100 / $total;
					$percent_int = floor($percent);
				}
				?>
					<tr><td style="WIDTH: auto; BORDER: 0;"><?php echo pun_htmlspecialchars($value); ?></td> <td style="BORDER: 0; WIDTH: 45%;"><h2 style="width: <?php if (isset($votes[$key])) echo $percent_int; else echo "0"; ?>%; font-size: 1px; height: 2px; margin-bottom: 3px"></h2></td> <td style="BORDER: 0; WIDTH: auto;"> <?php if (isset($votes[$key])) echo $percent_int . "% - " . $votes[$key]; else echo "0% - 0"; ?></td></tr>
					<?php
			}
			else if ($ptype == 3) 
			{ 
				$total = 0;
				$yes_percent = 0;
				$no_percent = 0;
				$vote_yes = 0;
				$vote_no = 0;
				if (isset($votes[$key]['yes']))
				{
					$vote_yes = $votes[$key]['yes'];
				}

				if (isset($votes[$key]['no'])) {
					$vote_no += $votes[$key]['no'];
				}

				$total = $vote_yes + $vote_no;
				if (isset($votes[$key]))
				{
					$yes_percent =   floor($vote_yes * 100 / $total);
					$no_percent = floor($vote_no * 100 / $total);
				}


				?>
					<tr><td style="WIDTH: auto; BORDER: 0;">
					<?php echo pun_htmlspecialchars($value); ?></td>
					<td style="BORDER: 0; WIDTH: auto;"><b><?php echo $cur_topic['yes']; ?></b></td>
					<td style="BORDER: 0; WIDTH: 22%;"><h2 style="width: <?php if (isset($votes[$key])) echo $yes_percent; else echo "0"; ?>%; font-size: 1px; height: 2px; margin-bottom: 3px"></h2></td>
					<td style="BORDER: 0; WIDTH: auto;">
					<?php 
					if (isset($votes[$key]['yes'])) 
						echo $yes_percent . "% - " . $votes[$key]['yes']; 
					else 
						echo "0% - " . 0; 
				?> 
					</td>
					<td style="BORDER: 0; WIDTH: auto;"><b><?php echo $cur_topic['no']; ?></b></td>
					<td style="BORDER: 0; WIDTH: 22%;"><h2 style="width: <?php if (isset($votes[$key])) echo $no_percent; else echo "0"; ?>%; font-size: 1px; height: 2px; margin-bottom: 3px"></h2></td>
					<td style="BORDER: 0; WIDTH: auto;">
					<?php 
					if (isset($votes[$key]['no'])) 
						echo $no_percent . "% - " . $votes[$key]['no']; 
					else 
						echo "0% - " . 0; 
				?> 
					</td>

					</tr>

					</tr>
					<?php }
			else
				message($lang_common['Bad request']);
		} 	
		?><tr>
			<td colspan="7" style="WIDTH: auto; BORDER: 0;">
			<center><?php echo $lang_polls['Total votes'] ?>: <?php echo $total; ?>, <?php echo $lang_polls['null votes'] ?>: <?php echo abs(count($voters) - $total); ?></center>
			</td> <?php 
	} 

?>				</table>
</div>

</div>

<?php if ($showsubmit == true) { ?>
	<p align="center"><input type="submit" name="submit" tabindex="2" value="<?php echo $lang_polls['Vote'] ?>" accesskey="s" /> <input type="submit" name="null" tabindex="2" value="<?php echo $lang_polls['Null vote'] ?>" accesskey="n" /></p>
		<?php } ?>
		</form>
		</div>
		</div>

<?php } ?>
