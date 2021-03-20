<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\TestSuite;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;

class TransactionStrategyTest extends TestCase
{
    public $fixtures = ['core.Users'];

    /**
     * Test that beforeTest starts a transaction that afterTest closes.
     *
     * @return void
     */
    public function testTransactionWrapping()
    {
        $users = TableRegistry::get('Users');

        $strategy = new TransactionStrategy();
        $strategy->beforeTest();
        $user = $users->newEntity(['username' => 'testing', 'password' => 'secrets']);

        $users->save($user, ['atomic' => true]);
        $this->assertNotEmpty($users->get($user->id), 'User should exist.');

        // Rollback and the user should be gone.
        $strategy->afterTest();
        $this->assertNull($users->findById($user->id)->first(), 'No user expected.');
    }
}
