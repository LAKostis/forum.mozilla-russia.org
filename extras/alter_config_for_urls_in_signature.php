<?

define('PUN_ROOT', './');
@include PUN_ROOT.'config.php';

// If PUN isn't defined, config.php is missing or corrupt or we are outside the root directory
if (!defined('PUN'))
	exit('This file must be run from the forum root directory.');

// Enable debug mode
define('PUN_DEBUG', 1);

// Disable error reporting for uninitialized variables
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Turn off magic_quotes_runtime
set_magic_quotes_runtime(0);

// Turn off PHP time limit
@set_time_limit(0);


// Load the functions script
require PUN_ROOT.'include/functions.php';


// Load DB abstraction layer and try to connect
require PUN_ROOT.'include/dblayer/common_db.php';


// do it
$result = $db->query('INSERT INTO '.$db->prefix.'config VALUES("o_urls_in_signature", "50")');

echo ($result) ? 'done' : 'falied';

require PUN_ROOT.'include/cache.php';
generate_config_cache();

?>
