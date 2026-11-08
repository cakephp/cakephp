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
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link https://cakephp.org CakePHP(tm) Project
 * @since 6.0.0
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use Cake\View\HtmlString;
use Cake\View\HtmlStringable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use function Cake\Core\h;

/**
 * Tests trusted HTML values and content escaping.
 */
class HtmlStringTest extends TestCase
{
    /**
     * Verify that markup survives rendering and serialization unchanged.
     *
     * @param string $value The markup to wrap.
     */
    #[DataProvider('valueProvider')]
    public function testValue(string $value): void
    {
        $html = new HtmlString($value);

        $this->assertSame($value, (string)$html);
        $this->assertSame($value, HtmlString::render($html));
        $this->assertSame(json_encode($value), json_encode($html));
        $this->assertEquals($html, unserialize(serialize($html)));
    }

    /**
     * Provide empty, encoded, and Unicode markup values.
     *
     * @return array<string, array{string}>
     */
    public static function valueProvider(): array
    {
        return [
            'empty' => [''],
            'markup' => ['<strong title="Save & continue">Save</strong>'],
            'entities' => ['&amp; &#039; &quot;'],
            'unicode' => ['<span>Crème brûlée 🍰</span>'],
        ];
    }

    /**
     * Escape ordinary text, including existing entities and invalid UTF-8.
     */
    public function testRenderEscapesText(): void
    {
        $this->assertSame(
            '&lt;strong&gt;&quot;Save&quot; &amp; &#039;continue&#039;&lt;/strong&gt;',
            HtmlString::render('<strong>"Save" & \'continue\'</strong>'),
        );
        $this->assertSame('&amp;amp;', HtmlString::render('&amp;'));
        $this->assertSame("\u{FFFD}&lt;", HtmlString::render("\xFF<"));
    }

    /**
     * Use CakePHP's current encoding instead of assuming UTF-8.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRenderUsesCurrentEncoding(): void
    {
        $encoding = mb_internal_encoding();
        try {
            mb_internal_encoding('ISO-8859-1');
            $this->assertSame("\xE9&lt;", HtmlString::render("\xE9<"));
        } finally {
            mb_internal_encoding($encoding);
        }
    }

    /**
     * Honor the HTML contract for implementations supplied by applications.
     */
    public function testCustomHtmlStringable(): void
    {
        $html = Mockery::mock(HtmlStringable::class);
        $html->shouldReceive('__toString')->andReturn('<em>Custom</em>');

        $this->assertSame('<em>Custom</em>', HtmlString::render($html));
        $this->assertSame('&lt;em&gt;Custom&lt;/em&gt;', h($html));
    }
}
