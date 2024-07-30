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

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\I18n\Parser\PoFileParser;
use Cake\TestSuite\TestCase;
use function Cake\I18n\__;
use function Cake\I18n\__d;
use function Cake\I18n\__x;

/**
 * Tests the PoFileLoader
 */
class PoFileParserTest extends TestCase
{
    /**
     * Locale folder path
     *
     * @var string
     */
    protected $path;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->path = Configure::read('App.paths.locales.0');
    }

    /**
     * Tear down method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        I18n::clear();
        I18n::setLocale(I18n::getDefaultLocale());
        Cache::clear('_cake_core_');
    }

    /**
     * Tests parsing a file with plurals and message context
     */
    public function testParse(): void
    {
        $parser = new PoFileParser();
        $file = $this->path . 'rule_1_po' . DS . 'default.po';
        $messages = $parser->parse($file);
        $this->assertCount(8, $messages);
        $expected = [
            'Plural Rule 1' => [
                '_context' => [
                    '' => 'Plural Rule 1 (translated)',
                ],
            ],
            '%d = 1' => [
                '_context' => [
                    'This is the context' => 'First Context translation',
                    'Another Context' => '%d = 1 (translated)',
                ],
            ],
            'p:%d = 0 or > 1' => [
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
            'p:%-5d = 0 or > 1' => [
                '_context' => [
                    '' => [
                        0 => '%-5d = 1 (translated)',
                        1 => '',
                        2 => '',
                        3 => '',
                        4 => '%-5d = 0 or > 1 (translated)',
                    ],
                ],
            ],
            '%d = 2' => [
                '_context' => [
                    'This is another translated context' => 'First Context translation',
                ],
            ],
            '%-6d = 3' => [
                '_context' => [
                    '' => '%-6d = 1 (translated)',
                ],
            ],
            'p:%-6d = 0 or > 1' => [
                '_context' => [
                    '' => [
                        0 => '%-6d = 1 (translated)',
                        1 => '',
                        2 => '',
                        3 => '',
                        4 => '%-6d = 0 or > 1 (translated)',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, $messages);
    }

    /**
     * Tests parsing a file with multiline keys and values
     */
    public function testParseMultiLine(): void
    {
        $parser = new PoFileParser();
        $file = $this->path . 'en' . DS . 'default.po';
        $messages = $parser->parse($file);
        $this->assertCount(13, $messages);
        $this->assertTextEquals("v\nsecond line", $messages["valid\nsecond line"]['_context']['']);

        $this->assertTextEquals("new line: \nno new line: \\n (translated)", $messages["new line: \nno new line: \\n"]['_context']['']);
    }

    /**
     * Test parsing a file with quoted strings
     */
    public function testQuotedString(): void
    {
        $parser = new PoFileParser();
        $file = $this->path . 'en' . DS . 'default.po';
        $messages = $parser->parse($file);

        $this->assertTextEquals('this is a "quoted string" (translated)', $messages['this is a "quoted string"']['_context']['']);
    }

    /**
     * Test parsing a file with message context on some msgid values.
     *
     * This behavior is not ideal, but more thorough solutions
     * would break compatibility. Perhaps this is something we can
     * reconsider in 4.x
     */
    public function testParseContextOnSomeMessages(): void
    {
        $parser = new PoFileParser();
        $file = $this->path . 'en' . DS . 'context.po';
        $messages = $parser->parse($file);

        I18n::setTranslator('default', function () use ($messages): Package {
            $package = new Package('default');
            $package->setMessages($messages);

            return $package;
        }, 'en_CA');

        $this->assertSame('En cours', $messages['Pending']['_context']['']);
        $this->assertSame('En cours - context', $messages['Pending']['_context']['Pay status']);
        $this->assertSame('En resolved', $messages['Resolved']['_context']['']);
        $this->assertSame('En resolved - context', $messages['Resolved']['_context']['Pay status']);

        $key = '{0,plural,=0{Je suis}=1{Je suis}=2{Nous sommes} other{Nous sommes}}';
        $this->assertStringContainsString("I've", $messages[$key]['_context']['origin']);

        // Confirm actual behavior
        I18n::setLocale('en_CA');
        $this->assertSame('En cours', __('Pending'));
        $this->assertSame('En cours - context', __x('Pay status', 'Pending'));
        $this->assertSame('En resolved', __('Resolved'));
        $this->assertSame('En resolved - context', __x('Pay status', 'Resolved'));
        $this->assertSame("I've", __x('origin', $key, [1]));
        $this->assertSame('We are', __x('origin', $key, [3]));
    }

    /**
     * Test parsing context based messages
     */
    public function testParseContextMessages(): void
    {
        $parser = new PoFileParser();
        $file = $this->path . 'en' . DS . 'context.po';
        $messages = $parser->parse($file);

        I18n::setTranslator('default', function () use ($messages): Package {
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
     */
    public function testPlurals(): void
    {
        I18n::getTranslator('default', 'de_DE');

        // Check translated messages
        I18n::setLocale('de_DE');
        $this->assertSame('Standorte', __d('wa', 'Locations'));
        I18n::setLocale('en_EN');
        $this->assertSame('Locations', __d('wa', 'Locations'));
    }
}
