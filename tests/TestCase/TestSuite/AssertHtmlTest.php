<?php
namespace Cake\Test\Fixture;

use Cake\TestSuite\TestCase;
use PHPUnit_Framework_ExpectationFailedException;

/**
 * This class helps in indirectly testing the functionality of TestCase::assertHtml
 */
class AssertHtmlTest extends TestCase
{
    public function testAssertHtmlWhitespace()
    {
        $input = <<<HTML
<div class="wrapper">
    <h4 class="widget-title">Popular tags
        <i class="i-icon"></i>
    </h4>
</div>
HTML;
        $pattern = [
            'div' => ['class' => 'wrapper'],
            'h4' => ['class' => 'widget-title'], 'Popular tags',
            'i' => ['class' => 'i-icon'], '/i',
            '/h4',
            '/div'
        ];
        $this->assertHtml($pattern, $input);
    }
    /**
     * test assertHtml works with single and double quotes
     *
     * @return void
     */
    public function testAssertHtmlQuoting()
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

        $input = "<span><strong>Text</strong></span>";
        $pattern = [
            '<span',
            '<strong',
            'Text',
            '/strong',
            '/span'
        ];
        $this->assertHtml($pattern, $input);

        $input = "<span class='active'><strong>Text</strong></span>";
        $pattern = [
            'span' => ['class'],
            '<strong',
            'Text',
            '/strong',
            '/span'
        ];
        $this->assertHtml($pattern, $input);
    }

    /**
     * Test that assertHtml runs quickly.
     *
     * @return void
     */
    public function testAssertHtmlRuntimeComplexity()
    {
        $pattern = [
            'div' => [
                'attr1' => 'val1',
                'attr2' => 'val2',
                'attr3' => 'val3',
                'attr4' => 'val4',
                'attr5' => 'val5',
                'attr6' => 'val6',
                'attr7' => 'val7',
                'attr8' => 'val8',
            ],
            'My div',
            '/div'
        ];
        $input = '<div attr8="val8" attr6="val6" attr4="val4" attr2="val2"' .
            ' attr1="val1" attr3="val3" attr5="val5" attr7="val7" />' .
            'My div' .
            '</div>';
        $this->assertHtml($pattern, $input);
    }


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
     * test assertions fail when attributes are wrong.
     *
     * @return void
     */
    public function testBadAssertHtmlInvalidAttribute()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            'a' => ['hRef' => '/test.html', 'clAss' => 'active'],
            'My link2',
            '/a'
        ];
        try {
            $this->assertHtml($pattern, $input);
            $this->fail('Assertion should fail');
        } catch (PHPUnit_Framework_ExpectationFailedException $e) {
            $this->assertContains(
                'Attribute did not match. Was expecting Attribute "clAss" == "active"',
                $e->getMessage()
            );
        }
    }

    /**
     * test assertion failure on incomplete HTML
     *
     * @return void
     */
    public function testBadAssertHtmlMissingTags()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            '<a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a'
        ];
        try {
            $this->assertHtml($pattern, $input);
        } catch (PHPUnit_Framework_ExpectationFailedException $e) {
            $this->assertContains(
                'Item #1 / regex #0 failed: Open <a tag',
                $e->getMessage()
            );
        }
    }
}
