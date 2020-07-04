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
namespace Cake\Test\TestCase\I18n\Parser;

use Cake\Core\Configure;
use Cake\I18n\Parser\MoFileParser;
use Cake\TestSuite\TestCase;

/**
 * Tests the MoFileLoader
 */
class MoFileParserTest extends TestCase
{
    /**
     * Locale folder path
     *
     * @var string
     */
    protected $path;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->path = Configure::read('App.paths.locales.0');
    }

    /**
     * Tests parsing a file with plurals and message context
     *
     * @return void
     */
    public function testParse()
    {
        $parser = new MoFileParser();
        $file = $this->path . 'rule_1_mo' . DS . 'core.mo';
        $messages = $parser->parse($file);
        $this->assertCount(3, $messages);
        $expected = [
            '%d = 1 (from core)' => [
                '_context' => [
                    '' => '%d = 1 (from core translated)',
                ],
            ],
            '%d = 0 or > 1 (from core)' => [
                '_context' => [
                    '' => [
                        '%d = 1 (from core translated)',
                        '%d = 0 or > 1 (from core translated)',
                    ],
                ],
            ],
            'Plural Rule 1 (from core)' => [
                '_context' => [
                    '' => 'Plural Rule 1 (from core translated)',
                ],
            ],
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
        $parser = new MoFileParser();
        $file = $this->path . 'rule_0_mo' . DS . 'core.mo';
        $messages = $parser->parse($file);
        $this->assertCount(4, $messages);
        $expected = [
            'Plural Rule 1 (from core)' => [
                '_context' => [
                    '' => 'Plural Rule 0 (from core translated)',
                ],
            ],
            '%d = 1 (from core)' => [
                '_context' => [
                    '' => '%d ends with any # (from core translated)',
                ],
            ],
            '%d = 0 or > 1 (from core)' => [
                '_context' => [
                    '' => [
                        '%d ends with any # (from core translated)',
                    ],
                ],
            ],
            "new line: \nno new line: \\n" => [
                '_context' => [
                    '' => "new line: \nno new line: \\n (translated)",
                ],
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
        $parser = new MoFileParser();
        $file = $this->path . 'rule_9_mo' . DS . 'core.mo';
        $messages = $parser->parse($file);
        $this->assertCount(3, $messages);
        $expected = [
            '%d = 1 (from core)' => [
                '_context' => [
                    '' => '%d is 1 (from core translated)',
                ],
            ],
            '%d = 0 or > 1 (from core)' => [
                '_context' => [
                    '' => [
                        '%d is 1 (from core translated)',
                        '%d ends in 2-4, not 12-14 (from core translated)',
                        '%d everything else (from core translated)',
                    ],
                ],
            ],
            'Plural Rule 1 (from core)' => [
                '_context' => [
                    '' => 'Plural Rule 9 (from core translated)',
                ],
            ],
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
        $parser = new MoFileParser();
        $file = $this->path . 'rule_0_mo' . DS . 'default.mo';
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
                    'Another Context' => '%d = 1 (translated)',
                ],
            ],
            '%d = 0 or > 1' => [
                '_context' => [
                    'Another Context' => [
                        0 => '%d = 1 (translated)',
                        1 => '%d = 0 or > 1 (translated)',
                    ],
                ],
            ],
            '%-5d = 1' => [
                '_context' => [
                    '' => '%-5d = 1 (translated)',
                ],
            ],
            '%-5d = 0 or > 1' => [
                '_context' => [
                    '' => [
                        '%-5d = 1 (translated)',
                        '%-5d = 0 or > 1 (translated)',
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $messages);
    }
}
