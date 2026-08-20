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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\AssociationsNormalizerTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests AssociationsNormalizerTrait.
 */
class AssociationsNormalizerTraitTest extends TestCase
{
    /**
     * @return array<string, array{0: array|string, 1: array}>
     */
    public static function normalizeAssociationsProvider(): array
    {
        $expected = [
            'First' => [
                'associated' => [
                    'Second' => [],
                    'Third' => [],
                    'Fourth' => [],
                ],
            ],
        ];

        return [
            'dot notation' => [
                ['First.Second', 'First.Third', 'First.Fourth'],
                $expected,
            ],
            'contain style list' => [
                ['First' => ['Second', 'Third', 'Fourth']],
                $expected,
            ],
            'single nested child' => [
                ['First' => ['Second']],
                [
                    'First' => [
                        'associated' => [
                            'Second' => [],
                        ],
                    ],
                ],
            ],
            'deeper contain style' => [
                ['First' => ['Second' => ['Third']]],
                [
                    'First' => [
                        'associated' => [
                            'Second' => [
                                'associated' => [
                                    'Third' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'mixed options and associations' => [
                ['Comments' => ['fields' => ['id', 'body'], 'Users']],
                [
                    'Comments' => [
                        'fields' => ['id', 'body'],
                        'associated' => [
                            'Users' => [],
                        ],
                    ],
                ],
            ],
            'options only' => [
                ['Tags' => ['onlyIds' => true]],
                [
                    'Tags' => [
                        'onlyIds' => true,
                    ],
                ],
            ],
            'lowercase aliases' => [
                ['authors' => ['supervisors' => ['tags']]],
                [
                    'authors' => [
                        'associated' => [
                            'supervisors' => [
                                'associated' => [
                                    'tags' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array|string $associations
     * @param array $expected
     */
    #[DataProvider('normalizeAssociationsProvider')]
    public function testNormalizeAssociations(array|string $associations, array $expected): void
    {
        $normalizer = new class {
            use AssociationsNormalizerTrait {
                normalizeAssociations as public;
            }
        };

        $this->assertSame($expected, $normalizer->normalizeAssociations($associations));
    }
}
