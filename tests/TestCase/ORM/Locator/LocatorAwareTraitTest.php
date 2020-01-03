<?php
declare(strict_types=1);

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

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Locator\LocatorInterface;
use Cake\TestSuite\TestCase;

/**
 * LocatorAwareTrait test case
 */
class LocatorAwareTraitTest extends TestCase
{
    /**
     * @var object|\Cake\ORM\Locator\LocatorAwareTrait
     */
    protected $subject;

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getObjectForTrait(LocatorAwareTrait::class);
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
        $newLocator = $this->getMockBuilder(LocatorInterface::class)->getMock();
        $this->subject->setTableLocator($newLocator);
        $subjectLocator = $this->subject->getTableLocator();
        $this->assertSame($newLocator, $subjectLocator);
    }
}
