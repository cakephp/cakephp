<?php
/**
 * Note: Router::setExtensions() is available since CakePHP 2.2
 * If you use 2.1 dont load this routes file, and add pdf to your parseExtensions() in app/Config/routes.php
 */
Router::parseExtensions();
Router::setExtensions(array('pdf'));