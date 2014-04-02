<?php
/**
 * Common DokuWiki functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if (!defined('PUN_ROOT'))
	exit('The constant PUN_ROOT must be defined and point to a valid PunBB installation root directory.');
  require_once PUN_ROOT.'include/utf8/utf8.php';
  require_once PUN_ROOT.'include/utf8/utils/unicode.php';
  require_once PUN_ROOT.'include/utf8/utils/specials.php';
  require_once PUN_ROOT.'wiki/conf/dokuwiki.php';
  require_once PUN_ROOT.'wiki/inc/io.php';
  require_once PUN_ROOT.'wiki/inc/utf8.php';
  require_once PUN_ROOT.'wiki/inc/mail.php';

  //set up error reporting to sane values
  //error_reporting(E_ALL ^ E_NOTICE);

  //make session rewrites XHTML compliant
  ini_set('arg_separator.output', '&amp;');

  //init session
  session_name("DokuWiki");
  session_start();

  //kill magic quotes
  if (get_magic_quotes_gpc()) {
	  if (!empty($_GET))    remove_magic_quotes($_GET);
	  if (!empty($_POST))   remove_magic_quotes($_POST);
	  if (!empty($_COOKIE)) remove_magic_quotes($_COOKIE);
	  if (!empty($_REQUEST)) remove_magic_quotes($_REQUEST);
	  if (!empty($_SESSION)) remove_magic_quotes($_SESSION);
	  ini_set('magic_quotes_gpc', 0);
  }
  if (version_compare(PHP_VERSION, '5.3.0', '<'))
	  set_magic_quotes_runtime(0);
  ini_set('magic_quotes_sybase',0);

  //disable gzip if not available
  if($conf['usegzip'] && !function_exists('gzopen')){
    $conf['usegzip'] = 0;
  }

  //remember original umask
  $conf['oldumask'] = umask();

  //make absolute mediaweb
  if(!preg_match('#^(https?://|/)#i',$conf['mediaweb'])){
    $conf['mediaweb'] = getBaseURL().$conf['mediaweb'];
  }

/**
 * remove magic quotes recursivly
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function remove_magic_quotes(&$array) {
  foreach (array_keys($array) as $key) {
    if (is_array($array[$key])) {
      remove_magic_quotes($array[$key]);
    }else {
      $array[$key] = stripslashes($array[$key]);
    }
  } 
} 

/**
 * Returns the full absolute URL to the directory where
 * DokuWiki is installed in (includes a trailing slash)
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getBaseURL($abs=false){
  global $conf;
  //if canonical url enabled always return absolute
  if($conf['canonical']) $abs = true;

  $dir = dirname($_SERVER['PHP_SELF']).'/';

  $dir = str_replace('\\','/',$dir); #bugfix for weird WIN behaviour
  $dir = preg_replace('#//+#','/',$dir);

  //finish here for relative URLs
  if(!$abs) return $dir;

  $port = ':'.$_SERVER['SERVER_PORT'];
  //remove port from hostheader as sent by IE
  $host = preg_replace('/:.*$/','',$_SERVER['HTTP_HOST']);

  // see if HTTPS is enabled - apache leaves this empty when not available,
  // IIS sets it to 'off', 'false' and 'disabled' are just guessing
  if (preg_match('/^(|off|false|disabled)$/i',$_SERVER['HTTPS'])){
    $proto = 'http://';
    if ($_SERVER['SERVER_PORT'] == '80') {
      $port='';
    }
  }else{
    $proto = 'https://';
    if ($_SERVER['SERVER_PORT'] == '443') {
      $port='';
    }
  }

  return $proto.$host.$port.$dir;
}

/**
 * Return info about the current document as associative
 * array.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function pageinfo(){
  global $ID;
  global $REV;
  global $USERINFO;
  global $conf;
  global $pun_user;

  if($_SERVER['REMOTE_USER']){
    $info['user']     = $_SERVER['REMOTE_USER'];
    $info['userinfo'] = $USERINFO;
    $info['perm']     = auth_quickaclcheck($ID);
  }else{
    $info['user']     = '';
    $info['perm']     = auth_aclcheck($ID,'',null);
  }

  $info['namespace'] = getNS($ID);
  $info['locked']    = checklock($ID);
  $info['filepath']  = realpath(wikiFN($ID,$REV));
  $info['exists']    = @file_exists($info['filepath']);
  if($REV && !$info['exists']){
    //check if current revision was meant
    $cur = wikiFN($ID);
    if(@file_exists($cur) && (@filemtime($cur) == $REV)){
      $info['filepath'] = realpath($cur);
      $info['exists']   = true;
      $REV = '';
    }
  }
  if($info['exists']){
    $info['writable'] = (is_writable($info['filepath']) &&
                         ($info['perm'] >= AUTH_EDIT));
  }else{
    $info['writable'] = ($info['perm'] >= AUTH_CREATE);
  }
  $info['editable']  = ($info['writable'] && empty($info['lock']));
  $info['lastmod']   = @filemtime($info['filepath']);

  //who's the editor
  if($REV){
    $revinfo = getRevisionInfo($ID,$REV);
  }else{
    $revinfo = getRevisionInfo($ID,$info['lastmod']);
  }
  $info['ip']     = $revinfo['ip'];
  $info['user']   = $revinfo['user'];
  $info['sum']    = $revinfo['sum'];
  $info['editor'] = (($pun_user['g_id'] == PUN_ADMIN) || ($pun_user['g_id'] == PUN_MOD)) ? $revinfo['ip'] : '';
  if($revinfo['user'] && !$pun_user['is_guest']) $info['editor'].= ' ('.$revinfo['user'].')';

  return $info;
}

/**
 * print a message
 *
 * If HTTP headers were not sent yet the message is added 
 * to the global message array else it's printed directly
 * using html_msgarea()
 * 
 *
 * Levels can be:
 *
 * -1 error
 *  0 info
 *  1 success
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    html_msgarea
 */
