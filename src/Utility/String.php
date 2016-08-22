<?php
// @deprecated Backward compatibility with 2.x series
if (PHP_VERSION_ID < 70000) {
    class_alias('Cake\Utility\Text', 'Cake\Utility\String');
}
