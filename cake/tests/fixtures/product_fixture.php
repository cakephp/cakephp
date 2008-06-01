<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.fixtures
 * @since			CakePHP(tm) v 1.2.0.4667
 * @version			$Rev$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.fixtures
 */ 
class ProductFixture extends CakeTestFixture {
    var $name = 'Product';    
        
    var $fields = array(
        'id' => array('type' => 'integer', 'key' => 'primary'),
        'name' => array('type' => 'string', 'length' => 255, 'null' => false),
        'type' => array('type' => 'string', 'length' => 255, 'null' => false),
        'price' => array('type' => 'integer', 'null' => false),
    ); 
    
    var $records = array(
        array( 'id' => 1 , 'name' => 'Park\'s Great Hits', 'type' => 'Music', 'price' => 19 ),  
        array( 'id' => 2 , 'name' => 'Silly Puddy', 'type' => 'Toy', 'price' => 3 ),  
        array( 'id' => 3 , 'name' => 'Playstation', 'type' => 'Toy', 'price' => 89 ),  
        array( 'id' => 4 , 'name' => 'Men\'s T-Shirt', 'type' => 'Clothing', 'price' => 32 ),  
        array( 'id' => 5 , 'name' => 'Blouse', 'type' => 'Clothing', 'price' => 34 ),  
        array( 'id' => 6 , 'name' => 'Electronica 2002', 'type' => 'Music', 'price' => 4 ),  
        array( 'id' => 7 , 'name' => 'Country Tunes', 'type' => 'Music', 'price' => 21 ),  
        array( 'id' => 8 , 'name' => 'Watermelon', 'type' => 'Food', 'price' => 9 ),  
    );
} 
?> 