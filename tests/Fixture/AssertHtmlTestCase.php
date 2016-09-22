<?php
namespace Cake\Test\Fixture;

use Cake\TestSuite\TestCase;

/**
 * This class helps in indirectly testing the functionalities of TestCase::assertHtml
 */
class AssertHtmlTestCase extends TestCase
{

    /**
     * test that assertHtml knows how to handle correct quoting.
     *
     * @return void
     */
    public function testAssertHtmlQuotes()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<a href='/test.html' class='active'>My link</a>";
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<a href='/test.html' class='active'>My link</a>";
        $pattern = [
            'a' => ['href' => 'preg:/.*\.html/', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);
    }

    /**
     * testNumericValuesInExpectationForAssertHtml
     *
     * @return void
     */
    public function testNumericValuesInExpectationForAssertHtml()
    {
        $value = 220985;

        $input = '<p><strong>' . $value . '</strong></p>';
        $pattern = [
            '<p',
                '<strong',
                    $value,
                '/strong',
            '/p'
        ];
        $this->assertHtml($pattern, $input);

        $input = '<p><strong>' . $value . '</strong></p><p><strong>' . $value . '</strong></p>';
        $pattern = [
            '<p',
                '<strong',
                    $value,
                '/strong',
            '/p',
            '<p',
                '<strong',
                    $value,
                '/strong',
            '/p',
        ];
        $this->assertHtml($pattern, $input);

        $input = '<p><strong>' . $value . '</strong></p><p id="' . $value . '"><strong>' . $value . '</strong></p>';
        $pattern = [
            '<p',
                '<strong',
                    $value,
                '/strong',
            '/p',
            'p' => ['id' => $value],
                '<strong',
                    $value,
                '/strong',
            '/p',
        ];
        $this->assertHtml($pattern, $input);
    }

    /**
     * testBadAssertHtml
     *
     * @return void
     */
    public function testBadAssertHtml()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            'a' => ['hRef' => '/test.html', 'clAss' => 'active'],
            'My link2',
            '/a'
        ];
        $this->assertHtml($pattern, $input);
    }

    /**
     * testBadAssertHtml
     *
     * @return void
     */
    public function testBadAssertHtml2()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            '<a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        $this->assertHtml($pattern, $input);
    }
}
