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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\HashValidator;
use stdClass;

class HashValidatorTest extends TestCase
{
    public function testBasicTypes(): void
    {
        // all valid basic types
        $schema = [
            'fields' => [
                'array' => 'array',
                'list' => 'list',
                'string' => 'string',
                'float' => 'float',
                'int' => 'int',
                'bool' => 'bool',
                'null' => 'null',
                'mixed' => 'mixed',
                'class' => stdClass::class,
            ],
        ];
        $hash = ['array' => [], 'list' => ['value'], 'string' => 'a string', 'float' => 1.23, 'int' => 1, 'bool' => true, 'mixed' => true, 'null' => null, 'class' => new stdClass()];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // all invalid basic types (mixed cannot be invalid)
        $schema = [
            'fields' => [
                'array' => 'array',
                'list' => 'list',
                'string' => 'string',
                'float' => 'float',
                'int' => 'int',
                'bool' => 'bool',
                'null' => 'null',
                'class' => stdClass::class,
            ],
        ];
        $hash = ['array' => null, 'list' => ['a' => 'value'], 'string' => 1.23, 'float' => 2, 'int' => null, 'bool' => new stdClass(), 'null' => 1, 'class' => true];
        $this->assertSame(
            [
                'array' => 'Field value does not match expected type.',
                'list' => 'Field value does not match expected type.',
                'string' => 'Field value does not match expected type.',
                'float' => 'Field value does not match expected type.',
                'int' => 'Field value does not match expected type.',
                'bool' => 'Field value does not match expected type.',
                'null' => 'Field value does not match expected type.',
                'class' => 'Field value does not match expected type.',
            ],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testClassStringType(): void
    {
        $schema = [
            'fields' => [
                'has_class_string' => [
                    'type' => ['class-string<>' => stdClass::class],
                ],
            ],
        ];
        $hash = ['has_class_string' => stdClass::class];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'has_class_string' => [
                    'type' => ['class-string<>' => [HashValidator::class, stdClass::class]],
                ],
            ],
        ];
        $hash = ['has_class_string' => stdClass::class];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'has_class_string' => [
                    'type' => ['class-string<>' => stdClass::class],
                ],
            ],
        ];
        $hash = ['has_class_string' => HashValidator::class];
        $this->assertSame(
            ['has_class_string' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testArrayType()
    {
        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => ['array<>' => stdClass::class],
                ],
            ],
        ];
        $hash = ['is_array' => ['a' => new stdClass(), new stdClass()]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => ['array<>' => [stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_array' => ['a' => new stdClass(), new stdClass()]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => ['array<>' => ['int', stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_array' => [new stdClass(), 'a' => 1]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => ['array<>' => [stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_array' => new stdClass()];
        $this->assertSame(
            ['is_array' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => ['array<>' => [stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_array' => [new stdClass(), 'a' => 1]];
        $this->assertSame(
            ['is_array' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testListType()
    {
        $schema = [
            'fields' => [
                'is_list' => [
                    'type' => ['list<>' => stdClass::class],
                ],
            ],
        ];
        $hash = ['is_list' => [new stdClass(), new stdClass()]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_list' => [
                    'type' => ['list<>' => [stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_list' => [new stdClass(), new stdClass()]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_list' => [
                    'type' => ['list<>' => ['int', stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_list' => [new stdClass(), 1]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_list' => [
                    'type' => ['list<>' => [stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_list' => new stdClass()];
        $this->assertSame(
            ['is_list' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );

        $schema = [
            'fields' => [
                'is_list' => [
                    'type' => ['list<>' => [stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_list' => [new stdClass(), 1]];
        $this->assertSame(
            ['is_list' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );

        $schema = [
            'fields' => [
                'is_list' => [
                    'type' => ['list<>' => ['int', stdClass::class]],
                ],
            ],
        ];
        $hash = ['is_list' => [new stdClass(), 'a' => 1]];
        $this->assertSame(
            ['is_list' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testArrayShapes(): void
    {
        // single-dimension
        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['int' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => ['int' => 1]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['int' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => ['int' => 1.13]];
        $this->assertSame(
            ['is_array.int' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );

        // test multi-dimension
        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => [
                                'nested' => [
                                    'type' => [
                                        'array{}' => [
                                            'fields' => ['int' => 'int'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => ['nested' => ['int' => 1]]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => [
                                'nested' => [
                                    'type' => [
                                        'array{}' => [
                                            'fields' => ['int' => 'int'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => ['nested' => ['int' => 1.23]]];
        $this->assertSame(
            ['is_array.nested.int' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testUnionType(): void
    {
        // type is in union
        $schema = ['fields' => ['string_or_int' => ['type' => ['string', 'int']]]];
        $hash = ['string_or_int' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // type is not in union
        $schema = ['fields' => ['string_or_float' => ['type' => ['string', 'float']]]];
        $hash = ['string_or_float' => 1];
        $this->assertSame(
            ['string_or_float' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );

        // test generic in union
        $schema = ['fields' => ['int_or_array' => ['type' => ['int', 'array<>' => ['class-string<>' => stdClass::class]]]]];
        $hash = ['int_or_array' => [stdClass::class]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // test shape in union
        $schema = [
            'fields' => [
                'int_or_shape' => [
                    'type' => [
                        'int',
                        'array{}' => [
                            'fields' => ['int' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['int_or_shape' => ['int' => 1]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'int_or_shape' => [
                    'type' => [
                        'int',
                        'array{}' => [
                            'fields' => ['int' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['int_or_shape' => ['int' => 1.2]];
        $this->assertSame(
            ['int_or_shape.int' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testRequired(): void
    {
        // check default is false
        $schema = [
            'fields' => [
                'int' => [
                    'type' => 'int',
                ],
            ],
        ];
        $hash = [];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // set default to true
        $schema = [
            'required' => true,
            'fields' => [
                'int' => [
                    'type' => 'int',
                ],
            ],
        ];
        $hash = [];
        $this->assertSame(
            ['int' => 'Required field missing from hash.'],
            (new HashValidator($schema))->validate($hash)
        );

        // override field to true
        $schema = [
            'fields' => [
                'int' => [
                    'required' => true,
                    'type' => 'int',
                ],
            ],
        ];
        $hash = [];
        $this->assertSame(
            ['int' => 'Required field missing from hash.'],
            (new HashValidator($schema))->validate($hash)
        );

        // override field to false
        $schema = [
            'required' => true,
            'fields' => [
                'int' => [
                    'required' => false,
                    'type' => 'int',
                ],
            ],
        ];
        $hash = [];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // override shape to true
        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'required' => true,
                            'fields' => [
                                'int' => 'int',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => []];
        $this->assertSame(
            ['is_array.int' => 'Required field missing from hash.'],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testValidateStrict(): void
    {
        // check default is true
        $schema = ['fields' => ['int' => 'int']];
        $hash = ['extra' => true];
        $this->assertSame(
            ['extra' => 'Field does not exist in schema.'],
            (new HashValidator($schema))->validate($hash)
        );

        // override default to false
        $schema = ['strict' => false, 'fields' => ['int' => 'int']];
        $hash = ['extra' => true];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // test shape inherits default
        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['int' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => ['extra' => true]];
        $this->assertSame(
            ['is_array.extra' => 'Field does not exist in schema.'],
            (new HashValidator($schema))->validate($hash)
        );

        // override shape to false
        $schema = [
            'strict' => true,
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'strict' => false,
                            'fields' => ['int' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array' => ['extra' => true]];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // strict and required
        $schema = ['required' => true, 'fields' => ['int' => 'int']];
        $hash = ['extra' => true];
        $this->assertSame(
            [
                'extra' => 'Field does not exist in schema.',
                'int' => 'Required field missing from hash.',
            ],
            (new HashValidator($schema))->validate($hash)
        );
    }

    public function testAllowPaths(): void
    {
        // default to true
        $schema = [
            'fields' => [
                'is_array' => 'array',
            ],
        ];
        $hash = ['is_array.nested.field' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['nested' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array.nested' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['nested' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array.nested.extra' => []];
        $this->assertSame(
            ['is_array.nested' => 'Field value does not match expected type.'],
            (new HashValidator($schema))->validate($hash)
        );

        // disabled
        $schema = ['allowPaths' => false, 'fields' => ['this.is.an.int' => 'int']];
        $hash = ['this.is.an.int' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));

        // partial write with missing required fields
        $schema = [
            'fields' => [
                'int' => [
                    'required' => true,
                    'type' => 'int',
                ],
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['nested' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array.nested' => 1];
        $this->assertSame(
            ['int' => 'Required field missing from hash.'],
            (new HashValidator($schema))->validate($hash)
        );

        // partial write with required fields in existing hash
        $schema = [
            'fields' => [
                'int' => [
                    'required' => true,
                    'type' => 'int',
                ],
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['nested' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array.nested' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash, ['int' => 1]));

        // partial write with required nested fields in existing hash
        $schema = [
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['nested' => 'int'],
                        ],
                    ],
                ],
                'is_array_2' => [
                    'type' => [
                        'array{}' => [
                            'fields' => ['nested' => 'int'],
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array.nested' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash, ['is_array_2.nested' => 1]));
    }

    public function testReferences(): void
    {
        $schema = [
            'refs' => [
                'int_shape_ref' => [
                    'fields' => ['nested' => 'int'],
                ],
            ],
            'fields' => [
                'is_array' => [
                    'type' => [
                        'array{}' => [
                            'ref' => 'int_shape_ref',
                        ],
                    ],
                ],
            ],
        ];
        $hash = ['is_array.nested' => 1];
        $this->assertEmpty((new HashValidator($schema))->validate($hash));
    }
}
