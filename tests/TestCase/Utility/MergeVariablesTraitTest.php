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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use TestApp\Utility\Child;
use TestApp\Utility\Grandchild;

/**
 * MergeVariablesTrait test case
 */
class MergeVariablesTraitTest extends TestCase
{
    /**
     * Test merging vars as a list.
     */
    public function testMergeVarsAsList(): void
    {
        $object = new Grandchild();
        $object->mergeVars(['listProperty']);

        $expected = ['One', 'Two', 'Three', 'Four', 'Five'];
        $this->assertSame($expected, $object->listProperty);
    }

    /**
     * Test merging vars as an associative list.
     */
    public function testMergeVarsAsAssoc(): void
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
     */
    public function testMergeVarsAsAssocWithKeyValues(): void
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
        $this->assertSame($expected, $object->nestedProperty);
    }

    /**
     * Test merging vars with mixed modes.
     */
    public function testMergeVarsMixedModes(): void
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
        $this->assertEquals($expected, $object->listProperty);
    }

    /**
     * Test that merging variables with booleans in the class hierarchy
     * doesn't cause issues.
     */
    public function testMergeVarsWithBoolean(): void
    {
        $object = new Child();
        $object->mergeVars(['hasBoolean']);
        $this->assertSame(['test'], $object->hasBoolean);
    }
}
