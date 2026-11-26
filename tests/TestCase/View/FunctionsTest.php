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
use Cake\View\Helper\HtmlHelper;
use Cake\View\HtmlString;
use Cake\View\View;
use function Cake\Core\h;
use function Cake\View\html;

/**
 * Tests the namespaced view convenience functions.
 */
class FunctionsTest extends TestCase
{
    /**
     * Preserve markup, entities, Unicode, and empty content in HTML values.
     */
    public function testHtml(): void
    {
        foreach (['', '<strong>Save &amp; continue</strong>', '<span>Crème brûlée 🍰</span>'] as $value) {
            $html = html($value);

            $this->assertInstanceOf(HtmlString::class, $html);
            $this->assertSame($value, (string)$html);
        }
    }

    /**
     * Trust wrapped link content while preserving attribute and explicit escaping.
     */
    public function testHtmlEscaping(): void
    {
        $helper = new HtmlHelper(new View());
        $html = html('<strong title="Save">Save</strong>');

        $this->assertSame(
            '<a href="/save" title="&quot;Save&quot; &amp; continue"><strong title="Save">Save</strong></a>',
            $helper->link($html, '/save', ['title' => html('"Save" & continue')]),
        );
        $this->assertSame('&lt;strong title=&quot;Save&quot;&gt;Save&lt;/strong&gt;', h($html));
    }
}
