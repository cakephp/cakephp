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

use Cake\ORM\TableRegistry;
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
     * @return void
     */
    public function testTableLocator()
    {
        $tableLocator = $this->subject->tableLocator();
        $this->assertSame(TableRegistry::locator(), $tableLocator);

        $newLocator = $this->getMockBuilder('Cake\ORM\Locator\LocatorInterface')->getMock();
        $subjectLocator = $this->subject->tableLocator($newLocator);
        $this->assertSame($newLocator, $subjectLocator);
    }
}
