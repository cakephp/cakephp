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
 * @link          https://cakephp.org CakePHP Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error\Debug;

use Cake\Error\Debug\ArrayItemNode;
use Cake\Error\Debug\ArrayNode;
use Cake\Error\Debug\ClassNode;
use Cake\Error\Debug\ConsoleFormatter;
use Cake\Error\Debug\PropertyNode;
use Cake\Error\Debug\ReferenceNode;
use Cake\Error\Debug\ScalarNode;
use Cake\Error\Debug\SpecialNode;
use Cake\TestSuite\TestCase;

/**
 * ConsoleFormatterTest
 */
class ConsoleFormatterTest extends TestCase
{
    /**
     * Test dumping a graph that contains all possible nodes.
     */
    public function testDump(): void
    {
        $node = new ClassNode('MyObject', 1);
        $node->addProperty(new PropertyNode('stringProp', 'public', new ScalarNode('string', 'value')));
        $node->addProperty(new PropertyNode('intProp', 'protected', new ScalarNode('int', 1)));
        $node->addProperty(new PropertyNode('floatProp', 'protected', new ScalarNode('float', 1.1)));
        $node->addProperty(new PropertyNode('boolProp', 'protected', new ScalarNode('bool', true)));
        $node->addProperty(new PropertyNode('nullProp', 'private', new ScalarNode('null', null)));
        $arrayNode = new ArrayNode([
            new ArrayItemNode(new ScalarNode('string', ''), new SpecialNode('too much')),
            new ArrayItemNode(new ScalarNode('int', 1), new ReferenceNode('MyObject', 1)),
        ]);
        $node->addProperty(new PropertyNode('arrayProp', 'public', $arrayNode));

        $formatter = new ConsoleFormatter();
        $result = $formatter->dump($node);

        $this->assertStringContainsString("\033[1;33m", $result, 'Should contain yellow code');
        $this->assertStringContainsString("\033[0;32m", $result, 'Should contain green code');
        $this->assertStringContainsString("\033[1;34m", $result, 'Should contain blue code');
        $this->assertStringContainsString("\033[0;36m", $result, 'Should contain cyan code');
        $expected = <<<TEXT
object(MyObject) id:1 {
  stringProp => 'value'
  protected intProp => (int) 1
  protected floatProp => (float) 1.1
  protected boolProp => true
  private nullProp => null
  arrayProp => [
    '' => too much,
    (int) 1 => object(MyObject) id:1 {}
  ]
}
TEXT;
        $noescape = preg_replace('/\\033\[\d\;\d+\;m([^\\\\]+)\\033\[0m/', '$1', $expected);
        $this->assertSame($expected, $noescape);
    }
}
