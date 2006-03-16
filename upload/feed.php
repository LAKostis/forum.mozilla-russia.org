<?php
/**
 * XML feed export
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
  define('PUN_ROOT', './');
  define('PUN_QUIET_VISIT', 1);
  require PUN_ROOT.'include/common.php';
  ini_set('short_open_tag',"1");
  require_once("wiki/inc/common.php");
  require_once("wiki/inc/parser.php");
  require_once("wiki/inc/feedcreator.class.php");
  require_once("wiki/inc/auth.php");

  //set auth header for login
  if($_REQUEST['login'] && !isset($_SERVER['PHP_AUTH_USER'])){
    header('WWW-Authenticate: Basic realm="'.$conf['title'].'"');
    header('HTTP/1.0 401 Unauthorized');
    auth_logoff();
  }


  $num  = $_REQUEST['num'];
  $type = $_REQUEST['type'];
  if (isset($_REQUEST['mode']))
	$mode = $_REQUEST['mode'];
  else
	$mode = 'list';
  $ns   = $_REQUEST['ns'];

  switch ($type){
    case 'rss':
       $type = 'RSS0.9';
       break;
    case 'rss2':
       $type = 'RSS2.0';
       break;
    case 'atom':
       $type = 'ATOM0.3';
       break;
    default:
       $type = 'RSS1.0';
  }

  //some defaults for the feed
  $CACHEGROUP = 'feed';
  $conf['typography'] = false;
  $conf['canonical']  = true;
  $parser['toc']      = false;

  $rss = new UniversalFeedCreator();
  $rss = new DokuWikiFeedCreator();
  $rss->title = $conf['title'];
  $rss->link  = wl();
  $rss->syndicationURL = getBaseURL().'/feed.php';
  $rss->cssStyleSheet = getBaseURL().'/wiki/feed.css';

  if($mode == 'list'){
    rssListNamespace($rss,$ns);
  }else{
    rssRecentChanges($rss,$num);
  }

  header('Content-Type: application/xml; charset='.$lang['encoding']);
  print $rss->createFeed($type,$lang['encoding']);

// ---------------------------------------------------------------- //

/**
 * Add recent changed to a feed object
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function rssRecentChanges(&$rss,$num){
//  $recents = getRecents($num);
  $recents = getRecents($num,true);
  foreach(array_keys($recents) as $id){
    $desc = cleanDesc(parsedWiki($id));
    if(!empty($recents[$id]['sum'])){
      $desc = '['.strip_tags($recents[$id]['sum']).'] '.$desc;
    }
    $item = new FeedItem();
    $item->title       = $id;
    $item->link        = wl($id,'rev='.$recents[$id]['date']);
    $item->description = $desc;
    $item->date        = date('r',$recents[$id]['date']);
    if(strpos($id,':')!==false){
      $item->category    = substr($id,0,strrpos($id,':'));
    }
    if($recents[$id]['user']){
      $item->author = $recents[$id]['user'].'@';
    }else{
      $item->author = 'anonymous@';
    }
    $item->author  .= $recents[$id]['ip'];
    
    $rss->addItem($item);
  }
}

/**
 * Add all pages of a namespace to a feedobject
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function rssListNamespace(&$rss,$ns){
  require_once("wiki/inc/search.php");
  global $conf;

  $ns=':'.cleanID($ns);
  $ns=str_replace(':','/',$ns);

  $data = array();
  sort($data);
  search($data,$conf['datadir'],'search_list','',$ns);
  foreach($data as $row){
    $id = $row['id'];
    $date = filemtime(wikiFN($id));
    $desc = cleanDesc(parsedWiki($id));
    $item = new FeedItem();
    $item->title       = $id;
    $item->link        = wl($id,'rev='.$date);
    $item->description = $desc;
    $item->date        = date('r',$date);
    $rss->addItem($item);
  }  
}

/**
 * Clean description for feed inclusion
 *
 * Removes HTML tags and line breaks and trims the text to
 * 250 chars
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function cleanDesc($desc){
  //remove TOC
  $desc = strip_tags($desc);
  $desc = preg_replace('/[\n\r\t]/',' ',$desc);
  $desc = preg_replace('/  /',' ',$desc);
  $desc = substr($desc,0,250);
  $desc = $desc.'...';
  return $desc;
}

?>
