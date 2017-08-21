<?php
// @deprecated Backward compatibility with 2.x series
if (PHP_VERSION_ID < 70000) {
    class_alias('Cake\Utility\Text', 'Cake\Utility\String');
    trigger_error('Using Cake\Utility\String will throw an error in CakePHP 3.6.0. Please use Cake\Utility\Text instead.', E_USER_NOTICE);
}
