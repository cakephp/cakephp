<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Datasource\Paging\SortField;
use Cake\Datasource\Paging\SortsFactory;
use Cake\TestSuite\TestCase;

/**
 * SortsFactory Test Case
 */
class SortsFactoryTest extends TestCase
{
    /**
     * Test basic add() functionality
     *
     * @return void
     */
    public function testAdd(): void
    {
        $factory = new SortsFactory();
        $factory->add('newest', SortField::desc('created'));
        $sorts = $factory->build();

        $this->assertArrayHasKey('newest', $sorts);
        $this->assertCount(1, $sorts['newest']);
        $this->assertInstanceOf(SortField::class, $sorts['newest'][0]);
    }

    /**
     * Test add() with multiple fields
     *
     * @return void
     */
    public function testAddMultipleFields(): void
    {
        $factory = new SortsFactory();
        $factory->add('relevance', SortField::desc('score'), SortField::asc('title'));
        $sorts = $factory->build();

        $this->assertCount(2, $sorts['relevance']);
        $this->assertInstanceOf(SortField::class, $sorts['relevance'][0]);
        $this->assertInstanceOf(SortField::class, $sorts['relevance'][1]);
    }

    /**
     * Test add() with string fields
     *
     * @return void
     */
    public function testAddStringFields(): void
    {
        $factory = new SortsFactory();
        $factory->add('simple', 'title', 'created');
        $sorts = $factory->build();

        $this->assertCount(2, $sorts['simple']);
        $this->assertSame('title', $sorts['simple'][0]);
        $this->assertSame('created', $sorts['simple'][1]);
    }

    /**
     * Test add() with no fields (shorthand)
     *
     * @return void
     */
    public function testAddShorthand(): void
    {
        $factory = new SortsFactory();
        $factory->add('title');
        $sorts = $factory->build();

        $this->assertCount(1, $sorts['title']);
        $this->assertSame('title', $sorts['title'][0]);
    }
}
