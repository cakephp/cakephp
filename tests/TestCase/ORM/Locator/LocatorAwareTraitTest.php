<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\ORM\Locator;

use Cake\TestSuite\TestCase;

/**
 * LocatorAwareTrait test case
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
     * Tests tableLocator method
     *
     * @group deprecated
     * @return void
     */
    public function testTableLocator()
    {
        $this->deprecated(function () {
            $tableLocator = $this->subject->tableLocator();
            $this->assertSame($this->getTableLocator(), $tableLocator);

            $newLocator = $this->getMockBuilder('Cake\ORM\Locator\LocatorInterface')->getMock();
            $subjectLocator = $this->subject->tableLocator($newLocator);
            $this->assertSame($newLocator, $subjectLocator);
        });
    }

    /**
     * Tests testGetTableLocator method
     *
     * @return void
     */
    public function testGetTableLocator()
    {
        $tableLocator = $this->subject->getTableLocator();
        $this->assertSame($this->getTableLocator(), $tableLocator);
    }

    /**
     * Tests testSetTableLocator method
     *
     * @return void
     */
    public function testSetTableLocator()
    {
        $newLocator = $this->getMockBuilder('Cake\ORM\Locator\LocatorInterface')->getMock();
        $this->subject->setTableLocator($newLocator);
        $subjectLocator = $this->subject->getTableLocator();
        $this->assertSame($newLocator, $subjectLocator);
    }
}
