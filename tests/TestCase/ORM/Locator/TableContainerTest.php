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
namespace Cake\Test\TestCase\ORM\Locator;

use Cake\Core\Container;
use Cake\ORM\Locator\TableContainer;
use Cake\TestSuite\TestCase;
use League\Container\Exception\NotFoundException;
use TestApp\Model\Table\ArticlesTable;
use TestApp\Model\Table\FakeTable;

class TableContainerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
    }

    public function testTableContainer(): void
    {
        $container = new Container();
        $container->delegate(new TableContainer());

        $table = $container->get(ArticlesTable::class);
        $this->assertInstanceOf(ArticlesTable::class, $table);
        $this->assertSame($table, $container->get(ArticlesTable::class));
    }

    public function testTableContainerMissingTable(): void
    {
        $container = new Container();
        $container->delegate(new TableContainer());

        $this->expectException(NotFoundException::class);
        $container->get(FakeTable::class);
    }
}
