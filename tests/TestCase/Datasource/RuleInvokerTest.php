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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use Cake\Datasource\RuleInvoker;
use Cake\ORM\Entity;
use Cake\ORM\Rule\ValidCount;
use PHPUnit\Framework\TestCase;

class RuleInvokerTest extends TestCase
{
    public function testInvoke(): void
    {
        $entity = new Entity([
            'players' => 1,
        ]);

        $rule = new ValidCount('players');
        $rulesInvoker = new RuleInvoker(
            $rule,
            'countPlayers',
            [
                'count' => 2,
                'errorField' => 'player_id',
                'message' => function ($entity, $options) {
                    return 'Player count should be ' . $options['count'] . ' not ' . $entity->get('players');
                },
            ]
        );
        $rulesInvoker->__invoke($entity, []);
        $this->assertEquals(
            ['countPlayers' => 'Player count should be 2 not 1'],
            $entity->getError('player_id')
        );
    }
}