function msg($message,$lvl=0){
  global $MSG;
  $errors[-1] = 'error';
  $errors[0]  = 'info';
  $errors[1]  = 'success';

  if(!headers_sent()){
    if(!isset($MSG)) $MSG = array();
    $MSG[]=array('lvl' => $errors[$lvl], 'msg' => $message);
  }else{
    $MSG = array();
    $MSG[]=array('lvl' => $errors[$lvl], 'msg' => $message);
    html_msgarea();
  }
}

/**
 * This builds the breadcrumb trail and returns it as array
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function breadcrumbs(){
  global $ID;
  global $ACT;
  global $conf;
  $crumbs = $_SESSION[$conf['title']]['bc'];
  
  //first visit?
  if (!is_array($crumbs)){
    $crumbs = array();
  }
  //we only save on show and existing wiki documents
  if($ACT != 'show' || !@file_exists(wikiFN($ID))){
    $_SESSION[$conf['title']]['bc'] = $crumbs;
    return $crumbs;
  }
  //remove ID from array
  $pos = array_search($ID,$crumbs);
  if($pos !== false && $pos !== null){
    array_splice($crumbs,$pos,1);
  }

  //add to array
  $crumbs[] =$ID;
  //reduce size
  while(count($crumbs) > $conf['breadcrumbs']){
    array_shift($crumbs);
  }
  //save to session
  $_SESSION[$conf['title']]['bc'] = $crumbs;
  return $crumbs;
}

/**
 * Filter for page IDs
 *
 * This is run on a ID before it is outputted somewhere
 * currently used to replace the colon with something else
 * on Windows systems and to have proper URL encoding
 *
 * Urlencoding is ommitted when the second parameter is false
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function idfilter($id,$ue=true){
  global $conf;
  if ($conf['useslash'] && $conf['userewrite']){
    $id = strtr($id,':','/');
  }elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' &&
      $conf['userewrite']) {
    $id = strtr($id,':',';');
  }
  if($ue){
    $id = urlencode($id);
    $id = str_replace('%3A',':',$id); //keep as colon
    $id = str_replace('%2F','/',$id); //keep as slash
  }
  return $id;
}

/**
 * This builds a link to a wikipage (using getBaseURL)
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function wl($id='',$more='',$script='doku.php',$canonical=false){
  global $conf;
  $more = str_replace(',','&amp;',$more);

  $id    = idfilter($id);
  $xlink = getBaseURL($canonical);

  if(!$conf['userewrite']){
    $xlink .= $script;
    $xlink .= '?id='.$id;
    if($more) $xlink .= '&amp;'.$more;
  }else{
    $xlink .= $id;
    if($more) $xlink .= '?'.$more;
  }
  
  return $xlink;
}

/**
 * Just builds a link to a script
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function script($script='doku.php'){
  $link = getBaseURL();
  $link .= $script;
  return $link;
}

/**
 * Return namespacepart of a wiki ID
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getNS($id){
 if(strpos($id,':')!==false){
   return substr($id,0,strrpos($id,':'));
 }
 return false;
}

/**
 * Returns the ID without the namespace
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function noNS($id){
  return preg_replace('/.*:/','',$id);
}

/**
 * Spamcheck against wordlist
 *
 * Checks the wikitext against a list of blocked expressions
 * returns true if the text contains any bad words
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function checkwordblock(){
  global $TEXT;
  global $conf;

  if(!$conf['usewordblock']) return false;

  $blockfile = file('wiki/conf/wordblock.conf');
  //how many lines to read at once (to work around some PCRE limits)
  if(version_compare(phpversion(),'4.3.0','<')){
    //old versions of PCRE define a maximum of parenthesises even if no
    //backreferences are used - the maximum is 99
    //this is very bad performancewise and may even be too high still
    $chunksize = 40; 
  }else{
    //read file in chunks of 600 - this should work around the
    //MAX_PATTERN_SIZE in modern PCRE
    $chunksize = 600;
  }
  while($blocks = array_splice($blockfile,0,$chunksize)){
    $re = array();
    #build regexp from blocks
    foreach($blocks as $block){
      $block = preg_replace('/#.*$/','',$block);
      $block = trim($block);
      if(empty($block)) continue;
      $re[]  = $block;
    }
    if(preg_match('#('.join('|',$re).')#si',$TEXT)) return true;
  }
  return false;
}

/**
 * Return the IP of the client
 *
 * Honours X-Forwarded-For Proxy Headers
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function clientIP(){
  global $pun_user;
  $my = get_remote_address();
  if($pun_user['username']){
    $my .= ' ('.$pun_user['username'].')';
  }
  return $my;
}

/**
 * Checks if a given page is currently locked.
 *
 * removes stale lockfiles
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function checklock($id){
  global $conf;
  $lock = wikiFN($id).'.lock';
  
  //no lockfile
  if(!@file_exists($lock)) return false;
  
  //lockfile expired
  if((time() - filemtime($lock)) > $conf['locktime']){
    unlink($lock);
    return false;
  }
  
  //my own lock
  $ip = io_readFile($lock);
  if( ($ip == clientIP()) || ($ip == $_SERVER['REMOTE_USER']) ){
    return false;
  }
  
  return $ip;
}

/**
 * Lock a page for editing
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function lock($id){
  $lock = wikiFN($id).'.lock';
  if($_SERVER['REMOTE_USER']){
    io_saveFile($lock,$_SERVER['REMOTE_USER']);
  }else{
    io_saveFile($lock,clientIP());
  }
}

/**
 * Unlock a page if it was locked by the user
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @return bool true if a lock was removed
 */
