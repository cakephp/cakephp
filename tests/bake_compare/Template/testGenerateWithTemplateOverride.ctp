<?php
/**
 * These are my routes. There are many like them but these are my own.
 */
use Cake\Routing\Router;

Router::plugin('Special', function ($routes) {
	$routes->fallbacks();
});
