<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * CounterCacheCommandTest class
 */
class CounterCacheCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = [
        'core.CounterCacheComments',
        'core.CounterCacheUsers',
    ];

    protected Table $Comments;
    protected Table $Users;

    public function setUp(): void
    {
        parent::setUp();

        $this->setAppNamespace();
        $connection = ConnectionManager::get('test');

        $this->getTableLocator()->get('Users', [
            'table' => 'counter_cache_users',
            'connection' => $connection,
        ]);

        $comments = $this->getTableLocator()->get('Comments', [
            'table' => 'counter_cache_comments',
            'connection' => $connection,
        ]);

        $comments->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);

        $comments->addBehavior('CounterCache', [
            'Users' => ['comment_count'],
        ]);
    }

    public function testExecute(): void
    {
        $this->exec('counter_cache Comments');
        $this->assertExitSuccess();
        $this->assertOutputContains('Counter cache updated successfully.');
    }

    public function testExecuteWithOptions(): void
    {
        $this->exec('counter_cache Comments --assoc Users --limit 1 --page 1');
        $this->assertExitSuccess();
    }

    public function testExecuteFailure(): void
    {
        $this->exec('counter_cache Users');
        $this->assertExitError();
        $this->assertErrorContains('The specified model does not have the CounterCache behavior attached.');
    }
}