function unlock($id){
  $lock = wikiFN($id).'.lock';
  if(@file_exists($lock)){
    $ip = io_readFile($lock);
    if( ($ip == clientIP()) || ($ip == $_SERVER['REMOTE_USER']) ){
      @unlink($lock);
      return true;
    }
  }
  return false;
}

/**
 * Remove unwanted chars from ID
 *
 * Cleans a given ID to only use allowed characters. Accented characters are
 * converted to unaccented ones
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function cleanID($id){
  global $conf;
  global $lang;
  $id = trim($id);
  $id = utf8_strtolower($id);

  //alternative namespace seperator
  $id = strtr($id,';',':');
  if($conf['useslash']){
    $id = strtr($id,'/',':');
  }else{
    $id = strtr($id,'/','_');
  }

  if($conf['deaccent']) $id = utf8_deaccent($id,-1);

  //remove specials
  //$id = preg_replace('#[\x00-\x20 ¡!"§$%&()\[\]{}¿\\?`\'\#~*+=,<>\|^°@µ¹²³¼½¬]#u','_',$id);
  $id = utf8_strip_specials($id,'_','_:.-');

  //clean up
  $id = preg_replace('#__#','_',$id);
  $id = preg_replace('#:+#',':',$id);
  $id = trim($id,':._-');
  $id = preg_replace('#:[:\._\-]+#',':',$id);

  return($id);
}

/**
 * returns the full path to the datafile specified by ID and
 * optional revision
 *
 * The filename is URL encoded to protect Unicode chars
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function wikiFN($id,$rev=''){
  global $conf;
  $id = cleanID($id);
  $id = str_replace(':','/',$id);
  if(empty($rev)){
    $fn = $conf['datadir'].'/'.$id.'.txt';
  }else{
    $fn = $conf['olddir'].'/'.$id.'.'.$rev.'.txt';
    if($conf['usegzip'] && !@file_exists($fn)){
      //return gzip if enabled and plaintext doesn't exist
      $fn .= '.gz';
    }
  }
  $fn = utf8_encodeFN($fn);
  return $fn;
}

/**
 * Returns the full filepath to a localized textfile if local
 * version isn't found the english one is returned
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function localeFN($id){
  global $conf, $pun_user;
  $file = './lang/'.$pun_user['language'].'/WikiPages/'.$id.'.txt';
  if(!@file_exists($file)){
    //fall back to english
    $file = './lang/English/WikiPages/'.$id.'.txt';
  }
  return cleanText($file);
}

/**
 * convert line ending to unix format
 *
 * @see    formText() for 2crlf conversion
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function cleanText($text){
  $text = preg_replace("/(\015\012)|(\015)/","\012",$text);
  return $text;
}

/**
 * Prepares text for print in Webforms by encoding special chars.
 * It also converts line endings to Windows format which is
 * pseudo standard for webforms. 
 *
 * @see    cleanText() for 2unix conversion
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function formText($text){
  $text = preg_replace("/\012/","\015\012",$text);
  return htmlspecialchars($text);
}

/**
 * Returns the specified local text in parsed format
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function parsedLocale($id){
  //disable section editing
  global $parser;
  $se = $parser['secedit'];
  $parser['secedit'] = false;
  //fetch parsed locale
  $html = io_cacheParse(localeFN($id));
  //reset section editing
  $parser['secedit'] = $se;
  return $html;
}

/**
 * Returns the specified local text in raw format
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function rawLocale($id){
  return io_readFile(localeFN($id));
}


/**
 * Returns the parsed Wikitext for the given id and revision.
 *
 * If $excuse is true an explanation is returned if the file
 * wasn't found
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function parsedWiki($id,$rev='',$excuse=true){
  $file = wikiFN($id,$rev);
  $ret  = '';
  
  //ensure $id is in global $ID (needed for parsing)
  global $ID;
  $ID = $id;
  
  if($rev){
    if(@file_exists($file)){
      $ret = parse(io_readFile($file));
    }elseif($excuse){
      $ret = parsedLocale('norev');
    }
  }else{
    if(@file_exists($file)){
      $ret = io_cacheParse($file);
    }elseif($excuse){
      $ret = parsedLocale('newpage');
    }
  }
  return $ret;
}

/**
 * Returns the raw WikiText
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function rawWiki($id,$rev=''){
  return io_readFile(wikiFN($id,$rev));
}

/**
 * Returns the raw Wiki Text in three slices.
 *
 * The range parameter needs to have the form "from-to"
 * and gives the range of the section.
 * The returned order is prefix, section and suffix.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function rawWikiSlices($range,$id,$rev=''){
  list($from,$to) = split('-',$range,2);
  $text = io_readFile(wikiFN($id,$rev));
  $text = split("\n",$text);
  if(!$from) $from = 0;
  if(!$to)   $to   = count($text);

  $slices[0] = join("\n",array_slice($text,0,$from));
  $slices[1] = join("\n",array_slice($text,$from,$to + 1  - $from));
  $slices[2] = join("\n",array_slice($text,$to+1));

  return $slices;
}

/**
 * Joins wiki text slices
 *
 * function to join the text slices with correct lineendings again.
 * When the pretty parameter is set to true it adds additional empty
 * lines between sections if needed (used on saving).
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function con($pre,$text,$suf,$pretty=false){

  if($pretty){
    if($pre && substr($pre,-1) != "\n") $pre .= "\n";
    if($suf && substr($text,-1) != "\n") $text .= "\n";
  }

  if($pre) $pre .= "\n";
  if($suf) $text .= "\n";
  return $pre.$text.$suf;
}

/**
 * print debug messages
 *
 * little function to print the content of a var
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function dbg($msg,$hidden=false){
  (!$hidden) ? print '<pre class="dbg">' : print "<!--\n";
  print_r($msg);
  (!$hidden) ? print '</pre>' : print "\n-->";
}

/**
 * Add's an entry to the changelog
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function addLogEntry($date,$id,$summary=""){
  global $conf, $pun_user;
  $id     = cleanID($id);//FIXME not needed anymore?

  if(!@is_writable($conf['changelog'])){
    msg($conf['changelog'].' is not writable!',-1);
    return;
  }

  if(!$date) $date = time(); //use current time if none supplied
  $remote = get_remote_address();
  $user = $pun_user['username'];

  $logline = join("\t",array($date,$remote,$id,$user,$summary))."\n";

  //FIXME: use adjusted io_saveFile instead
  $fh = fopen($conf['changelog'],'a');
  if($fh){
    fwrite($fh,$logline);
    fclose($fh);
  }
}

/**
 * returns an array of recently changed files using the
 * changelog
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getRecents($num=0,$incdel=false){
  global $conf;
  $recent = array();
  if(!$num) $num = $conf['recent'];

  if(!@is_readable($conf['changelog'])){
    msg($conf['changelog'].' is not readable',-1);
    return $recent;
  }

  $loglines = file($conf['changelog']);
  rsort($loglines); //reverse sort on timestamp

  foreach ($loglines as $line){
    $line = rtrim($line);        //remove newline
    if(empty($line)) continue;   //skip empty lines
    $info = split("\t",$line);   //split into parts
    //add id if not in yet and file still exists and is allowed to read
    if(!$recent[$info[2]] && 
       (@file_exists(wikiFN($info[2])) || $incdel) &&
       (auth_quickaclcheck($info[2]) >= AUTH_READ)
      ){
      $recent[$info[2]]['date'] = $info[0];
      $recent[$info[2]]['ip']   = $info[1];
      $recent[$info[2]]['user'] = $info[3];
      $recent[$info[2]]['sum']  = $info[4];
      $recent[$info[2]]['del']  = !@file_exists(wikiFN($info[2]));
    }
    if(count($recent) >= $num){
      break; //finish if enough items found
    }
  }
  return $recent;
}

/**
 * gets additonal informations for a certain pagerevison
 * from the changelog
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getRevisionInfo($id,$rev){
  global $conf;
  $info = array();
  if(!@is_readable($conf['changelog'])){
    msg($conf['changelog'].' is not readable',-1);
    return $recent;
  }
  $loglines = file($conf['changelog']);
  $loglines = preg_grep("/$rev\t\d+\.\d+\.\d+\.\d+\t$id\t/",$loglines);
  rsort($loglines); //reverse sort on timestamp (shouldn't be needed)
  $line = explode("\t",$loglines[0]);
  $info['date'] = $line[0];
  $info['ip']   = $line[1];
  $info['user'] = $line[3];
  $info['sum']   = $line[4];
  return $info;
}

/**
 * Saves a wikitext by calling io_saveFile
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function saveWikiText($id,$text,$summary){
  global $conf;
  global $lang;
  umask($conf['umask']);
  // ignore if no changes were made
  if($text == rawWiki($id,'')){
    return;
  }

  $file = wikiFN($id);
  $old  = saveOldRevision($id);

  if (empty($text)){
    // remove empty files
    @unlink($file);
    $del = true;
    //autoset summary on deletion
    if(empty($summary)) $summary = $lang['deleted'];
  }else{
    // save file (datadir is created in io_saveFile)
    io_saveFile($file,$text);
    $del = false;
  }

  addLogEntry(@filemtime($file),$id,$summary);
  notify($id,$old,$summary);
  
  //purge cache on add by updating the purgefile
  if($conf['purgeonadd'] && (!$old || $del)){
    io_saveFile($conf['datadir'].'/.cache/purgefile',time());
  }
}

/**
 * moves the current version to the attic and returns its
 * revision date
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function saveOldRevision($id){
	global $conf;
  umask($conf['umask']);
  $oldf = wikiFN($id);
  if(!@file_exists($oldf)) return '';
  $date = filemtime($oldf);
  $newf = wikiFN($id,$date);
  if(substr($newf,-3)=='.gz'){
    io_saveFile($newf,rawWiki($id));
  }else{
    io_makeFileDir($newf);
    copy($oldf, $newf);
  }
  return $date;
}

/**
 * Sends a notify mail to the wikiadmin when a page was
 * changed
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function notify($id,$rev="",$summary=""){
  global $lang;
  global $conf;
  $hdrs ='';
  if(empty($conf['notify'])) return; //notify enabled?
  
  $text = rawLocale('mailtext');
  $text = str_replace('@DATE@',date($conf['dformat']),$text);
  $text = str_replace('@BROWSER@',$_SERVER['HTTP_USER_AGENT'],$text);
  $text = str_replace('@IPADDRESS@',$_SERVER['REMOTE_ADDR'],$text);
  $text = str_replace('@HOSTNAME@',gethostbyaddr($_SERVER['REMOTE_ADDR']),$text);
  $text = str_replace('@NEWPAGE@',wl($id,'','doku.php',true),$text);
  $text = str_replace('@DOKUWIKIURL@',getBaseURL(true),$text);
  $text = str_replace('@SUMMARY@',$summary,$text);
  $text = str_replace('@USER@',$_SERVER['REMOTE_USER'],$text);
  
  if($rev){
    $subject = $lang['mail_changed'].' '.$id;
    $text = str_replace('@OLDPAGE@',wl($id,"rev=$rev",'doku.php',true),$text);
    require_once("inc/DifferenceEngine.php");
    $df  = new Diff(split("\n",rawWiki($id,$rev)),
                    split("\n",rawWiki($id)));
    $dformat = new UnifiedDiffFormatter();
    $diff    = $dformat->format($df);
  }else{
    $subject=$lang['mail_newpage'].' '.$id;
    $text = str_replace('@OLDPAGE@','none',$text);
    $diff = rawWiki($id);
  }
  $text = str_replace('@DIFF@',$diff,$text);

  mail_send($conf['notify'],$subject,$text,$conf['mailfrom']);
}

/**
 * Return a list of available page revisons
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getRevisions($id){
  $revd = dirname(wikiFN($id,'foo'));
  $revs = array();
  $clid = cleanID($id);
  if(strrpos($clid,':')) $clid = substr($clid,strrpos($clid,':')+1); //remove path

  if (is_dir($revd) && $dh = opendir($revd)) {
    while (($file = readdir($dh)) !== false) {
      if (is_dir($revd.'/'.$file)) continue;
      if (preg_match('/^'.$clid.'\.(\d+)\.txt(\.gz)?$/',$file,$match)){
        $revs[]=$match[1];
      }
    }
    closedir($dh);
  }
  rsort($revs);
  return $revs;
}

/**
 * downloads a file from the net and saves it to the given location
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function download($url,$file){
  $fp = @fopen($url,"rb");
  if(!$fp) return false;

  while(!feof($fp)){
    $cont.= fread($fp,1024);
  }
  fclose($fp);

  $fp2 = @fopen($file,"w");
  if(!$fp2) return false;
  fwrite($fp2,$cont);
  fclose($fp2);
  return true;
} 

/**
 * extracts the query from a google referer
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getGoogleQuery(){
  $url = parse_url($_SERVER['HTTP_REFERER']);

  if(!preg_match("#google\.#i",$url['host'])) return '';
  $query = array();
  parse_str($url['query'],$query);

  return $query['q'];
}

/**
 * Try to set correct locale
 *
 * @deprecated No longer used
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
function setCorrectLocale(){
  global $conf;
  global $lang;

  $enc = strtoupper($lang['encoding']);
  foreach ($lang['locales'] as $loc){
    //try locale
    if(@setlocale(LC_ALL,$loc)) return;
    //try loceale with encoding
    if(@setlocale(LC_ALL,"$loc.$enc")) return;
  }
  //still here? try to set from environment
  @setlocale(LC_ALL,"");
}

/**
 * Return the human readable size of a file
 *
 * @param       int    $size   A file size
 * @param       int    $dec    A number of decimal places
 * @author      Martin Benjamin <b.martin@cybernet.ch>
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.0
 */
