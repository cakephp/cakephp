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
 * 
 */
uses('neat_array');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class NeatArrayTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $neatArray;

/**
 * Enter description here...
 *
 * @return NeatArrayTest
 */
   function NeatArrayTest()
   {
      $this->UnitTestCase('NeatArray test');
   }

/**
 * Enter description here...
 *
 */
   function setUp()
   {
      $this->neatArray = new NeatArray();
   }

/**
 * Enter description here...
 *
 */
   function tearDown()
   {
      unset($this->neatArray);
   }


/**
 * Enter description here...
 *
 */
   function testInArray()
   {
      $a = array('foo'=>' bar ', 'i-am'=>'a');
      $b = array('foo'=>'bar ',  'i-am'=>'b');
      $c = array('foo'=>' bar',  'i-am'=>'c');
      $d = array('foo'=>'bar',   'i-am'=>'d');
      
      $n = new NeatArray(array($a, $b, $c, $d));

      $result = $n->findIn('foo', ' bar ');
      $expected = array(0=>$a);
      $this->assertEqual($result, $expected);

      $result = $n->findIn('foo', 'bar ');
      $expected = array(1=>$b);
      $this->assertEqual($result, $expected);

      $result = $n->findIn('foo', ' bar');
      $expected = array(2=>$c);
      $this->assertEqual($result, $expected);

      $result = $n->findIn('foo', 'bar');
      $expected = array(3=>$d);
      $this->assertEqual($result, $expected);
   }

}

?>