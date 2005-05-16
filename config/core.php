<?PHP

/* 
 * Basic configuration
*/
// Debugging level
// 0: production, 1: development, 2: full debug with sql
define ('DEBUG', 0);

// Full-page caching
define ('CACHE_PAGES', false);
// Cache lifetime in seconds, 0 for debugging, -1 for eternity,
define ('CACHE_PAGES_FOR', -1);


/*
 * Advanced configuration
*/
// Debug options
if (DEBUG) {
	error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);
	$TIME_START = getmicrotime();
}

?>