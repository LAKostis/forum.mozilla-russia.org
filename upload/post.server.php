<?php
define('PUN_ROOT', './');
require_once PUN_ROOT.'include/common.php';
require_once PUN_ROOT.'include/parser.php';
require_once PUN_ROOT.'lang/'.$pun_user['language'].'/post.php';

function buildpreviewdivcontent($message)
{
    global $lang_post;
    $previewdiv = "<div id=\"postpreview\" class=\"blockpost\">";
    $previewdiv .= "<h2><span>".$lang_post['Post preview']."</span></h2>";
    $previewdiv .= "<div class=\"box\"><div class=\"inbox\"><div class=\"postright\">";
    $previewdiv .= "<div class=\"postmsg\">".$message."</div>";
    $previewdiv .= "</div></div></div>";
    $previewdiv .= "</div>";
    return $previewdiv;
}

function builderrordivcontent(&$errors)
{
    global $lang_post;
    $errordiv = "<div id=\"posterror\" class=\"block\">";
    $errordiv .= "<h2><span>".$lang_post['Post errors']."</span></h2>";
    $errordiv .= "<div class=\"box\"><div class=\"inbox\">";
    $errordiv .= "<p>".$lang_post['Post errors info']."</p><ul>";
    while (list(, $cur_error) = each($errors))
		$errordiv .= '<li><strong>'.$cur_error.'</strong></li>';
    $errordiv .= "</ul></div></div></div>";
    return $errordiv;
}

function getpreview($postform)
{
	global $db, $pun_user, $pun_config, $lang_post;
	$errors = array();
	$message = pun_linebreaks(trim($postform['req_message']));
	if(get_magic_quotes_gpc())
		$message = stripslashes($message);

	if ($message == '')
		$errors[] = $lang_post['No message'];
	else if (strlen($message) > 65535)
		$errors[] = $lang_post['Too long message'];
	else if ($pun_config['p_message_all_caps'] == '0' && pun_strtoupper($message) == $message && $pun_user['g_id'] > PUN_MOD)
		$message = pun_ucwords(pun_strtolower($message));

    // Validate BBCode syntax
	if ($pun_config['p_message_bbcode'] == '1' && strpos($message, '[') !== false && strpos($message, ']') !== false)
	{
		$message = preparse_bbcode($message, $errors);
	}
    
    $objResponse = new xajaxResponse();
    if(!empty($errors))
    {
        $objResponse->addAssign("ajaxpostpreview", "innerHTML", builderrordivcontent($errors));
    }
    else
    {
        $hide_smilies = isset($postform['hide_smilies']) ? 1 : 0;
        $message = parse_message($message, $hide_smilies);
        $objResponse->addAssign("ajaxpostpreview", "innerHTML", buildpreviewdivcontent($message));
    }

	return $objResponse->getXML();
}

require("post.common.php");
$xajax->processRequests();
?>
