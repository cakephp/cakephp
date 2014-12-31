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
namespace Cake\Test\TestCase\I18n\Parser;

use Cake\I18n\Parser\PoFileParser;
use Cake\TestSuite\TestCase;

/**
 * Tests the PoFileLoader
 *
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
            'Plural Rule 1' => 'Plural Rule 1 (translated)',
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
            '%-5d = 1' => '%-5d = 1 (translated)',
            '%-5d = 0 or > 1' => [
                0 => '%-5d = 1 (translated)',
                1 => '',
                2 => '',
                3 => '',
                4 => '%-5d = 0 or > 1 (translated)'
            ]
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
        $this->assertTextEquals("v\nsecond line", $messages["valid\nsecond line"]);
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

        $this->assertTextEquals('this is a "quoted string" (translated)', $messages['this is a "quoted string"']);
    }
}