function filesize_h($size, $dec = 1){
  $sizes = array('B', 'KB', 'MB', 'GB');
  $count = count($sizes);
  $i = 0;
    
  while ($size >= 1024 && ($i < $count - 1)) {
    $size /= 1024;
    $i++;
  }

  return round($size, $dec) . ' ' . $sizes[$i];
}

/**
 * Run a few sanity checks
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function getVersion(){
  //import version string
  if(@file_exists('./wiki/VERSION')){
    //official release
    return 'Release '.io_readfile('./wiki/VERSION');
  }elseif(is_dir('_darcs')){
    //darcs checkout
    $inv = file('_darcs/inventory');
    $inv = preg_grep('#andi@splitbrain\.org\*\*\d{14}#',$inv);
    $cur = array_pop($inv);
    preg_match('#\*\*(\d{4})(\d{2})(\d{2})#',$cur,$matches);
    return 'Darcs '.$matches[1].'-'.$matches[2].'-'.$matches[3];
  }else{
    return 'snapshot?';
  }
}

/**
 * Run a few sanity checks
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function check(){
  global $conf;
  global $INFO;

  msg('DokuWiki version: '.getVersion(),1);

  if(version_compare(phpversion(),'4.3.0','<')){
    msg('Your PHP version is too old ('.phpversion().' vs. 4.3.+ recommended)',-1);
  }elseif(version_compare(phpversion(),'4.3.10','<')){
    msg('Consider upgrading PHP to 4.3.10 or higher for security reasons (your version: '.phpversion().')',0);
  }else{
    msg('PHP version '.phpversion(),1);
  }

  if(is_writable($conf['changelog'])){
    msg('Changelog is writable',1);
  }else{
    msg('Changelog is not writable',-1);
  }

  if(is_writable($conf['datadir'])){
    msg('Datadir is writable',1);
  }else{
    msg('Datadir is not writable',-1);
  }

  if(is_writable($conf['olddir'])){
    msg('Attic is writable',1);
  }else{
    msg('Attic is not writable',-1);
  }

  if(is_writable($conf['mediadir'])){
    msg('Mediadir is writable',1);
  }else{
    msg('Mediadir is not writable',-1);
  }

  if(function_exists('mb_strpos')){
    if(defined('UTF8_NOMBSTRING')){
      msg('mb_string extension is available but will not be used',0);
    }else{
      msg('mb_string extension is available and will be used',1);
    }
  }else{
    msg('mb_string extension not available - PHP only replacements will be used',0);
  }
 
  msg('Your current permission for this page is '.$INFO['perm'],0);

  if(is_writable($INFO['filepath'])){
    msg('The current page is writable by the webserver',0);
  }else{
    msg('The current page is not writable by the webserver',0);
  }

  if($INFO['writable']){
    msg('The current page is writable by you',0);
  }else{
    msg('The current page is not writable you',0);
  }
}
?>
