<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * 
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * Basic defines
 */
uses('dbo_factory');

class DboTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $dbo;

/**
 * Enter description here...
 *
 * @return DboTest
 */
   function DboTest()
   {
      $this->UnitTestCase('DBO test');
   }

/**
 * Enter description here...
 *
 */
   function setUp()
   {
      $this->dbo = DBO::getInstance('test');
      
      $this->createTemporaryTable();
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function tearDown()
   {
      if(!$this->dbo) return false;

      $this->dropTemporaryTable();
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function createTemporaryTable()
   {
      if(!$this->dbo) return false;

      if($this->dbo->config['driver'] == 'postgres')
      $sql = 'CREATE TABLE __test(id serial NOT NULL, body CHARACTER VARYING(255))';
      else
      $sql = 'CREATE TABLE __test(id INT UNSIGNED PRIMARY KEY, body VARCHAR(255))';

      return $this->dbo->query($sql);
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function dropTemporaryTable()
   {
      if(!$this->dbo) return false;

      return $this->dbo->query("DROP TABLE __test");
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function testHasImplementation()
   {
      if(!$this->dbo) return false;

      $functions = array(
      'connect',
      'disconnect',
      'execute',
      'fetchRow',
      'tables',
      'fields',
      'prepare',
      'lastError',
      'lastAffected',
      'lastNumRows',
      'lastInsertId'
      );

      foreach($functions as $function)
      {
         $this->assertTrue(method_exists($this->dbo, $function));
      }
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function testConnectivity()
   {
      if(!$this->dbo) return false;

      $this->assertTrue($this->dbo->connected);
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function testFields()
   {
      if(!$this->dbo) return false;

      $fields = $this->dbo->fields('__test');
      $this->assertEqual(count($fields), 2, 'equals');
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function testTables()
   {
      if(!$this->dbo) return false;

      $this->assertTrue(in_array('__test', $this->dbo->tables()));
   }
}

?>