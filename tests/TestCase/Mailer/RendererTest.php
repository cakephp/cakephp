<?php
declare(strict_types=1);
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Mailer\Renderer;
use Cake\TestSuite\TestCase;

class TestRenderer extends Renderer
{
    /**
     * Wrap to protected method
     *
     * @param string $text
     * @param int $length
     * @return array
     */
    public function wrap($text, $length = Renderer::LINE_LENGTH_MUST)
    {
        return parent::wrap($text, $length);
    }
}

/**
 * RendererTest class
 */
class RendererTest extends TestCase
{
    /**
     * testWrap method
     *
     * @return void
     */
    public function testWrap()
    {
        $renderer = new TestRenderer();

        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $renderer->wrap($text, Renderer::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci,',
            'non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
        $result = $renderer->wrap($text, Renderer::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
            'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
            'amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
        $result = $renderer->wrap($text, Renderer::LINE_LENGTH_SHOULD);
        $expected = [
            '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
            'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
            'pellentesque accumsan amet.<hr></p>',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $renderer->wrap($text, Renderer::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
            '<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
            'nisi, vehicula pellentesque accumsan amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum <a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">ok</a>';
        $result = $renderer->wrap($text, Renderer::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            '<a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">',
            'ok</a>',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite ok.';
        $result = $renderer->wrap($text, Renderer::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            'withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite',
            'ok.',
            '',
        ];
        $this->assertSame($expected, $result);
    }
}
