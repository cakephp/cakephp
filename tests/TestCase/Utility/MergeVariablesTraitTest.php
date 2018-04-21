<?php
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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\MergeVariablesTrait;

class Base
{

    use MergeVariablesTrait;

    public $hasBoolean = false;

    public $listProperty = ['One'];

    public $assocProperty = ['Red'];

    public function mergeVars($properties, $options = [])
    {
        return $this->_mergeVars($properties, $options);
    }
}

class Child extends Base
{

    public $hasBoolean = ['test'];

    public $listProperty = ['Two', 'Three'];

    public $assocProperty = [
        'Green' => ['lime'],
        'Orange'
    ];

    public $nestedProperty = [
        'Red' => [
            'apple' => 'gala',
        ],
        'Green' => [
            'citrus' => 'lime'
        ],
    ];
}

class Grandchild extends Child
{

    public $listProperty = ['Four', 'Five'];

    public $assocProperty = [
        'Green' => ['apple'],
        'Yellow' => ['banana']
    ];

    public $nestedProperty = [
        'Red' => [
            'citrus' => 'blood orange',
        ],
        'Green' => [
            'citrus' => 'key lime'
        ],
    ];
}

/**
 * MergeVariablesTrait test case
 */
class MergeVariablesTraitTest extends TestCase
{

    /**
     * Test merging vars as a list.
     *
     * @return void
     */
    public function testMergeVarsAsList()
    {
        $object = new Grandchild();
        $object->mergeVars(['listProperty']);

        $expected = ['One', 'Two', 'Three', 'Four', 'Five'];
        $this->assertSame($expected, $object->listProperty);
    }

    /**
     * Test merging vars as an associative list.
     *
     * @return void
     */
    public function testMergeVarsAsAssoc()
    {
        $object = new Grandchild();
        $object->mergeVars(['assocProperty'], ['associative' => ['assocProperty']]);
        $expected = [
            'Red' => null,
            'Orange' => null,
            'Green' => ['apple'],
            'Yellow' => ['banana'],
        ];
        $this->assertEquals($expected, $object->assocProperty);
    }

    /**
     * Test merging variable in associated properties that contain
     * additional keys.
     *
     * @return void
     */
    public function testMergeVarsAsAssocWithKeyValues()
    {
        $object = new Grandchild();
        $object->mergeVars(['nestedProperty'], ['associative' => ['nestedProperty']]);

        $expected = [
            'Red' => [
                'citrus' => 'blood orange',
            ],
            'Green' => [
                'citrus' => 'key lime',
            ],
        ];
        $this->assertEquals($expected, $object->nestedProperty);
    }

    /**
     * Test merging vars with mixed modes.
     *
     * @return void
     */
    public function testMergeVarsMixedModes()
    {
        $object = new Grandchild();
        $object->mergeVars(['assocProperty', 'listProperty'], ['associative' => ['assocProperty']]);
        $expected = [
            'Red' => null,
            'Orange' => null,
            'Green' => ['apple'],
            'Yellow' => ['banana'],
        ];
        $this->assertEquals($expected, $object->assocProperty);

        $expected = ['One', 'Two', 'Three', 'Four', 'Five'];
        $this->assertSame($expected, $object->listProperty);
    }

    /**
     * Test that merging variables with booleans in the class hierarchy
     * doesn't cause issues.
     *
     * @return void
     */
    public function testMergeVarsWithBoolean()
    {
        $object = new Child();
        $object->mergeVars(['hasBoolean']);
        $this->assertEquals(['test'], $object->hasBoolean);
    }
}
