<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\ORM\Locator;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LocatorAwareTrait test case
 *
 */
class LocatorAwareTraitTest extends TestCase
{

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->subject = $this->getObjectForTrait('Cake\ORM\Locator\LocatorAwareTrait');
    }

    /**
     * Tests locator method
     *
     * @return void
     */
    public function testLocator()
    {
        $locator = $this->subject->locator();
        $this->assertSame(TableRegistry::locator(), $locator);

        $newLocator = $this->getMock('Cake\ORM\Locator\LocatorInterface');
        $subjectLocator = $this->subject->locator($newLocator);
        $this->assertSame($newLocator, $subjectLocator);
    }
}
