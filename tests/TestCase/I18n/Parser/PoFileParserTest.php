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
use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Parser\PoFileParser;
use Cake\TestSuite\TestCase;

/**
 * Tests the PoFileLoader
 */
class PoFileParserTest extends TestCase
{
    protected $locale;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->locale = I18n::getLocale();
    }

    /**
     * Tear down method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        I18n::clear();
        I18n::setLocale($this->locale);
        Cache::clear(false, '_cake_core_');
    }

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
        $this->assertCount(8, $messages);
        $expected = [
            'Plural Rule 1' => [
                '_context' => [
                    '' => 'Plural Rule 1 (translated)'
                ]
            ],
            '%d = 1' => [
                '_context' => [
                    'This is the context' => 'First Context translation',
                    'Another Context' => '%d = 1 (translated)'
                ]
            ],
            'p:%d = 0 or > 1' => [
                '_context' => [
                    'Another Context' => [
                        0 => '%d = 1 (translated)',
                        1 => '%d = 0 or > 1 (translated)'
                    ]
                ]
            ],
            '%-5d = 1' => [
                '_context' => [
                    '' => '%-5d = 1 (translated)'
                ]
            ],
            'p:%-5d = 0 or > 1' => [
                '_context' => [
                    '' => [
                        0 => '%-5d = 1 (translated)',
                        1 => '',
                        2 => '',
                        3 => '',
                        4 => '%-5d = 0 or > 1 (translated)'
                    ]
                ]
            ],
            '%d = 2' => [
                '_context' => [
                    'This is another translated context' => 'First Context translation',
                ]
            ],
            '%-6d = 3' => [
                '_context' => [
                    '' => '%-6d = 1 (translated)',
                ]
            ],
            'p:%-6d = 0 or > 1' => [
                '_context' => [
                    '' => [
                        0 => '%-6d = 1 (translated)',
                        1 => '',
                        2 => '',
                        3 => '',
                        4 => '%-6d = 0 or > 1 (translated)',
                    ]
                ]
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

        I18n::setTranslator('default', function () use ($messages) {
            $package = new Package('default');
            $package->setMessages($messages);

            return $package;
        }, 'en_CA');

        $this->assertSame('En cours', $messages['Pending']['_context']['']);
        $this->assertSame('En cours - context', $messages['Pending']['_context']['Pay status']);
        $this->assertSame('En resolved', $messages['Resolved']['_context']['']);
        $this->assertSame('En resolved - context', $messages['Resolved']['_context']['Pay status']);

        $key = '{0,plural,=0{Je suis}=1{Je suis}=2{Nous sommes} other{Nous sommes}}';
        $this->assertContains("I've", $messages[$key]['_context']['origin']);

        // Confirm actual behavior
        I18n::setLocale('en_CA');
        $this->assertSame('En cours', __('Pending'));
        $this->assertSame('En cours - context', __x('Pay status', 'Pending'));
        $this->assertSame('En resolved', __('Resolved'));
        $this->assertSame('En resolved - context', __x('Pay status', 'Resolved'));
        $this->assertSame("I've", __x('origin', $key, [1]));
        $this->assertSame("We are", __x('origin', $key, [3]));
    }

    /**
     * Test parsing context based messages
     *
     * @return void
     */
    public function testParseContextMessages()
    {
        $parser = new PoFileParser();
        $file = APP . 'Locale' . DS . 'en' . DS . 'context.po';
        $messages = $parser->parse($file);

        I18n::setTranslator('default', function () use ($messages) {
            $package = new Package('default');
            $package->setMessages($messages);

            return $package;
        }, 'en_US');

        // Check translated messages
        I18n::setLocale('en_US');
        $this->assertSame('Titel mit Kontext', __x('context', 'title'));
        $this->assertSame('Titel mit anderem Kontext', __x('another_context', 'title'));
        $this->assertSame('Titel ohne Kontext', __('title'));
    }

    /**
     * Test parsing plurals
     *
     * @return void
     */
    public function testPlurals()
    {
        $parser = new PoFileParser();
        $file = APP . 'Locale' . DS . 'de' . DS . 'wa.po';
        $messages = $parser->parse($file);

        I18n::getTranslator('default', 'de_DE', function () use ($messages) {
            $package = new Package('default');
            $package->setMessages($messages);

            return $package;
        });

        // Check translated messages
        I18n::setLocale('de_DE');
        $this->assertEquals('Standorte', __d('wa', 'Locations'));
        I18n::setLocale('en_EN');
        $this->assertEquals('Locations', __d('wa', 'Locations'));
    }
}
