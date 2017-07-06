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

use Cake\I18n\Parser\MoFileParser;
use Cake\TestSuite\TestCase;

/**
 * Tests the MoFileLoader
 */
class MoFileParserTest extends TestCase
{

    /**
     * Tests parsing a file with plurals and message context
     *
     * @return void
     */
    public function testParse()
    {
        $parser = new MoFileParser;
        $file = APP . 'Locale' . DS . 'rule_1_mo' . DS . 'core.mo';
        $messages = $parser->parse($file);
        $this->assertCount(3, $messages);
        $expected = [
            '%d = 1 (from core)' => '%d = 1 (from core translated)',
            '%d = 0 or > 1 (from core)' => [
                '%d = 1 (from core translated)',
                '%d = 0 or > 1 (from core translated)'
            ],
            'Plural Rule 1 (from core)' => 'Plural Rule 1 (from core translated)'
        ];
        $this->assertEquals($expected, $messages);
    }

    /**
     * Tests parsing a file with single form plurals
     *
     * @return void
     */
    public function testParse0()
    {
        $parser = new MoFileParser;
        $file = APP . 'Locale' . DS . 'rule_0_mo' . DS . 'core.mo';
        $messages = $parser->parse($file);
        $this->assertCount(3, $messages);
        $expected = [
            'Plural Rule 1 (from core)' => 'Plural Rule 0 (from core translated)',
            '%d = 1 (from core)' => '%d ends with any # (from core translated)',
            '%d = 0 or > 1 (from core)' => [
                '%d ends with any # (from core translated)',
            ],
        ];
        $this->assertEquals($expected, $messages);
    }

    /**
     * Tests parsing a file with larger plural forms
     *
     * @return void
     */
    public function testParse2()
    {
        $parser = new MoFileParser;
        $file = APP . 'Locale' . DS . 'rule_9_mo' . DS . 'core.mo';
        $messages = $parser->parse($file);
        $this->assertCount(3, $messages);
        $expected = [
            '%d = 1 (from core)' => '%d is 1 (from core translated)',
            '%d = 0 or > 1 (from core)' => [
                '%d is 1 (from core translated)',
                '%d ends in 2-4, not 12-14 (from core translated)',
                '%d everything else (from core translated)'
            ],
            'Plural Rule 1 (from core)' => 'Plural Rule 9 (from core translated)'
        ];
        $this->assertEquals($expected, $messages);
    }

    /**
     * Tests parsing a file with plurals and message context
     *
     * @return void
     */
    public function testParseFull()
    {
        $parser = new MoFileParser;
        $file = APP . 'Locale' . DS . 'rule_0_mo' . DS . 'default.mo';
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
                '%-5d = 1 (translated)',
                '%-5d = 0 or > 1 (translated)'
            ]
        ];
        $this->assertEquals($expected, $messages);
    }
}
