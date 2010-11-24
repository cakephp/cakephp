<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class ProductUpdateAllFixture extends CakeTestFixture {
	var $name = 'ProductUpdateAll';
	var $table = 'product_update_all';

    var $fields = array(
            'id' => array('type'=>'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
            'name' => array('type'=>'string', 'null' => false, 'length' => 29),
            'groupcode' => array('type'=>'integer', 'null' => false, 'length' => 4),
            'group_id' => array('type'=>'integer', 'null' => false, 'length' => 8),
            'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
            );
    var $records = array(
        array(
            'id'  => 1,
            'name'  => 'product one',
            'groupcode'  => 120,
            'group_id'  => 1
            ),
        array(
            'id'  => 2,
            'name'  => 'product two',
            'groupcode'  => 120,
            'group_id'  => 1),
        array(
            'id'  => 3,
            'name'  => 'product three',
            'groupcode'  => 125,
            'group_id'  => 2),
        array(
            'id'  => 4,
            'name'  => 'product four',
            'groupcode'  => 135,
            'group_id'  => 4)
        );
}
