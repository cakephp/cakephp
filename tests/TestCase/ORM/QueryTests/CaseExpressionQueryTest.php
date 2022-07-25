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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\QueryTests;

use Cake\ORM\Query;
use Cake\TestSuite\TestCase;

class CaseExpressionQueryTest extends TestCase
{
    protected $fixtures = [
        'core.Products',
    ];

    public function testOverwrittenReturnType(): void
    {
        $query = $this->getTableLocator()->get('Products')
            ->find()
            ->select(function (Query $query) {
                return [
                    'name',
                    'price',
                    'is_cheap' => $query->newExpr()
                        ->case()
                        ->when(['price <' => 20])
                        ->then(1)
                        ->else(0)
                        ->setReturnType('boolean'),
                ];
            })
            ->orderAsc('price')
            ->orderAsc('name')
            ->disableHydration();

        $expected = [
            [
                'name' => 'First product',
                'price' => 10,
                'is_cheap' => true,
            ],
            [
                'name' => 'Second product',
                'price' => 20,
                'is_cheap' => false,
            ],
            [
                'name' => 'Third product',
                'price' => 30,
                'is_cheap' => false,
            ],
        ];
        $this->assertSame($expected, $query->toArray());
    }

    public function bindingValueDataProvider(): array
    {
        return [
            ['1', 3],
            ['2', 4],
        ];
    }

    /**
     * @dataProvider bindingValueDataProvider
     * @param string $when The `WHEN` value.
     * @param int $result The result value.
     */
    public function testBindValues(string $when, int $result): void
    {
        $value = '1';
        $then = '3';
        $else = '4';

        $query = $this->getTableLocator()->get('Products')
            ->find()
            ->select(function (Query $query) {
                return [
                    'val' => $query->newExpr()
                        ->case($query->newExpr(':value'))
                        ->when($query->newExpr(':when'))
                        ->then($query->newExpr(':then'))
                        ->else($query->newExpr(':else'))
                        ->setReturnType('integer'),
                ];
            })
            ->bind(':value', $value, 'integer')
            ->bind(':when', $when, 'integer')
            ->bind(':then', $then, 'integer')
            ->bind(':else', $else, 'integer')
            ->disableHydration();

        $expected = [
            'val' => $result,
        ];
        $this->assertSame($expected, $query->first());
    }
}
