<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Formatter\SprintfFormatter;
use Cake\TestSuite\TestCase;

/**
 * SprintfFormatter tests
 *
 */
class SprintfFormatterTest extends TestCase
{

    /**
     * Tests that variables are interpolated correctly
     *
     * @return void
     */
    public function testFormatSimple()
    {
        $formatter = new SprintfFormatter();
        $this->assertEquals('Hello José', $formatter->format('en_US', 'Hello %s', ['José']));
        $this->assertEquals('1 Orange', $formatter->format('en_US', '%d %s', [1, 'Orange']));
    }

    /**
     * Tests that plural forms are selected for the passed locale
     *
     * @return void
     */
    public function testFormatPlural()
    {
        $formatter = new SprintfFormatter();
        $messages = ['%d is 0', '%d is 1', '%d is 2', '%d is 3', '%d > 11'];
        $this->assertEquals('1 is 1', $formatter->format('ar', $messages, ['_count' => 1]));
        $this->assertEquals('2 is 2', $formatter->format('ar', $messages, ['_count' => 2]));
        $this->assertEquals('20 > 11', $formatter->format('ar', $messages, ['_count' => 20]));
    }

    /**
     * Tests that strings stored inside context namespaces can also be formatted
     *
     * @return void
     */
    public function testFormatWithContext()
    {
        $messages = [
            'simple' => [
                '_context' => [
                    'context a' => 'Text "a" %s',
                    'context b' => 'Text "b" %s'
                ]
            ],
            'complex' => [
                '_context' => [
                    'context b' => [
                        0 => 'Only one',
                        1 => 'there are %d'
                    ]
                ]
            ]
        ];

        $formatter = new SprintfFormatter();
        $this->assertEquals(
            'Text "a" is good',
            $formatter->format('en', $messages['simple'], ['_context' => 'context a', 'is good'])
        );
        $this->assertEquals(
            'Text "b" is good',
            $formatter->format('en', $messages['simple'], ['_context' => 'context b', 'is good'])
        );
        $this->assertEquals(
            'Text "a" is good',
            $formatter->format('en', $messages['simple'], ['is good'])
        );

        $this->assertEquals(
            'Only one',
            $formatter->format('en', $messages['complex'], ['_context' => 'context b', '_count' => 1])
        );

        $this->assertEquals(
            'there are 2',
            $formatter->format('en', $messages['complex'], ['_context' => 'context b', '_count' => 2])
        );
    }

    /**
     * Tests that it is possible to provide a singular fallback when passing a string message.
     * This is useful for getting quick feedback on the code during development instead of
     * having to provide all plural forms even for the default language
     *
     * @return void
     */
    public function testSingularFallback()
    {
        $formatter = new SprintfFormatter();
        $singular = 'one thing';
        $plural = 'many things';
        $this->assertEquals($singular, $formatter->format('en_US', $plural, ['_count' => 1, '_singular' => $singular]));
        $this->assertEquals($plural, $formatter->format('en_US', $plural, ['_count' => 2, '_singular' => $singular]));
    }
}
