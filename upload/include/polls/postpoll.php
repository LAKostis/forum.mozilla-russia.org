<?php
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

if (!empty($_POST['create_poll'])) 
{
	// Get the question
	$question = pun_trim($_POST['req_question']);
	if ($question == '')
		$errors[] = $lang_polls['No question'];
	else if (pun_strlen($question) > 70)
		$errors[] = $lang_polls['Too long question'];
	else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($question) == $question && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
		$question = ucwords(pun_strtolower($question)); 
	// If its a multislect yes/no poll then we need to make sure they have the right values
	if ($ptype == 3) {
		$yesval = pun_trim($_POST['poll_yes']);

		if ($yesval == '')
			$errors[] = $lang_polls['No yes'];
		else if (pun_strlen($yesval) > 35)
			$errors[] = $lang_polls['Too long yes'];
		else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($yesval) == $yesval && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
			$yesval = ucwords(pun_strtolower($yesval));

		$noval = pun_trim($_POST['poll_no']);

		if ($noval == '')
			$errors[] = $lang_polls['No no'];
		else if (pun_strlen($noval) > 35)
			$errors[] = $lang_polls['Too long no'];
		else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($noval) == $noval && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
			$noval = ucwords(pun_strtolower($noval));
	} 
	// This isn't exactly a good way todo it, but it works. I may rethink this code later
	$option = array();
	$lastoption = "null";
	while (list($key, $value) = each($_POST['poll_option'])) {
		$value = pun_trim($value);
		if ($value != "") {
			if ($lastoption == '')
				$errors[] = $lang_polls['Empty option'];
			else {
				$option[$key] = pun_trim($value);
				if (pun_strlen($option[$key]) > 55)
					$errors[] = $lang_polls['Too long option'];
				else if ($key > $pun_config['poll_max_fields'])
					message($lang_common['Bad request']);
				else if ($pun_config['p_subject_all_caps'] == '0' && strtoupper($option[$key]) == $option[$key] && ($pun_user['g_id'] > PUN_MOD && !$pun_user['g_global_moderation']))
					$option[$key] = ucwords(pun_strtolower($option[$key]));
			} 
		} 
		$lastoption = pun_trim($value);
	} 

	// People are naughty
	if (empty($option))
		$errors[] = $lang_polls['No options'];

	if (!array_key_exists(2,$option))
		$errors[] = $lang_polls['Low options'];
}

?>

