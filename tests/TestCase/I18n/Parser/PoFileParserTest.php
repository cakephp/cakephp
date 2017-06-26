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
namespace Cake\Test\TestCase\I18n\Parser;

use Aura\Intl\Package;
use Cake\I18n\I18n;
use Cake\I18n\Parser\PoFileParser;
use Cake\TestSuite\TestCase;

/**
 * Tests the PoFileLoader
 */
class PoFileParserTest extends TestCase
{

    /**
     * Tests parsing a file with plurals and message context
     *
     * @return void
     */
    public function testParse()
    {
        $parser = new PoFileParser;
        $file = APP . 'Locale' . DS . 'rule_1_po' . DS . 'default.po';
        $messages = $parser->parse($file);
        $this->assertCount(5, $messages);
        $expected = [
            'Plural Rule 1' => [
                '_context' => [
                    '' => 'Plural Rule 1 (translated)',
                ],
            ],
            '%d = 1' => [
                '_context' => [
                    'This is the context' => 'First Context trasnlation',
                    'Another Context' => '%d = 1 (translated)'
                ]
            ],
            '%d = 0 or > 1' => [
                '_context' => [
                    'Another Context' => [
                        0 => '%d = 1 (translated)',
                        1 => '%d = 0 or > 1 (translated)'
                    ]
                ]
            ],
            '%-5d = 1' => [
                '_context' => [
                    '' => '%-5d = 1 (translated)',
                ],
            ],
            '%-5d = 0 or > 1' => [
                '_context' => [
                    '' => [
                        0 => '%-5d = 1 (translated)',
                        1 => '',
                        2 => '',
                        3 => '',
                        4 => '%-5d = 0 or > 1 (translated)'
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $messages);
    }

    /**
     * Tests parsing a file with multiline keys and values
     *
     * @return void
     */
    public function testParseMultiLine()
    {
        $parser = new PoFileParser;
        $file = APP . 'Locale' . DS . 'en' . DS . 'default.po';
        $messages = $parser->parse($file);
        $this->assertCount(12, $messages);
        $this->assertTextEquals("v\nsecond line", $messages["valid\nsecond line"]['_context']['']);
    }

    /**
     * Test parsing a file with quoted strings
     *
     * @return void
     */
    public function testQuotedString()
    {
        $parser = new PoFileParser;
        $file = APP . 'Locale' . DS . 'en' . DS . 'default.po';
        $messages = $parser->parse($file);

        $this->assertTextEquals('this is a "quoted string" (translated)', $messages['this is a "quoted string"']['_context']['']);
    }

    /**
     * Test parsing a file with message context on some msgid values.
     *
     * This behavior is not ideal, but more thorough solutions
     * would break compatibility. Perhaps this is something we can
     * reconsider in 4.x
     *
     * @return void
     */
    public function testParseContextOnSomeMessages()
    {
        $parser = new PoFileParser();
        $file = APP . 'Locale' . DS . 'en' . DS . 'context.po';
        $messages = $parser->parse($file);

        I18n::translator('default', 'de_DE', function () use ($messages) {
            $package = new Package('default');
            $package->setMessages($messages);

            return $package;
        });
        $this->assertSame('En cours', $messages['Pending']['_context']['']);
        $this->assertSame('En cours - context', $messages['Pending']['_context']['Pay status']);
        $this->assertSame('En resolved', $messages['Resolved']['_context']['']);
        $this->assertSame('En resolved - context', $messages['Resolved']['_context']['Pay status']);

        // Confirm actual behavior
        I18n::locale('de_DE');
        $this->assertSame('En cours', __('Pending'));
        $this->assertSame('En cours - context', __x('Pay status', 'Pending'));
        $this->assertSame('En resolved', __('Resolved'));
        $this->assertSame('En resolved - context', __x('Pay status', 'Resolved'));
    }
}
