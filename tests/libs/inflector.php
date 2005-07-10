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
uses('inflector');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class InflectorTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $inflector;

/**
 * Enter description here...
 *
 * @return InflectorTest
 */
   function InflectorTest()
   {
      $this->UnitTestCase('Inflector test');
   }

/**
 * Enter description here...
 *
 */
   function setUp()
   {
      $this->inflector = new Inflector();
   }

/**
 * Enter description here...
 *
 */
   function tearDown()
   {
      unset($this->inflector);
   }

/**
 * Enter description here...
 *
 */
   function testPluralizeSingularize()
   {
      $singulars = array(
      'search', 'switch', 'fix', 'box', 'process', 'address', 'query', 'ability',
      'agency', 'half', 'safe', 'wife', 'basis', 'diagnosis', 'datum', 'medium',
      'person', 'salesperson', 'man', 'woman', 'spokesman', 'child', 'page', 'robot');
      $plurals = array(
      'searches', 'switches', 'fixes', 'boxes', 'processes', 'addresses', 'queries', 'abilities',
      'agencies', 'halves', 'saves', 'wives', 'bases', 'diagnoses', 'data', 'media',
      'people', 'salespeople', 'men', 'women', 'spokesmen', 'children', 'pages', 'robots');

      foreach (array_combine($singulars, $plurals) as $singular => $plural)
      {
         $this->assertEqual($this->inflector->pluralize($singular), $plural);
         $this->assertEqual($this->inflector->singularize($plural), $singular);
      }
   }

/**
 * Enter description here...
 *
 */
   function testCamelize()
   {
      $this->assertEqual($this->inflector->camelize('foo_bar_baz'), 'FooBarBaz');
   }

/**
 * Enter description here...
 *
 */
   function testUnderscore()
   {
      $this->assertEqual($this->inflector->underscore('FooBarBaz'), 'foo_bar_baz');
   }

/**
 * Enter description here...
 *
 */
   function testHumanize()
   {
      $this->assertEqual($this->inflector->humanize('foo_bar_baz'), 'Foo Bar Baz');
   }

/**
 * Enter description here...
 *
 */
   function testTableize()
   {
      $this->assertEqual($this->inflector->tableize('Bar'), 'bars');
   }

/**
 * Enter description here...
 *
 */
   function testClassify()
   {
      $this->assertEqual($this->inflector->classify('bars'), 'Bar');
   }

/**
 * Enter description here...
 *
 */
   function testForeignKey()
   {
      $this->assertEqual($this->inflector->foreignKey('Bar'), 'bar_id');
   }
}

?>