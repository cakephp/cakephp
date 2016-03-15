<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * TextTest class
 *
 */
class TextTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->encoding = mb_internal_encoding();
        $this->Text = new Text();
    }

    public function tearDown()
    {
        parent::tearDown();
        mb_internal_encoding($this->encoding);
        unset($this->Text);
    }

    /**
     * testUuidGeneration method
     *
     * @return void
     */
    public function testUuidGeneration()
    {
        $result = Text::uuid();
        $pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";
        $match = (bool)preg_match($pattern, $result);
        $this->assertTrue($match);
    }

    /**
     * testMultipleUuidGeneration method
     *
     * @return void
     */
    public function testMultipleUuidGeneration()
    {
        $check = [];
        $count = mt_rand(10, 1000);
        $pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";

        for ($i = 0; $i < $count; $i++) {
            $result = Text::uuid();
            $match = (bool)preg_match($pattern, $result);
            $this->assertTrue($match);
            $this->assertFalse(in_array($result, $check));
            $check[] = $result;
        }
    }

    /**
     * testInsert method
     *
     * @return void
     */
    public function testInsert()
    {
        $string = 'some string';
        $expected = 'some string';
        $result = Text::insert($string, []);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = :sum. Cake is :adjective.';
        $expected = '2 + 2 = 4. Cake is yummy.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = %sum. Cake is %adjective.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy'], ['before' => '%']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = 2sum2. Cake is 9adjective9.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy'], ['format' => '/([\d])%s\\1/']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = 12sum21. Cake is 23adjective45.';
        $expected = '2 + 2 = 4. Cake is 23adjective45.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy'], ['format' => '/([\d])([\d])%s\\2\\1/']);
        $this->assertEquals($expected, $result);

        $string = ':web :web_site';
        $expected = 'www http';
        $result = Text::insert($string, ['web' => 'www', 'web_site' => 'http']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = <sum. Cake is <adjective>.';
        $expected = '2 + 2 = <sum. Cake is yummy.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy'], ['before' => '<', 'after' => '>']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = \:sum. Cake is :adjective.';
        $expected = '2 + 2 = :sum. Cake is yummy.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = !:sum. Cake is :adjective.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy'], ['escape' => '!']);
        $this->assertEquals($expected, $result);

        $string = '2 + 2 = \%sum. Cake is %adjective.';
        $expected = '2 + 2 = %sum. Cake is yummy.';
        $result = Text::insert($string, ['sum' => '4', 'adjective' => 'yummy'], ['before' => '%']);
        $this->assertEquals($expected, $result);

        $string = ':a :b \:a :a';
        $expected = '1 2 :a 1';
        $result = Text::insert($string, ['a' => 1, 'b' => 2]);
        $this->assertEquals($expected, $result);

        $string = ':a :b :c';
        $expected = '2 3';
        $result = Text::insert($string, ['b' => 2, 'c' => 3], ['clean' => true]);
        $this->assertEquals($expected, $result);

        $string = ':a :b :c';
        $expected = '1 3';
        $result = Text::insert($string, ['a' => 1, 'c' => 3], ['clean' => true]);
        $this->assertEquals($expected, $result);

        $string = ':a :b :c';
        $expected = '2 3';
        $result = Text::insert($string, ['b' => 2, 'c' => 3], ['clean' => true]);
        $this->assertEquals($expected, $result);

        $string = ':a, :b and :c';
        $expected = '2 and 3';
        $result = Text::insert($string, ['b' => 2, 'c' => 3], ['clean' => true]);
        $this->assertEquals($expected, $result);

        $string = '":a, :b and :c"';
        $expected = '"1, 2"';
        $result = Text::insert($string, ['a' => 1, 'b' => 2], ['clean' => true]);
        $this->assertEquals($expected, $result);

        $string = '"${a}, ${b} and ${c}"';
        $expected = '"1, 2"';
        $result = Text::insert($string, ['a' => 1, 'b' => 2], ['before' => '${', 'after' => '}', 'clean' => true]);
        $this->assertEquals($expected, $result);

        $string = '<img src=":src" alt=":alt" class="foo :extra bar"/>';
        $expected = '<img src="foo" class="foo bar"/>';
        $result = Text::insert($string, ['src' => 'foo'], ['clean' => 'html']);

        $this->assertEquals($expected, $result);

        $string = '<img src=":src" class=":no :extra"/>';
        $expected = '<img src="foo"/>';
        $result = Text::insert($string, ['src' => 'foo'], ['clean' => 'html']);
        $this->assertEquals($expected, $result);

        $string = '<img src=":src" class=":no :extra"/>';
        $expected = '<img src="foo" class="bar"/>';
        $result = Text::insert($string, ['src' => 'foo', 'extra' => 'bar'], ['clean' => 'html']);
        $this->assertEquals($expected, $result);

        $result = Text::insert("this is a ? string", "test");
        $expected = "this is a test string";
        $this->assertEquals($expected, $result);

        $result = Text::insert("this is a ? string with a ? ? ?", ['long', 'few?', 'params', 'you know']);
        $expected = "this is a long string with a few? params you know";
        $this->assertEquals($expected, $result);

        $result = Text::insert('update saved_urls set url = :url where id = :id', ['url' => 'http://www.testurl.com/param1:url/param2:id', 'id' => 1]);
        $expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id where id = 1";
        $this->assertEquals($expected, $result);

        $result = Text::insert('update saved_urls set url = :url where id = :id', ['id' => 1, 'url' => 'http://www.testurl.com/param1:url/param2:id']);
        $expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id where id = 1";
        $this->assertEquals($expected, $result);

        $result = Text::insert(':me cake. :subject :verb fantastic.', ['me' => 'I :verb', 'subject' => 'cake', 'verb' => 'is']);
        $expected = "I :verb cake. cake is fantastic.";
        $this->assertEquals($expected, $result);

        $result = Text::insert(':I.am: :not.yet: passing.', ['I.am' => 'We are'], ['before' => ':', 'after' => ':', 'clean' => ['replacement' => ' of course', 'method' => 'text']]);
        $expected = "We are of course passing.";
        $this->assertEquals($expected, $result);

        $result = Text::insert(
            ':I.am: :not.yet: passing.',
            ['I.am' => 'We are'],
            ['before' => ':', 'after' => ':', 'clean' => true]
        );
        $expected = "We are passing.";
        $this->assertEquals($expected, $result);

        $result = Text::insert('?-pended result', ['Pre']);
        $expected = "Pre-pended result";
        $this->assertEquals($expected, $result);

        $string = 'switching :timeout / :timeout_count';
        $expected = 'switching 5 / 10';
        $result = Text::insert($string, ['timeout' => 5, 'timeout_count' => 10]);
        $this->assertEquals($expected, $result);

        $string = 'switching :timeout / :timeout_count';
        $expected = 'switching 5 / 10';
        $result = Text::insert($string, ['timeout_count' => 10, 'timeout' => 5]);
        $this->assertEquals($expected, $result);

        $string = 'switching :timeout_count by :timeout';
        $expected = 'switching 10 by 5';
        $result = Text::insert($string, ['timeout' => 5, 'timeout_count' => 10]);
        $this->assertEquals($expected, $result);

        $string = 'switching :timeout_count by :timeout';
        $expected = 'switching 10 by 5';
        $result = Text::insert($string, ['timeout_count' => 10, 'timeout' => 5]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test Clean Insert
     *
     * @return void
     */
    public function testCleanInsert()
    {
        $result = Text::cleanInsert(':incomplete', [
            'clean' => true, 'before' => ':', 'after' => ''
        ]);
        $this->assertEquals('', $result);

        $result = Text::cleanInsert(
            ':incomplete',
            [
            'clean' => ['method' => 'text', 'replacement' => 'complete'],
            'before' => ':', 'after' => '']
        );
        $this->assertEquals('complete', $result);

        $result = Text::cleanInsert(':in.complete', [
            'clean' => true, 'before' => ':', 'after' => ''
        ]);
        $this->assertEquals('', $result);

        $result = Text::cleanInsert(
            ':in.complete and',
            [
            'clean' => true, 'before' => ':', 'after' => '']
        );
        $this->assertEquals('', $result);

        $result = Text::cleanInsert(':in.complete or stuff', [
            'clean' => true, 'before' => ':', 'after' => ''
        ]);
        $this->assertEquals('stuff', $result);

        $result = Text::cleanInsert(
            '<p class=":missing" id=":missing">Text here</p>',
            ['clean' => 'html', 'before' => ':', 'after' => '']
        );
        $this->assertEquals('<p>Text here</p>', $result);
    }

    /**
     * Tests that non-insertable variables (i.e. arrays) are skipped when used as values in
     * Text::insert().
     *
     * @return void
     */
    public function testAutoIgnoreBadInsertData()
    {
        $data = ['foo' => 'alpha', 'bar' => 'beta', 'fale' => []];
        $result = Text::insert('(:foo > :bar || :fale!)', $data, ['clean' => 'text']);
        $this->assertEquals('(alpha > beta || !)', $result);
    }

    /**
     * testTokenize method
     *
     * @return void
     */
    public function testTokenize()
    {
        $result = Text::tokenize('A,(short,boring test)');
        $expected = ['A', '(short,boring test)'];
        $this->assertEquals($expected, $result);

        $result = Text::tokenize('A,(short,more interesting( test)');
        $expected = ['A', '(short,more interesting( test)'];
        $this->assertEquals($expected, $result);

        $result = Text::tokenize('A,(short,very interesting( test))');
        $expected = ['A', '(short,very interesting( test))'];
        $this->assertEquals($expected, $result);

        $result = Text::tokenize('"single tag"', ' ', '"', '"');
        $expected = ['"single tag"'];
        $this->assertEquals($expected, $result);

        $result = Text::tokenize('tagA "single tag" tagB', ' ', '"', '"');
        $expected = ['tagA', '"single tag"', 'tagB'];
        $this->assertEquals($expected, $result);

        // Ideographic width space.
        $result = Text::tokenize("tagA\xe3\x80\x80\"single\xe3\x80\x80tag\"\xe3\x80\x80tagB", "\xe3\x80\x80", '"', '"');
        $expected = ['tagA', '"single　tag"', 'tagB'];
        $this->assertEquals($expected, $result);
    }

    public function testReplaceWithQuestionMarkInString()
    {
        $string = ':a, :b and :c?';
        $expected = '2 and 3?';
        $result = Text::insert($string, ['b' => 2, 'c' => 3], ['clean' => true]);
        $this->assertEquals($expected, $result);
    }

    /**
     * test that wordWrap() works the same as built-in wordwrap function
     *
     * @dataProvider wordWrapProvider
     * @return void
     */
    public function testWordWrap($text, $width, $break = "\n", $cut = false)
    {
        $result = Text::wordWrap($text, $width, $break, $cut);
        $expected = wordwrap($text, $width, $break, $cut);
        $this->assertTextEquals($expected, $result, 'Text not wrapped same as built-in function.');
    }

    /**
     * data provider for testWordWrap method
     *
     * @return array
     */
    public function wordWrapProvider()
    {
        return [
            [
                'The quick brown fox jumped over the lazy dog.',
                33
            ],
            [
                'A very long woooooooooooord.',
                8
            ],
            [
                'A very long woooooooooooord. Right.',
                8
            ],
        ];
    }

    /**
     * test that wordWrap() properly handle unicode strings.
     *
     * @return void
     */
    public function testWordWrapUnicodeAware()
    {
        $text = 'Но вим омниюм факёльиси элыктрам, мюнырэ лэгыры векж ыт. Выльёт квюандо нюмквуам ты кюм. Зыд эю рыбюм.';
        $result = Text::wordWrap($text, 33, "\n", true);
        $expected = <<<TEXT
Но вим омниюм факёльиси элыктрам,
мюнырэ лэгыры векж ыт. Выльёт квю
андо нюмквуам ты кюм. Зыд эю рыбю
м.
TEXT;
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');

        $text = 'Но вим омниюм факёльиси элыктрам, мюнырэ лэгыры векж ыт. Выльёт квюандо нюмквуам ты кюм. Зыд эю рыбюм.';
        $result = Text::wordWrap($text, 33, "\n");
        $expected = <<<TEXT
Но вим омниюм факёльиси элыктрам,
мюнырэ лэгыры векж ыт. Выльёт
квюандо нюмквуам ты кюм. Зыд эю
рыбюм.
TEXT;
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');
    }

    /**
     * test that wordWrap() properly handle newline characters.
     *
     * @return void
     */
    public function testWordWrapNewlineAware()
    {
        $text = 'This is a line that is almost the 55 chars long.
This is a new sentence which is manually newlined, but is so long it needs two lines.';
        $result = Text::wordWrap($text, 55);
        $expected = <<<TEXT
This is a line that is almost the 55 chars long.
This is a new sentence which is manually newlined, but
is so long it needs two lines.
TEXT;
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');
    }

    /**
     * test wrap method.
     *
     * @return void
     */
    public function testWrap()
    {
        $text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
        $result = Text::wrap($text, 33);
        $expected = <<<TEXT
This is the song that never ends.
This is the song that never ends.
This is the song that never ends.
TEXT;
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');

        $result = Text::wrap($text, ['width' => 20, 'wordWrap' => false]);
        $expected = 'This is the song th' . "\n" .
            'at never ends. This' . "\n" .
            ' is the song that n' . "\n" .
            'ever ends. This is ' . "\n" .
            'the song that never' . "\n" .
            ' ends.';
        $this->assertTextEquals($expected, $result, 'Text not wrapped.');
    }

    /**
     * test wrap() indenting
     *
     * @return void
     */
    public function testWrapIndent()
    {
        $text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
        $result = Text::wrap($text, ['width' => 33, 'indent' => "\t", 'indentAt' => 1]);
        $expected = <<<TEXT
This is the song that never ends.
	This is the song that never ends.
	This is the song that never ends.
TEXT;
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test wrapBlock() indentical to wrap()
     *
     * @return void
     */
    public function testWrapBlockIndenticalToWrap()
    {
        $text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
        $result = Text::wrapBlock($text, 33);
        $expected = Text::wrap($text, 33);
        $this->assertTextEquals($expected, $result);

        $result = Text::wrapBlock($text, ['width' => 33, 'indentAt' => 0]);
        $expected = Text::wrap($text, ['width' => 33, 'indentAt' => 0]);
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test wrapBlock() indenting from first line
     *
     * @return void
     */
    public function testWrapBlockWithIndentAt0()
    {
        $text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
        $result = Text::wrapBlock($text, ['width' => 33, 'indent' => "\t", 'indentAt' => 0]);
        $expected = <<<TEXT
	This is the song that never
	ends. This is the song that
	never ends. This is the song
	that never ends.
TEXT;
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test wrapBlock() indenting from second line
     *
     * @return void
     */
    public function testWrapBlockWithIndentAt1()
    {
        $text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
        $result = Text::wrapBlock($text, ['width' => 33, 'indent' => "\t", 'indentAt' => 1]);
        $expected = <<<TEXT
This is the song that never ends.
	This is the song that never
	ends. This is the song that
	never ends.
TEXT;
        $this->assertTextEquals($expected, $result);
    }

    /**
     * test wrapBlock() indenting with multibyte caracters
     *
     * @return void
     */
    public function testWrapBlockIndentWithMultibyte()
    {
        $text = 'This is the song that never ends. 这是永远不会结束的歌曲。 This is the song that never ends.';
        $result = Text::wrapBlock($text, ['width' => 33, 'indent' => " → ", 'indentAt' => 1]);
        $expected = <<<TEXT
This is the song that never ends.
 → 这是永远不会结束的歌曲。 This is the song
 → that never ends.
TEXT;
        $this->assertTextEquals($expected, $result);
    }

    /**
     * testTruncate method
     *
     * @return void
     */
    public function testTruncate()
    {
        $text1 = 'The quick brown fox jumps over the lazy dog';
        $text2 = 'Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung';
        $text3 = '<b>&copy; 2005-2007, Cake Software Foundation, Inc.</b><br />written by Alexander Wegener';
        $text4 = '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Great, or?';
        $text5 = '0<b>1<i>2<span class="myclass">3</span>4<u>5</u>6</i>7</b>8<b>9</b>0';
        $text6 = '<p><strong>Extra dates have been announced for this year\'s tour.</strong></p><p>Tickets for the new shows in</p>';
        $text7 = 'El moño está en el lugar correcto. Eso fue lo que dijo la niña, ¿habrá dicho la verdad?';
        $text8 = 'Vive la R' . chr(195) . chr(169) . 'publique de France';
        $text9 = 'НОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
        $text10 = 'http://example.com/something/foo:bar';

        $this->assertSame($this->Text->truncate('Hello', 3), '...');
        $this->assertSame($this->Text->truncate('Hello', 3, ['exact' => false]), 'Hel...');
        $this->assertSame($this->Text->truncate($text1, 15), 'The quick br...');
        $this->assertSame($this->Text->truncate($text1, 15, ['exact' => false]), 'The quick...');
        $this->assertSame($this->Text->truncate($text1, 100), 'The quick brown fox jumps over the lazy dog');
        $this->assertSame($this->Text->truncate($text2, 10), 'Heiz&ou...');
        $this->assertSame($this->Text->truncate($text2, 10, ['exact' => false]), 'Heiz&ouml;...');
        $this->assertSame($this->Text->truncate($text3, 20), '<b>&copy; 2005-20...');
        $this->assertSame($this->Text->truncate($text4, 15), '<img src="my...');
        $this->assertSame($this->Text->truncate($text5, 6, ['ellipsis' => '']), '0<b>1<');
        $this->assertSame($this->Text->truncate($text1, 15, ['html' => true]), "The quick brow\xe2\x80\xa6");
        $this->assertSame($this->Text->truncate($text1, 15, ['exact' => false, 'html' => true]), "The quick\xe2\x80\xa6");
        $this->assertSame($this->Text->truncate($text2, 10, ['html' => true]), "Heiz&ouml;lr&uuml;c\xe2\x80\xa6");
        $this->assertSame($this->Text->truncate($text2, 10, ['exact' => false, 'html' => true]), "Heiz&ouml;\xe2\x80\xa6");
        $this->assertSame($this->Text->truncate($text3, 20, ['html' => true]), "<b>&copy; 2005-2007, Cake S\xe2\x80\xa6</b>");
        $this->assertSame($this->Text->truncate($text4, 15, ['html' => true]), "<img src=\"mypic.jpg\"> This image ta\xe2\x80\xa6");
        $this->assertSame($this->Text->truncate($text4, 45, ['html' => true]), "<img src=\"mypic.jpg\"> This image tag is not XHTML conform!<br><hr/><b>But the\xe2\x80\xa6</b>");
        $this->assertSame($this->Text->truncate($text4, 90, ['html' => true]), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Great,' . "\xe2\x80\xa6");
        $this->assertSame($this->Text->truncate($text5, 6, ['ellipsis' => '', 'html' => true]), '0<b>1<i>2<span class="myclass">3</span>4<u>5</u></i></b>');
        $this->assertSame($this->Text->truncate($text5, 20, ['ellipsis' => '', 'html' => true]), $text5);
        $this->assertSame($this->Text->truncate($text6, 57, ['exact' => false, 'html' => true]), "<p><strong>Extra dates have been announced for this year's\xe2\x80\xa6</strong></p>");
        $this->assertSame($this->Text->truncate($text7, 255), $text7);
        $this->assertSame($this->Text->truncate($text7, 15), 'El moño está...');
        $this->assertSame($this->Text->truncate($text8, 15), 'Vive la R' . chr(195) . chr(169) . 'pu...');
        $this->assertSame($this->Text->truncate($text9, 10), 'НОПРСТУ...');
        $this->assertSame($this->Text->truncate($text10, 30), 'http://example.com/somethin...');

        $text = '<p><span style="font-size: medium;"><a>Iamatestwithnospacesandhtml</a></span></p>';
        $result = $this->Text->truncate($text, 10, [
            'ellipsis' => '...',
            'exact' => false,
            'html' => true
        ]);
        $expected = '<p><span style="font-size: medium;"><a>...</a></span></p>';
        $this->assertEquals($expected, $result);

        $text = '<p><span style="font-size: medium;">El biógrafo de Steve Jobs, Walter
Isaacson, explica porqué Jobs le pidió que le hiciera su biografía en
este artículo de El País.</span></p>
<p><span style="font-size: medium;"><span style="font-size:
large;">Por qué Steve era distinto.</span></span></p>
<p><span style="font-size: medium;"><a href="http://www.elpais.com/
articulo/primer/plano/Steve/era/distinto/elpepueconeg/
20111009elpneglse_4/Tes">http://www.elpais.com/articulo/primer/plano/
Steve/era/distinto/elpepueconeg/20111009elpneglse_4/Tes</a></span></p>
<p><span style="font-size: medium;">Ya se ha publicado la biografía de
Steve Jobs escrita por Walter Isaacson  "<strong>Steve Jobs by Walter
Isaacson</strong>", aquí os dejamos la dirección de amazon donde
podeís adquirirla.</span></p>
<p><span style="font-size: medium;"><a>http://www.amazon.com/Steve-
Jobs-Walter-Isaacson/dp/1451648537</a></span></p>';
        $result = $this->Text->truncate($text, 500, [
            'ellipsis' => '... ',
            'exact' => false,
            'html' => true
        ]);
        $expected = '<p><span style="font-size: medium;">El biógrafo de Steve Jobs, Walter
Isaacson, explica porqué Jobs le pidió que le hiciera su biografía en
este artículo de El País.</span></p>
<p><span style="font-size: medium;"><span style="font-size:
large;">Por qué Steve era distinto.</span></span></p>
<p><span style="font-size: medium;"><a href="http://www.elpais.com/
articulo/primer/plano/Steve/era/distinto/elpepueconeg/
20111009elpneglse_4/Tes">http://www.elpais.com/articulo/primer/plano/
Steve/era/distinto/elpepueconeg/20111009elpneglse_4/Tes</a></span></p>
<p><span style="font-size: medium;">Ya se ha publicado la biografía de
Steve Jobs escrita por Walter Isaacson  "<strong>Steve Jobs by Walter
Isaacson</strong>", aquí os dejamos la dirección de amazon donde
podeís adquirirla.</span></p>
<p><span style="font-size: medium;"><a>... </a></span></p>';
        $this->assertEquals($expected, $result);
    }

    /**
     * testTruncate method with non utf8 sites
     *
     * @return void
     */
    public function testTruncateLegacy()
    {
        mb_internal_encoding('ISO-8859-1');
        $text = '<b>&copy; 2005-2007, Cake Software Foundation, Inc.</b><br />written by Alexander Wegener';
        $result = $this->Text->truncate($text, 31, [
            'html' => true,
            'exact' => false,
        ]);
        $expected = '<b>&copy; 2005-2007, Cake Software...</b>';
        $this->assertEquals($expected, $result);

        $result = $this->Text->truncate($text, 31, [
            'html' => true,
            'exact' => true,
        ]);
        $expected = '<b>&copy; 2005-2007, Cake Software F...</b>';
        $this->assertEquals($expected, $result);
    }

    /**
     * testTail method
     *
     * @return void
     */
    public function testTail()
    {
        $text1 = 'The quick brown fox jumps over the lazy dog';
        $text2 = 'Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung';
        $text3 = 'El moño está en el lugar correcto. Eso fue lo que dijo la niña, ¿habrá dicho la verdad?';
        $text4 = 'Vive la R' . chr(195) . chr(169) . 'publique de France';
        $text5 = 'НОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';

        $result = $this->Text->tail($text1, 13);
        $this->assertEquals('...e lazy dog', $result);

        $result = $this->Text->tail($text1, 13, ['exact' => false]);
        $this->assertEquals('...lazy dog', $result);

        $result = $this->Text->tail($text1, 100);
        $this->assertEquals('The quick brown fox jumps over the lazy dog', $result);

        $result = $this->Text->tail($text2, 10);
        $this->assertEquals('...;mpfung', $result);

        $result = $this->Text->tail($text2, 10, ['exact' => false]);
        $this->assertEquals('...', $result);

        $result = $this->Text->tail($text3, 255);
        $this->assertEquals($text3, $result);

        $result = $this->Text->tail($text3, 21);
        $this->assertEquals('...á dicho la verdad?', $result);

        $result = $this->Text->tail($text4, 25);
        $this->assertEquals('...a R' . chr(195) . chr(169) . 'publique de France', $result);

        $result = $this->Text->tail($text5, 10);
        $this->assertEquals('...цчшщъыь', $result);

        $result = $this->Text->tail($text5, 6, ['ellipsis' => '']);
        $this->assertEquals('чшщъыь', $result);
    }

    /**
     * testHighlight method
     *
     * @return void
     */
    public function testHighlight()
    {
        $text = 'This is a test text';
        $phrases = ['This', 'text'];
        $result = $this->Text->highlight($text, $phrases, ['format' => '<b>\1</b>']);
        $expected = '<b>This</b> is a test <b>text</b>';
        $this->assertEquals($expected, $result);

        $phrases = ['is', 'text'];
        $result = $this->Text->highlight($text, $phrases, ['format' => '<b>\1</b>', 'regex' => "|\b%s\b|iu"]);
        $expected = 'This <b>is</b> a test <b>text</b>';
        $this->assertEquals($expected, $result);

        $text = 'This is a test text';
        $phrases = null;
        $result = $this->Text->highlight($text, $phrases, ['format' => '<b>\1</b>']);
        $this->assertEquals($text, $result);

        $text = 'This is a (test) text';
        $phrases = '(test';
        $result = $this->Text->highlight($text, $phrases, ['format' => '<b>\1</b>']);
        $this->assertEquals('This is a <b>(test</b>) text', $result);

        $text = 'Ich saß in einem Café am Übergang';
        $expected = 'Ich <b>saß</b> in einem <b>Café</b> am <b>Übergang</b>';
        $phrases = ['saß', 'café', 'übergang'];
        $result = $this->Text->highlight($text, $phrases, ['format' => '<b>\1</b>']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testHighlightHtml method
     *
     * @return void
     */
    public function testHighlightHtml()
    {
        $text1 = '<p>strongbow isn&rsquo;t real cider</p>';
        $text2 = '<p>strongbow <strong>isn&rsquo;t</strong> real cider</p>';
        $text3 = '<img src="what-a-strong-mouse.png" alt="What a strong mouse!" />';
        $text4 = 'What a strong mouse: <img src="what-a-strong-mouse.png" alt="What a strong mouse!" />';
        $options = ['format' => '<b>\1</b>', 'html' => true];

        $expected = '<p><b>strong</b>bow isn&rsquo;t real cider</p>';
        $this->assertEquals($expected, $this->Text->highlight($text1, 'strong', $options));

        $expected = '<p><b>strong</b>bow <strong>isn&rsquo;t</strong> real cider</p>';
        $this->assertEquals($expected, $this->Text->highlight($text2, 'strong', $options));

        $this->assertEquals($text3, $this->Text->highlight($text3, 'strong', $options));

        $this->assertEquals($text3, $this->Text->highlight($text3, ['strong', 'what'], $options));

        $expected = '<b>What</b> a <b>strong</b> mouse: <img src="what-a-strong-mouse.png" alt="What a strong mouse!" />';
        $this->assertEquals($expected, $this->Text->highlight($text4, ['strong', 'what'], $options));
    }

    /**
     * testHighlightMulti method
     *
     * @return void
     */
    public function testHighlightMulti()
    {
        $text = 'This is a test text';
        $phrases = ['This', 'text'];
        $result = $this->Text->highlight($text, $phrases, ['format' => ['<b>\1</b>', '<em>\1</em>']]);
        $expected = '<b>This</b> is a test <em>text</em>';
        $this->assertEquals($expected, $result);
    }

    /**
     * testStripLinks method
     *
     * @return void
     */
    public function testStripLinks()
    {
        $text = 'This is a test text';
        $expected = 'This is a test text';
        $result = $this->Text->stripLinks($text);
        $this->assertEquals($expected, $result);

        $text = 'This is a <a href="#">test</a> text';
        $expected = 'This is a test text';
        $result = $this->Text->stripLinks($text);
        $this->assertEquals($expected, $result);

        $text = 'This <strong>is</strong> a <a href="#">test</a> <a href="#">text</a>';
        $expected = 'This <strong>is</strong> a test text';
        $result = $this->Text->stripLinks($text);
        $this->assertEquals($expected, $result);

        $text = 'This <strong>is</strong> a <a href="#">test</a> and <abbr>some</abbr> other <a href="#">text</a>';
        $expected = 'This <strong>is</strong> a test and <abbr>some</abbr> other text';
        $result = $this->Text->stripLinks($text);
        $this->assertEquals($expected, $result);
    }

    /**
     * testHighlightCaseInsensitivity method
     *
     * @return void
     */
    public function testHighlightCaseInsensitivity()
    {
        $text = 'This is a Test text';
        $expected = 'This is a <b>Test</b> text';

        $result = $this->Text->highlight($text, 'test', ['format' => '<b>\1</b>']);
        $this->assertEquals($expected, $result);

        $result = $this->Text->highlight($text, ['test'], ['format' => '<b>\1</b>']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testExcerpt method
     *
     * @return void
     */
    public function testExcerpt()
    {
        $text = 'This is a phrase with test text to play with';

        $expected = '...ase with test text to ...';
        $result = $this->Text->excerpt($text, 'test', 9, '...');
        $this->assertEquals($expected, $result);

        $expected = 'This is a...';
        $result = $this->Text->excerpt($text, 'not_found', 9, '...');
        $this->assertEquals($expected, $result);

        $expected = 'This is a phras...';
        $result = $this->Text->excerpt($text, null, 9, '...');
        $this->assertEquals($expected, $result);

        $expected = $text;
        $result = $this->Text->excerpt($text, null, 200, '...');
        $this->assertEquals($expected, $result);

        $expected = '...a phrase w...';
        $result = $this->Text->excerpt($text, 'phrase', 2, '...');
        $this->assertEquals($expected, $result);

        $phrase = 'This is a phrase with test text';
        $expected = $text;
        $result = $this->Text->excerpt($text, $phrase, 13, '...');
        $this->assertEquals($expected, $result);

        $text = 'aaaaaaaaaaaaaaaaaaaaaaaabbbbbbbbaaaaaaaaaaaaaaaaaaaaaaaa';
        $phrase = 'bbbbbbbb';
        $result = $this->Text->excerpt($text, $phrase, 10);
        $expected = '...aaaaaaaaaabbbbbbbbaaaaaaaaaa...';
        $this->assertEquals($expected, $result);
    }

    /**
     * testExcerptCaseInsensitivity method
     *
     * @return void
     */
    public function testExcerptCaseInsensitivity()
    {
        $text = 'This is a phrase with test text to play with';

        $expected = '...ase with test text to ...';
        $result = $this->Text->excerpt($text, 'TEST', 9, '...');
        $this->assertEquals($expected, $result);

        $expected = 'This is a...';
        $result = $this->Text->excerpt($text, 'NOT_FOUND', 9, '...');
        $this->assertEquals($expected, $result);
    }

    /**
     * testListGeneration method
     *
     * @return void
     */
    public function testListGeneration()
    {
        $result = $this->Text->toList([]);
        $this->assertEquals('', $result);

        $result = $this->Text->toList(['One']);
        $this->assertEquals('One', $result);

        $result = $this->Text->toList(['Larry', 'Curly', 'Moe']);
        $this->assertEquals('Larry, Curly and Moe', $result);

        $result = $this->Text->toList(['Dusty', 'Lucky', 'Ned'], 'y');
        $this->assertEquals('Dusty, Lucky y Ned', $result);

        $result = $this->Text->toList([1 => 'Dusty', 2 => 'Lucky', 3 => 'Ned'], 'y');
        $this->assertEquals('Dusty, Lucky y Ned', $result);

        $result = $this->Text->toList([1 => 'Dusty', 2 => 'Lucky', 3 => 'Ned'], 'and', ' + ');
        $this->assertEquals('Dusty + Lucky and Ned', $result);

        $result = $this->Text->toList(['name1' => 'Dusty', 'name2' => 'Lucky']);
        $this->assertEquals('Dusty and Lucky', $result);

        $result = $this->Text->toList(['test_0' => 'banana', 'test_1' => 'apple', 'test_2' => 'lemon']);
        $this->assertEquals('banana, apple and lemon', $result);
    }

    /**
     * testUtf8 method
     *
     * @return void
     */
    public function testUtf8()
    {
        $string = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $result = Text::utf8($string);
        $expected = [33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
                                58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,
                                83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105,
                                106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126];
        $this->assertEquals($expected, $result);

        $string = '¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈ';
        $result = Text::utf8($string);
        $expected = [161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181,
                                182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200];
        $this->assertEquals($expected, $result);

        $string = 'ÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬ';
        $result = Text::utf8($string);
        $expected = [201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221,
                                222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242,
                                243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263,
                                264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284,
                                285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299, 300];
        $this->assertEquals($expected, $result);

        $string = 'ĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏƐ';
        $result = Text::utf8($string);
        $expected = [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321,
                                322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342,
                                343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363,
                                364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381, 382, 383, 384,
                                385, 386, 387, 388, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400];
        $this->assertEquals($expected, $result);

        $string = 'ƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯǰǱǲǳǴ';
        $result = Text::utf8($string);
        $expected = [401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421,
                                422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442,
                                443, 444, 445, 446, 447, 448, 449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460, 461, 462, 463,
                                464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481, 482, 483, 484,
                                485, 486, 487, 488, 489, 490, 491, 492, 493, 494, 495, 496, 497, 498, 499, 500];
        $this->assertEquals($expected, $result);

        $string = 'əɚɛɜɝɞɟɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯʰʱʲʳʴʵʶʷʸʹʺʻʼ';
        $result = Text::utf8($string);
        $expected = [601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 621,
                                622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 637, 638, 639, 640, 641, 642,
                                643, 644, 645, 646, 647, 648, 649, 650, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660, 661, 662, 663,
                                664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 682, 683, 684,
                                685, 686, 687, 688, 689, 690, 691, 692, 693, 694, 695, 696, 697, 698, 699, 700];
        $this->assertEquals($expected, $result);

        $string = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏАБВГДЕЖЗИЙКЛ';
        $result = Text::utf8($string);
        $expected = [1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040, 1041,
                                1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050, 1051];
        $this->assertEquals($expected, $result);

        $string = 'МНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
        $result = Text::utf8($string);
        $expected = [1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060, 1061, 1062, 1063, 1064, 1065, 1066, 1067, 1068, 1069,
                                1070, 1071, 1072, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084, 1085, 1086, 1087,
                                1088, 1089, 1090, 1091, 1092, 1093, 1094, 1095, 1096, 1097, 1098, 1099, 1100];
        $this->assertEquals($expected, $result);

        $string = 'չպջռսվտ';
        $result = Text::utf8($string);
        $expected = [1401, 1402, 1403, 1404, 1405, 1406, 1407];
        $this->assertEquals($expected, $result);

        $string = 'فقكلمنهوىيًٌٍَُ';
        $result = Text::utf8($string);
        $expected = [1601, 1602, 1603, 1604, 1605, 1606, 1607, 1608, 1609, 1610, 1611, 1612, 1613, 1614, 1615];
        $this->assertEquals($expected, $result);

        $string = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞';
        $result = Text::utf8($string);
        $expected = [10032, 10033, 10034, 10035, 10036, 10037, 10038, 10039, 10040, 10041, 10042, 10043, 10044,
                                10045, 10046, 10047, 10048, 10049, 10050, 10051, 10052, 10053, 10054, 10055, 10056, 10057,
                                10058, 10059, 10060, 10061, 10062, 10063, 10064, 10065, 10066, 10067, 10068, 10069, 10070,
                                10071, 10072, 10073, 10074, 10075, 10076, 10077, 10078];
        $this->assertEquals($expected, $result);

        $string = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺛⺜⺝⺞⺟⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟⻠';
        $result = Text::utf8($string);
        $expected = [11904, 11905, 11906, 11907, 11908, 11909, 11910, 11911, 11912, 11913, 11914, 11915, 11916, 11917, 11918, 11919,
                                11920, 11921, 11922, 11923, 11924, 11925, 11926, 11927, 11928, 11929, 11931, 11932, 11933, 11934, 11935, 11936,
                                11937, 11938, 11939, 11940, 11941, 11942, 11943, 11944, 11945, 11946, 11947, 11948, 11949, 11950, 11951, 11952,
                                11953, 11954, 11955, 11956, 11957, 11958, 11959, 11960, 11961, 11962, 11963, 11964, 11965, 11966, 11967, 11968,
                                11969, 11970, 11971, 11972, 11973, 11974, 11975, 11976, 11977, 11978, 11979, 11980, 11981, 11982, 11983, 11984,
                                11985, 11986, 11987, 11988, 11989, 11990, 11991, 11992, 11993, 11994, 11995, 11996, 11997, 11998, 11999, 12000];
        $this->assertEquals($expected, $result);

        $string = '⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
        $result = Text::utf8($string);
        $expected = [12101, 12102, 12103, 12104, 12105, 12106, 12107, 12108, 12109, 12110, 12111, 12112, 12113, 12114, 12115, 12116,
                                12117, 12118, 12119, 12120, 12121, 12122, 12123, 12124, 12125, 12126, 12127, 12128, 12129, 12130, 12131, 12132,
                                12133, 12134, 12135, 12136, 12137, 12138, 12139, 12140, 12141, 12142, 12143, 12144, 12145, 12146, 12147, 12148,
                                12149, 12150, 12151, 12152, 12153, 12154, 12155, 12156, 12157, 12158, 12159];
        $this->assertEquals($expected, $result);

        $string = '눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿늀늁늂늃늄';
        $result = Text::utf8($string);
        $expected = [45601, 45602, 45603, 45604, 45605, 45606, 45607, 45608, 45609, 45610, 45611, 45612, 45613, 45614, 45615, 45616,
                                45617, 45618, 45619, 45620, 45621, 45622, 45623, 45624, 45625, 45626, 45627, 45628, 45629, 45630, 45631, 45632,
                                45633, 45634, 45635, 45636, 45637, 45638, 45639, 45640, 45641, 45642, 45643, 45644, 45645, 45646, 45647, 45648,
                                45649, 45650, 45651, 45652, 45653, 45654, 45655, 45656, 45657, 45658, 45659, 45660, 45661, 45662, 45663, 45664,
                                45665, 45666, 45667, 45668, 45669, 45670, 45671, 45672, 45673, 45674, 45675, 45676, 45677, 45678, 45679, 45680,
                                45681, 45682, 45683, 45684, 45685, 45686, 45687, 45688, 45689, 45690, 45691, 45692, 45693, 45694, 45695, 45696,
                                45697, 45698, 45699, 45700];
        $this->assertEquals($expected, $result);

        $string = 'ﹰﹱﹲﹳﹴ﹵ﹶﹷﹸﹹﹺﹻﹼﹽﹾﹿﺀﺁﺂﺃﺄﺅﺆﺇﺈﺉﺊﺋﺌﺍﺎﺏﺐﺑﺒﺓﺔﺕﺖﺗﺘﺙﺚﺛﺜﺝﺞﺟﺠﺡﺢﺣﺤﺥﺦﺧﺨﺩﺪﺫﺬﺭﺮﺯﺰ';
        $result = Text::utf8($string);
        $expected = [65136, 65137, 65138, 65139, 65140, 65141, 65142, 65143, 65144, 65145, 65146, 65147, 65148, 65149, 65150, 65151,
                                65152, 65153, 65154, 65155, 65156, 65157, 65158, 65159, 65160, 65161, 65162, 65163, 65164, 65165, 65166, 65167,
                                65168, 65169, 65170, 65171, 65172, 65173, 65174, 65175, 65176, 65177, 65178, 65179, 65180, 65181, 65182, 65183,
                                65184, 65185, 65186, 65187, 65188, 65189, 65190, 65191, 65192, 65193, 65194, 65195, 65196, 65197, 65198, 65199,
                                65200];
        $this->assertEquals($expected, $result);

        $string = 'ﺱﺲﺳﺴﺵﺶﺷﺸﺹﺺﺻﺼﺽﺾﺿﻀﻁﻂﻃﻄﻅﻆﻇﻈﻉﻊﻋﻌﻍﻎﻏﻐﻑﻒﻓﻔﻕﻖﻗﻘﻙﻚﻛﻜﻝﻞﻟﻠﻡﻢﻣﻤﻥﻦﻧﻨﻩﻪﻫﻬﻭﻮﻯﻰﻱﻲﻳﻴﻵﻶﻷﻸﻹﻺﻻﻼ';
        $result = Text::utf8($string);
        $expected = [65201, 65202, 65203, 65204, 65205, 65206, 65207, 65208, 65209, 65210, 65211, 65212, 65213, 65214, 65215, 65216,
                                65217, 65218, 65219, 65220, 65221, 65222, 65223, 65224, 65225, 65226, 65227, 65228, 65229, 65230, 65231, 65232,
                                65233, 65234, 65235, 65236, 65237, 65238, 65239, 65240, 65241, 65242, 65243, 65244, 65245, 65246, 65247, 65248,
                                65249, 65250, 65251, 65252, 65253, 65254, 65255, 65256, 65257, 65258, 65259, 65260, 65261, 65262, 65263, 65264,
                                65265, 65266, 65267, 65268, 65269, 65270, 65271, 65272, 65273, 65274, 65275, 65276];
        $this->assertEquals($expected, $result);

        $string = 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ';
        $result = Text::utf8($string);
        $expected = [65345, 65346, 65347, 65348, 65349, 65350, 65351, 65352, 65353, 65354, 65355, 65356, 65357, 65358, 65359, 65360,
                                65361, 65362, 65363, 65364, 65365, 65366, 65367, 65368, 65369, 65370];
        $this->assertEquals($expected, $result);

        $string = '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸ';
        $result = Text::utf8($string);
        $expected = [65377, 65378, 65379, 65380, 65381, 65382, 65383, 65384, 65385, 65386, 65387, 65388, 65389, 65390, 65391, 65392,
                                65393, 65394, 65395, 65396, 65397, 65398, 65399, 65400];
        $this->assertEquals($expected, $result);

        $string = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
        $result = Text::utf8($string);
        $expected = [65401, 65402, 65403, 65404, 65405, 65406, 65407, 65408, 65409, 65410, 65411, 65412, 65413, 65414, 65415, 65416,
                                65417, 65418, 65419, 65420, 65421, 65422, 65423, 65424, 65425, 65426, 65427, 65428, 65429, 65430, 65431, 65432,
                                65433, 65434, 65435, 65436, 65437, 65438];
        $this->assertEquals($expected, $result);

        $string = 'Ĥēĺļŏ, Ŵőřļď!';
        $result = Text::utf8($string);
        $expected = [292, 275, 314, 316, 335, 44, 32, 372, 337, 345, 316, 271, 33];
        $this->assertEquals($expected, $result);

        $string = 'Hello, World!';
        $result = Text::utf8($string);
        $expected = [72, 101, 108, 108, 111, 44, 32, 87, 111, 114, 108, 100, 33];
        $this->assertEquals($expected, $result);

        $string = '¨';
        $result = Text::utf8($string);
        $expected = [168];
        $this->assertEquals($expected, $result);

        $string = '¿';
        $result = Text::utf8($string);
        $expected = [191];
        $this->assertEquals($expected, $result);

        $string = 'čini';
        $result = Text::utf8($string);
        $expected = [269, 105, 110, 105];
        $this->assertEquals($expected, $result);

        $string = 'moći';
        $result = Text::utf8($string);
        $expected = [109, 111, 263, 105];
        $this->assertEquals($expected, $result);

        $string = 'državni';
        $result = Text::utf8($string);
        $expected = [100, 114, 382, 97, 118, 110, 105];
        $this->assertEquals($expected, $result);

        $string = '把百度设为首页';
        $result = Text::utf8($string);
        $expected = [25226, 30334, 24230, 35774, 20026, 39318, 39029];
        $this->assertEquals($expected, $result);

        $string = '一二三周永龍';
        $result = Text::utf8($string);
        $expected = [19968, 20108, 19977, 21608, 27704, 40845];
        $this->assertEquals($expected, $result);

        $string = 'ԀԂԄԆԈԊԌԎԐԒ';
        $result = Text::utf8($string);
        $expected = [1280, 1282, 1284, 1286, 1288, 1290, 1292, 1294, 1296, 1298];
        $this->assertEquals($expected, $result);

        $string = 'ԁԃԅԇԉԋԍԏԐԒ';
        $result = Text::utf8($string);
        $expected = [1281, 1283, 1285, 1287, 1289, 1291, 1293, 1295, 1296, 1298];
        $this->assertEquals($expected, $result);

        $string = 'ԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿՀՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏՐՑՒՓՔՕՖև';
        $result = Text::utf8($string);
        $expected = [1329, 1330, 1331, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1344, 1345, 1346,
                                1347, 1348, 1349, 1350, 1351, 1352, 1353, 1354, 1355, 1356, 1357, 1358, 1359, 1360, 1361, 1362, 1363, 1364,
                                1365, 1366, 1415];
        $this->assertEquals($expected, $result);

        $string = 'աբգդեզէըթժիլխծկհձղճմյնշոչպջռսվտրցւփքօֆև';
        $result = Text::utf8($string);
        $expected = [1377, 1378, 1379, 1380, 1381, 1382, 1383, 1384, 1385, 1386, 1387, 1388, 1389, 1390, 1391, 1392, 1393, 1394,
                                1395, 1396, 1397, 1398, 1399, 1400, 1401, 1402, 1403, 1404, 1405, 1406, 1407, 1408, 1409, 1410, 1411, 1412,
                                1413, 1414, 1415];
        $this->assertEquals($expected, $result);

        $string = 'ႠႡႢႣႤႥႦႧႨႩႪႫႬႭႮႯႰႱႲႳႴႵႶႷႸႹႺႻႼႽႾႿჀჁჂჃჄჅ';
        $result = Text::utf8($string);
        $expected = [4256, 4257, 4258, 4259, 4260, 4261, 4262, 4263, 4264, 4265, 4266, 4267, 4268, 4269, 4270, 4271, 4272, 4273,
                                4274, 4275, 4276, 4277, 4278, 4279, 4280, 4281, 4282, 4283, 4284, 4285, 4286, 4287, 4288, 4289, 4290, 4291,
                                4292, 4293];
        $this->assertEquals($expected, $result);

        $string = 'ḀḂḄḆḈḊḌḎḐḒḔḖḘḚḜḞḠḢḤḦḨḪḬḮḰḲḴḶḸḺḼḾṀṂṄṆṈṊṌṎṐṒṔṖṘṚṜṞṠṢṤṦṨṪṬṮṰṲṴṶṸṺṼṾẀẂẄẆẈẊẌẎẐẒẔẖẗẘẙẚẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼẾỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴỶỸ';
        $result = Text::utf8($string);
        $expected = [7680, 7682, 7684, 7686, 7688, 7690, 7692, 7694, 7696, 7698, 7700, 7702, 7704, 7706, 7708, 7710, 7712, 7714,
                                7716, 7718, 7720, 7722, 7724, 7726, 7728, 7730, 7732, 7734, 7736, 7738, 7740, 7742, 7744, 7746, 7748, 7750,
                                7752, 7754, 7756, 7758, 7760, 7762, 7764, 7766, 7768, 7770, 7772, 7774, 7776, 7778, 7780, 7782, 7784, 7786,
                                7788, 7790, 7792, 7794, 7796, 7798, 7800, 7802, 7804, 7806, 7808, 7810, 7812, 7814, 7816, 7818, 7820, 7822,
                                7824, 7826, 7828, 7830, 7831, 7832, 7833, 7834, 7840, 7842, 7844, 7846, 7848, 7850, 7852, 7854, 7856,
                                7858, 7860, 7862, 7864, 7866, 7868, 7870, 7872, 7874, 7876, 7878, 7880, 7882, 7884, 7886, 7888, 7890, 7892,
                                7894, 7896, 7898, 7900, 7902, 7904, 7906, 7908, 7910, 7912, 7914, 7916, 7918, 7920, 7922, 7924, 7926, 7928];
        $this->assertEquals($expected, $result);

        $string = 'ḁḃḅḇḉḋḍḏḑḓḕḗḙḛḝḟḡḣḥḧḩḫḭḯḱḳḵḷḹḻḽḿṁṃṅṇṉṋṍṏṑṓṕṗṙṛṝṟṡṣṥṧṩṫṭṯṱṳṵṷṹṻṽṿẁẃẅẇẉẋẍẏẑẓẕẖẗẘẙẚạảấầẩẫậắằẳẵặẹẻẽếềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵỷỹ';
        $result = Text::utf8($string);
        $expected = [7681, 7683, 7685, 7687, 7689, 7691, 7693, 7695, 7697, 7699, 7701, 7703, 7705, 7707, 7709, 7711, 7713, 7715,
                                    7717, 7719, 7721, 7723, 7725, 7727, 7729, 7731, 7733, 7735, 7737, 7739, 7741, 7743, 7745, 7747, 7749, 7751,
                                    7753, 7755, 7757, 7759, 7761, 7763, 7765, 7767, 7769, 7771, 7773, 7775, 7777, 7779, 7781, 7783, 7785, 7787,
                                    7789, 7791, 7793, 7795, 7797, 7799, 7801, 7803, 7805, 7807, 7809, 7811, 7813, 7815, 7817, 7819, 7821, 7823,
                                    7825, 7827, 7829, 7830, 7831, 7832, 7833, 7834, 7841, 7843, 7845, 7847, 7849, 7851, 7853, 7855, 7857, 7859,
                                    7861, 7863, 7865, 7867, 7869, 7871, 7873, 7875, 7877, 7879, 7881, 7883, 7885, 7887, 7889, 7891, 7893, 7895,
                                    7897, 7899, 7901, 7903, 7905, 7907, 7909, 7911, 7913, 7915, 7917, 7919, 7921, 7923, 7925, 7927, 7929];
        $this->assertEquals($expected, $result);

        $string = 'ΩKÅℲ';
        $result = Text::utf8($string);
        $expected = [8486, 8490, 8491, 8498];
        $this->assertEquals($expected, $result);

        $string = 'ωkåⅎ';
        $result = Text::utf8($string);
        $expected = [969, 107, 229, 8526];
        $this->assertEquals($expected, $result);

        $string = 'ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩⅪⅫⅬⅭⅮⅯↃ';
        $result = Text::utf8($string);
        $expected = [8544, 8545, 8546, 8547, 8548, 8549, 8550, 8551, 8552, 8553, 8554, 8555, 8556, 8557, 8558, 8559, 8579];
        $this->assertEquals($expected, $result);

        $string = 'ⅰⅱⅲⅳⅴⅵⅶⅷⅸⅹⅺⅻⅼⅽⅾⅿↄ';
        $result = Text::utf8($string);
        $expected = [8560, 8561, 8562, 8563, 8564, 8565, 8566, 8567, 8568, 8569, 8570, 8571, 8572, 8573, 8574, 8575, 8580];
        $this->assertEquals($expected, $result);

        $string = 'ⒶⒷⒸⒹⒺⒻⒼⒽⒾⒿⓀⓁⓂⓃⓄⓅⓆⓇⓈⓉⓊⓋⓌⓍⓎⓏ';
        $result = Text::utf8($string);
        $expected = [9398, 9399, 9400, 9401, 9402, 9403, 9404, 9405, 9406, 9407, 9408, 9409, 9410, 9411, 9412, 9413, 9414,
                                9415, 9416, 9417, 9418, 9419, 9420, 9421, 9422, 9423];
        $this->assertEquals($expected, $result);

        $string = 'ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ';
        $result = Text::utf8($string);
        $expected = [9424, 9425, 9426, 9427, 9428, 9429, 9430, 9431, 9432, 9433, 9434, 9435, 9436, 9437, 9438, 9439, 9440, 9441,
                                9442, 9443, 9444, 9445, 9446, 9447, 9448, 9449];
        $this->assertEquals($expected, $result);

        $string = 'ⰀⰁⰂⰃⰄⰅⰆⰇⰈⰉⰊⰋⰌⰍⰎⰏⰐⰑⰒⰓⰔⰕⰖⰗⰘⰙⰚⰛⰜⰝⰞⰟⰠⰡⰢⰣⰤⰥⰦⰧⰨⰩⰪⰫⰬⰭⰮ';
        $result = Text::utf8($string);
        $expected = [11264, 11265, 11266, 11267, 11268, 11269, 11270, 11271, 11272, 11273, 11274, 11275, 11276, 11277, 11278,
                                11279, 11280, 11281, 11282, 11283, 11284, 11285, 11286, 11287, 11288, 11289, 11290, 11291, 11292, 11293,
                                11294, 11295, 11296, 11297, 11298, 11299, 11300, 11301, 11302, 11303, 11304, 11305, 11306, 11307, 11308,
                                11309, 11310];
        $this->assertEquals($expected, $result);

        $string = 'ⰰⰱⰲⰳⰴⰵⰶⰷⰸⰹⰺⰻⰼⰽⰾⰿⱀⱁⱂⱃⱄⱅⱆⱇⱈⱉⱊⱋⱌⱍⱎⱏⱐⱑⱒⱓⱔⱕⱖⱗⱘⱙⱚⱛⱜⱝⱞ';
        $result = Text::utf8($string);
        $expected = [11312, 11313, 11314, 11315, 11316, 11317, 11318, 11319, 11320, 11321, 11322, 11323, 11324, 11325, 11326, 11327,
                                11328, 11329, 11330, 11331, 11332, 11333, 11334, 11335, 11336, 11337, 11338, 11339, 11340, 11341, 11342, 11343,
                                11344, 11345, 11346, 11347, 11348, 11349, 11350, 11351, 11352, 11353, 11354, 11355, 11356, 11357, 11358];
        $this->assertEquals($expected, $result);

        $string = 'ⲀⲂⲄⲆⲈⲊⲌⲎⲐⲒⲔⲖⲘⲚⲜⲞⲠⲢⲤⲦⲨⲪⲬⲮⲰⲲⲴⲶⲸⲺⲼⲾⳀⳂⳄⳆⳈⳊⳌⳎⳐⳒⳔⳖⳘⳚⳜⳞⳠⳢ';
        $result = Text::utf8($string);
        $expected = [11392, 11394, 11396, 11398, 11400, 11402, 11404, 11406, 11408, 11410, 11412, 11414, 11416, 11418, 11420,
                                    11422, 11424, 11426, 11428, 11430, 11432, 11434, 11436, 11438, 11440, 11442, 11444, 11446, 11448, 11450,
                                    11452, 11454, 11456, 11458, 11460, 11462, 11464, 11466, 11468, 11470, 11472, 11474, 11476, 11478, 11480,
                                    11482, 11484, 11486, 11488, 11490];
        $this->assertEquals($expected, $result);

        $string = 'ⲁⲃⲅⲇⲉⲋⲍⲏⲑⲓⲕⲗⲙⲛⲝⲟⲡⲣⲥⲧⲩⲫⲭⲯⲱⲳⲵⲷⲹⲻⲽⲿⳁⳃⳅⳇⳉⳋⳍⳏⳑⳓⳕⳗⳙⳛⳝⳟⳡⳣ';
        $result = Text::utf8($string);
        $expected = [11393, 11395, 11397, 11399, 11401, 11403, 11405, 11407, 11409, 11411, 11413, 11415, 11417, 11419, 11421, 11423,
                                11425, 11427, 11429, 11431, 11433, 11435, 11437, 11439, 11441, 11443, 11445, 11447, 11449, 11451, 11453, 11455,
                                11457, 11459, 11461, 11463, 11465, 11467, 11469, 11471, 11473, 11475, 11477, 11479, 11481, 11483, 11485, 11487,
                                11489, 11491];
        $this->assertEquals($expected, $result);

        $string = 'ﬀﬁﬂﬃﬄﬅﬆﬓﬔﬕﬖﬗ';
        $result = Text::utf8($string);
        $expected = [64256, 64257, 64258, 64259, 64260, 64261, 64262, 64275, 64276, 64277, 64278, 64279];
        $this->assertEquals($expected, $result);
    }

    /**
     * testAscii method
     *
     * @return void
     */
    public function testAscii()
    {
        $input = [33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
                            58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,
                            83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105,
                            106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126];
        $result = Text::ascii($input);

        $expected = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $this->assertEquals($expected, $result);

        $input = [161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181,
                                182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200];
        $result = Text::ascii($input);

        $expected = '¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈ';
        $this->assertEquals($expected, $result);

        $input = [201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221,
                                222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240, 241, 242,
                                243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260, 261, 262, 263,
                                264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284,
                                285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299, 300];
        $result = Text::ascii($input);
        $expected = 'ÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬ';
        $this->assertEquals($expected, $result);

        $input = [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321,
                                322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342,
                                343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360, 361, 362, 363,
                                364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380, 381, 382, 383, 384,
                                385, 386, 387, 388, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400];
        $expected = 'ĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏƐ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420, 421,
                                422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442,
                                443, 444, 445, 446, 447, 448, 449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460, 461, 462, 463,
                                464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481, 482, 483, 484,
                                485, 486, 487, 488, 489, 490, 491, 492, 493, 494, 495, 496, 497, 498, 499, 500];
        $expected = 'ƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯǰǱǲǳǴ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 621,
                                622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 637, 638, 639, 640, 641, 642,
                                643, 644, 645, 646, 647, 648, 649, 650, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660, 661, 662, 663,
                                664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680, 681, 682, 683, 684,
                                685, 686, 687, 688, 689, 690, 691, 692, 693, 694, 695, 696, 697, 698, 699, 700];
        $expected = 'əɚɛɜɝɞɟɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯʰʱʲʳʴʵʶʷʸʹʺʻʼ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040, 1041,
                                1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050, 1051];
        $expected = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏАБВГДЕЖЗИЙКЛ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060, 1061, 1062, 1063, 1064, 1065, 1066, 1067, 1068, 1069,
                                1070, 1071, 1072, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084, 1085, 1086, 1087,
                                1088, 1089, 1090, 1091, 1092, 1093, 1094, 1095, 1096, 1097, 1098, 1099, 1100];
        $expected = 'МНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1401, 1402, 1403, 1404, 1405, 1406, 1407];
        $expected = 'չպջռսվտ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1601, 1602, 1603, 1604, 1605, 1606, 1607, 1608, 1609, 1610, 1611, 1612, 1613, 1614, 1615];
        $expected = 'فقكلمنهوىيًٌٍَُ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [10032, 10033, 10034, 10035, 10036, 10037, 10038, 10039, 10040, 10041, 10042, 10043, 10044,
                                10045, 10046, 10047, 10048, 10049, 10050, 10051, 10052, 10053, 10054, 10055, 10056, 10057,
                                10058, 10059, 10060, 10061, 10062, 10063, 10064, 10065, 10066, 10067, 10068, 10069, 10070,
                                10071, 10072, 10073, 10074, 10075, 10076, 10077, 10078];
        $expected = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [11904, 11905, 11906, 11907, 11908, 11909, 11910, 11911, 11912, 11913, 11914, 11915, 11916, 11917, 11918, 11919,
                                11920, 11921, 11922, 11923, 11924, 11925, 11926, 11927, 11928, 11929, 11931, 11932, 11933, 11934, 11935, 11936,
                                11937, 11938, 11939, 11940, 11941, 11942, 11943, 11944, 11945, 11946, 11947, 11948, 11949, 11950, 11951, 11952,
                                11953, 11954, 11955, 11956, 11957, 11958, 11959, 11960, 11961, 11962, 11963, 11964, 11965, 11966, 11967, 11968,
                                11969, 11970, 11971, 11972, 11973, 11974, 11975, 11976, 11977, 11978, 11979, 11980, 11981, 11982, 11983, 11984,
                                11985, 11986, 11987, 11988, 11989, 11990, 11991, 11992, 11993, 11994, 11995, 11996, 11997, 11998, 11999, 12000];
        $expected = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺛⺜⺝⺞⺟⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟⻠';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [12101, 12102, 12103, 12104, 12105, 12106, 12107, 12108, 12109, 12110, 12111, 12112, 12113, 12114, 12115, 12116,
                                12117, 12118, 12119, 12120, 12121, 12122, 12123, 12124, 12125, 12126, 12127, 12128, 12129, 12130, 12131, 12132,
                                12133, 12134, 12135, 12136, 12137, 12138, 12139, 12140, 12141, 12142, 12143, 12144, 12145, 12146, 12147, 12148,
                                12149, 12150, 12151, 12152, 12153, 12154, 12155, 12156, 12157, 12158, 12159];
        $expected = '⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [45601, 45602, 45603, 45604, 45605, 45606, 45607, 45608, 45609, 45610, 45611, 45612, 45613, 45614, 45615, 45616,
                                45617, 45618, 45619, 45620, 45621, 45622, 45623, 45624, 45625, 45626, 45627, 45628, 45629, 45630, 45631, 45632,
                                45633, 45634, 45635, 45636, 45637, 45638, 45639, 45640, 45641, 45642, 45643, 45644, 45645, 45646, 45647, 45648,
                                45649, 45650, 45651, 45652, 45653, 45654, 45655, 45656, 45657, 45658, 45659, 45660, 45661, 45662, 45663, 45664,
                                45665, 45666, 45667, 45668, 45669, 45670, 45671, 45672, 45673, 45674, 45675, 45676, 45677, 45678, 45679, 45680,
                                45681, 45682, 45683, 45684, 45685, 45686, 45687, 45688, 45689, 45690, 45691, 45692, 45693, 45694, 45695, 45696,
                                45697, 45698, 45699, 45700];
        $expected = '눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿늀늁늂늃늄';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [65136, 65137, 65138, 65139, 65140, 65141, 65142, 65143, 65144, 65145, 65146, 65147, 65148, 65149, 65150, 65151,
                                65152, 65153, 65154, 65155, 65156, 65157, 65158, 65159, 65160, 65161, 65162, 65163, 65164, 65165, 65166, 65167,
                                65168, 65169, 65170, 65171, 65172, 65173, 65174, 65175, 65176, 65177, 65178, 65179, 65180, 65181, 65182, 65183,
                                65184, 65185, 65186, 65187, 65188, 65189, 65190, 65191, 65192, 65193, 65194, 65195, 65196, 65197, 65198, 65199,
                                65200];
        $expected = 'ﹰﹱﹲﹳﹴ﹵ﹶﹷﹸﹹﹺﹻﹼﹽﹾﹿﺀﺁﺂﺃﺄﺅﺆﺇﺈﺉﺊﺋﺌﺍﺎﺏﺐﺑﺒﺓﺔﺕﺖﺗﺘﺙﺚﺛﺜﺝﺞﺟﺠﺡﺢﺣﺤﺥﺦﺧﺨﺩﺪﺫﺬﺭﺮﺯﺰ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [65201, 65202, 65203, 65204, 65205, 65206, 65207, 65208, 65209, 65210, 65211, 65212, 65213, 65214, 65215, 65216,
                                65217, 65218, 65219, 65220, 65221, 65222, 65223, 65224, 65225, 65226, 65227, 65228, 65229, 65230, 65231, 65232,
                                65233, 65234, 65235, 65236, 65237, 65238, 65239, 65240, 65241, 65242, 65243, 65244, 65245, 65246, 65247, 65248,
                                65249, 65250, 65251, 65252, 65253, 65254, 65255, 65256, 65257, 65258, 65259, 65260, 65261, 65262, 65263, 65264,
                                65265, 65266, 65267, 65268, 65269, 65270, 65271, 65272, 65273, 65274, 65275, 65276];
        $expected = 'ﺱﺲﺳﺴﺵﺶﺷﺸﺹﺺﺻﺼﺽﺾﺿﻀﻁﻂﻃﻄﻅﻆﻇﻈﻉﻊﻋﻌﻍﻎﻏﻐﻑﻒﻓﻔﻕﻖﻗﻘﻙﻚﻛﻜﻝﻞﻟﻠﻡﻢﻣﻤﻥﻦﻧﻨﻩﻪﻫﻬﻭﻮﻯﻰﻱﻲﻳﻴﻵﻶﻷﻸﻹﻺﻻﻼ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [65345, 65346, 65347, 65348, 65349, 65350, 65351, 65352, 65353, 65354, 65355, 65356, 65357, 65358, 65359, 65360,
                                65361, 65362, 65363, 65364, 65365, 65366, 65367, 65368, 65369, 65370];
        $expected = 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [65377, 65378, 65379, 65380, 65381, 65382, 65383, 65384, 65385, 65386, 65387, 65388, 65389, 65390, 65391, 65392,
                                65393, 65394, 65395, 65396, 65397, 65398, 65399, 65400];
        $expected = '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [65401, 65402, 65403, 65404, 65405, 65406, 65407, 65408, 65409, 65410, 65411, 65412, 65413, 65414, 65415, 65416,
                                65417, 65418, 65419, 65420, 65421, 65422, 65423, 65424, 65425, 65426, 65427, 65428, 65429, 65430, 65431, 65432,
                                65433, 65434, 65435, 65436, 65437, 65438];
        $expected = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [292, 275, 314, 316, 335, 44, 32, 372, 337, 345, 316, 271, 33];
        $expected = 'Ĥēĺļŏ, Ŵőřļď!';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [72, 101, 108, 108, 111, 44, 32, 87, 111, 114, 108, 100, 33];
        $expected = 'Hello, World!';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [168];
        $expected = '¨';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [191];
        $expected = '¿';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [269, 105, 110, 105];
        $expected = 'čini';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [109, 111, 263, 105];
        $expected = 'moći';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [100, 114, 382, 97, 118, 110, 105];
        $expected = 'državni';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [25226, 30334, 24230, 35774, 20026, 39318, 39029];
        $expected = '把百度设为首页';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [19968, 20108, 19977, 21608, 27704, 40845];
        $expected = '一二三周永龍';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1280, 1282, 1284, 1286, 1288, 1290, 1292, 1294, 1296, 1298];
        $expected = 'ԀԂԄԆԈԊԌԎԐԒ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1281, 1283, 1285, 1287, 1289, 1291, 1293, 1295, 1296, 1298];
        $expected = 'ԁԃԅԇԉԋԍԏԐԒ';
        $result = Text::ascii($input);
        $this->assertEquals($expected, $result);

        $input = [1329, 1330, 1331, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1344, 1345, 1346, 1347,
                            1348, 1349, 1350, 1351, 1352, 1353, 1354, 1355, 1356, 1357, 1358, 1359, 1360, 1361, 1362, 1363, 1364, 1365,
                            1366, 1415];
        $result = Text::ascii($input);
        $expected = 'ԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿՀՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏՐՑՒՓՔՕՖև';
        $this->assertEquals($expected, $result);

        $input = [1377, 1378, 1379, 1380, 1381, 1382, 1383, 1384, 1385, 1386, 1387, 1388, 1389, 1390, 1391, 1392, 1393, 1394,
                                1395, 1396, 1397, 1398, 1399, 1400, 1401, 1402, 1403, 1404, 1405, 1406, 1407, 1408, 1409, 1410, 1411, 1412,
                                1413, 1414, 1415];
        $result = Text::ascii($input);
        $expected = 'աբգդեզէըթժիլխծկհձղճմյնշոչպջռսվտրցւփքօֆև';
        $this->assertEquals($expected, $result);

        $input = [4256, 4257, 4258, 4259, 4260, 4261, 4262, 4263, 4264, 4265, 4266, 4267, 4268, 4269, 4270, 4271, 4272, 4273, 4274,
                            4275, 4276, 4277, 4278, 4279, 4280, 4281, 4282, 4283, 4284, 4285, 4286, 4287, 4288, 4289, 4290, 4291, 4292, 4293];
        $result = Text::ascii($input);
        $expected = 'ႠႡႢႣႤႥႦႧႨႩႪႫႬႭႮႯႰႱႲႳႴႵႶႷႸႹႺႻႼႽႾႿჀჁჂჃჄჅ';
        $this->assertEquals($expected, $result);

        $input = [7680, 7682, 7684, 7686, 7688, 7690, 7692, 7694, 7696, 7698, 7700, 7702, 7704, 7706, 7708, 7710, 7712, 7714,
                                7716, 7718, 7720, 7722, 7724, 7726, 7728, 7730, 7732, 7734, 7736, 7738, 7740, 7742, 7744, 7746, 7748, 7750,
                                7752, 7754, 7756, 7758, 7760, 7762, 7764, 7766, 7768, 7770, 7772, 7774, 7776, 7778, 7780, 7782, 7784, 7786,
                                7788, 7790, 7792, 7794, 7796, 7798, 7800, 7802, 7804, 7806, 7808, 7810, 7812, 7814, 7816, 7818, 7820, 7822,
                                7824, 7826, 7828, 7830, 7831, 7832, 7833, 7834, 7840, 7842, 7844, 7846, 7848, 7850, 7852, 7854, 7856,
                                7858, 7860, 7862, 7864, 7866, 7868, 7870, 7872, 7874, 7876, 7878, 7880, 7882, 7884, 7886, 7888, 7890, 7892,
                                7894, 7896, 7898, 7900, 7902, 7904, 7906, 7908, 7910, 7912, 7914, 7916, 7918, 7920, 7922, 7924, 7926, 7928];
        $result = Text::ascii($input);
        $expected = 'ḀḂḄḆḈḊḌḎḐḒḔḖḘḚḜḞḠḢḤḦḨḪḬḮḰḲḴḶḸḺḼḾṀṂṄṆṈṊṌṎṐṒṔṖṘṚṜṞṠṢṤṦṨṪṬṮṰṲṴṶṸṺṼṾẀẂẄẆẈẊẌẎẐẒẔẖẗẘẙẚẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼẾỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴỶỸ';
        $this->assertEquals($expected, $result);

        $input = [7681, 7683, 7685, 7687, 7689, 7691, 7693, 7695, 7697, 7699, 7701, 7703, 7705, 7707, 7709, 7711, 7713, 7715,
                            7717, 7719, 7721, 7723, 7725, 7727, 7729, 7731, 7733, 7735, 7737, 7739, 7741, 7743, 7745, 7747, 7749, 7751,
                            7753, 7755, 7757, 7759, 7761, 7763, 7765, 7767, 7769, 7771, 7773, 7775, 7777, 7779, 7781, 7783, 7785, 7787,
                            7789, 7791, 7793, 7795, 7797, 7799, 7801, 7803, 7805, 7807, 7809, 7811, 7813, 7815, 7817, 7819, 7821, 7823,
                            7825, 7827, 7829, 7830, 7831, 7832, 7833, 7834, 7841, 7843, 7845, 7847, 7849, 7851, 7853, 7855, 7857, 7859,
                            7861, 7863, 7865, 7867, 7869, 7871, 7873, 7875, 7877, 7879, 7881, 7883, 7885, 7887, 7889, 7891, 7893, 7895,
                            7897, 7899, 7901, 7903, 7905, 7907, 7909, 7911, 7913, 7915, 7917, 7919, 7921, 7923, 7925, 7927, 7929];
        $result = Text::ascii($input);
        $expected = 'ḁḃḅḇḉḋḍḏḑḓḕḗḙḛḝḟḡḣḥḧḩḫḭḯḱḳḵḷḹḻḽḿṁṃṅṇṉṋṍṏṑṓṕṗṙṛṝṟṡṣṥṧṩṫṭṯṱṳṵṷṹṻṽṿẁẃẅẇẉẋẍẏẑẓẕẖẗẘẙẚạảấầẩẫậắằẳẵặẹẻẽếềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵỷỹ';
        $this->assertEquals($expected, $result);

        $input = [8486, 8490, 8491, 8498];
        $result = Text::ascii($input);
        $expected = 'ΩKÅℲ';
        $this->assertEquals($expected, $result);

        $input = [969, 107, 229, 8526];
        $result = Text::ascii($input);
        $expected = 'ωkåⅎ';
        $this->assertEquals($expected, $result);

        $input = [8544, 8545, 8546, 8547, 8548, 8549, 8550, 8551, 8552, 8553, 8554, 8555, 8556, 8557, 8558, 8559, 8579];
        $result = Text::ascii($input);
        $expected = 'ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩⅪⅫⅬⅭⅮⅯↃ';
        $this->assertEquals($expected, $result);

        $input = [8560, 8561, 8562, 8563, 8564, 8565, 8566, 8567, 8568, 8569, 8570, 8571, 8572, 8573, 8574, 8575, 8580];
        $result = Text::ascii($input);
        $expected = 'ⅰⅱⅲⅳⅴⅵⅶⅷⅸⅹⅺⅻⅼⅽⅾⅿↄ';
        $this->assertEquals($expected, $result);

        $input = [9398, 9399, 9400, 9401, 9402, 9403, 9404, 9405, 9406, 9407, 9408, 9409, 9410, 9411, 9412, 9413, 9414,
                            9415, 9416, 9417, 9418, 9419, 9420, 9421, 9422, 9423];
        $result = Text::ascii($input);
        $expected = 'ⒶⒷⒸⒹⒺⒻⒼⒽⒾⒿⓀⓁⓂⓃⓄⓅⓆⓇⓈⓉⓊⓋⓌⓍⓎⓏ';
        $this->assertEquals($expected, $result);

        $input = [9424, 9425, 9426, 9427, 9428, 9429, 9430, 9431, 9432, 9433, 9434, 9435, 9436, 9437, 9438, 9439, 9440, 9441,
                            9442, 9443, 9444, 9445, 9446, 9447, 9448, 9449];
        $result = Text::ascii($input);
        $expected = 'ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ';
        $this->assertEquals($expected, $result);

        $input = [11264, 11265, 11266, 11267, 11268, 11269, 11270, 11271, 11272, 11273, 11274, 11275, 11276, 11277, 11278, 11279,
                            11280, 11281, 11282, 11283, 11284, 11285, 11286, 11287, 11288, 11289, 11290, 11291, 11292, 11293, 11294, 11295,
                            11296, 11297, 11298, 11299, 11300, 11301, 11302, 11303, 11304, 11305, 11306, 11307, 11308, 11309, 11310];
        $result = Text::ascii($input);
        $expected = 'ⰀⰁⰂⰃⰄⰅⰆⰇⰈⰉⰊⰋⰌⰍⰎⰏⰐⰑⰒⰓⰔⰕⰖⰗⰘⰙⰚⰛⰜⰝⰞⰟⰠⰡⰢⰣⰤⰥⰦⰧⰨⰩⰪⰫⰬⰭⰮ';
        $this->assertEquals($expected, $result);

        $input = [11312, 11313, 11314, 11315, 11316, 11317, 11318, 11319, 11320, 11321, 11322, 11323, 11324, 11325, 11326, 11327,
                            11328, 11329, 11330, 11331, 11332, 11333, 11334, 11335, 11336, 11337, 11338, 11339, 11340, 11341, 11342, 11343,
                            11344, 11345, 11346, 11347, 11348, 11349, 11350, 11351, 11352, 11353, 11354, 11355, 11356, 11357, 11358];
        $result = Text::ascii($input);
        $expected = 'ⰰⰱⰲⰳⰴⰵⰶⰷⰸⰹⰺⰻⰼⰽⰾⰿⱀⱁⱂⱃⱄⱅⱆⱇⱈⱉⱊⱋⱌⱍⱎⱏⱐⱑⱒⱓⱔⱕⱖⱗⱘⱙⱚⱛⱜⱝⱞ';
        $this->assertEquals($expected, $result);

        $input = [11392, 11394, 11396, 11398, 11400, 11402, 11404, 11406, 11408, 11410, 11412, 11414, 11416, 11418, 11420,
                                    11422, 11424, 11426, 11428, 11430, 11432, 11434, 11436, 11438, 11440, 11442, 11444, 11446, 11448, 11450,
                                    11452, 11454, 11456, 11458, 11460, 11462, 11464, 11466, 11468, 11470, 11472, 11474, 11476, 11478, 11480,
                                    11482, 11484, 11486, 11488, 11490];
        $result = Text::ascii($input);
        $expected = 'ⲀⲂⲄⲆⲈⲊⲌⲎⲐⲒⲔⲖⲘⲚⲜⲞⲠⲢⲤⲦⲨⲪⲬⲮⲰⲲⲴⲶⲸⲺⲼⲾⳀⳂⳄⳆⳈⳊⳌⳎⳐⳒⳔⳖⳘⳚⳜⳞⳠⳢ';
        $this->assertEquals($expected, $result);

        $input = [11393, 11395, 11397, 11399, 11401, 11403, 11405, 11407, 11409, 11411, 11413, 11415, 11417, 11419, 11421, 11423,
                            11425, 11427, 11429, 11431, 11433, 11435, 11437, 11439, 11441, 11443, 11445, 11447, 11449, 11451, 11453, 11455,
                            11457, 11459, 11461, 11463, 11465, 11467, 11469, 11471, 11473, 11475, 11477, 11479, 11481, 11483, 11485, 11487,
                            11489, 11491];
        $result = Text::ascii($input);
        $expected = 'ⲁⲃⲅⲇⲉⲋⲍⲏⲑⲓⲕⲗⲙⲛⲝⲟⲡⲣⲥⲧⲩⲫⲭⲯⲱⲳⲵⲷⲹⲻⲽⲿⳁⳃⳅⳇⳉⳋⳍⳏⳑⳓⳕⳗⳙⳛⳝⳟⳡⳣ';
        $this->assertEquals($expected, $result);

        $input = [64256, 64257, 64258, 64259, 64260, 64261, 64262, 64275, 64276, 64277, 64278, 64279];
        $result = Text::ascii($input);
        $expected = 'ﬀﬁﬂﬃﬄﬅﬆﬓﬔﬕﬖﬗ';
        $this->assertEquals($expected, $result);
    }

    /**
     * testparseFileSize
     *
     * @dataProvider filesizes
     * @return void
     */
    public function testparseFileSize($params, $expected)
    {
        $result = Text::parseFileSize($params['size'], $params['default']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testparseFileSizeException
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testparseFileSizeException()
    {
        Text::parseFileSize('bogus', false);
    }

    /**
     * filesizes dataprovider
     *
     * @return array
     */
    public function filesizes()
    {
        return [
            [['size' => '512B', 'default' => false], 512],
            [['size' => '1KB', 'default' => false], 1024],
            [['size' => '1.5KB', 'default' => false], 1536],
            [['size' => '1MB', 'default' => false], 1048576],
            [['size' => '1mb', 'default' => false], 1048576],
            [['size' => '1.5MB', 'default' => false], 1572864],
            [['size' => '1GB', 'default' => false], 1073741824],
            [['size' => '1.5GB', 'default' => false], 1610612736],
            [['size' => '1K', 'default' => false], 1024],
            [['size' => '1.5K', 'default' => false], 1536],
            [['size' => '1M', 'default' => false], 1048576],
            [['size' => '1m', 'default' => false], 1048576],
            [['size' => '1.5M', 'default' => false], 1572864],
            [['size' => '1G', 'default' => false], 1073741824],
            [['size' => '1.5G', 'default' => false], 1610612736],
            [['size' => '512', 'default' => 'Unknown type'], 512],
            [['size' => '2VB', 'default' => 'Unknown type'], 'Unknown type']
        ];
    }

    /**
     * Data provider for testTransliterate()
     *
     * @return array
     */
    public function transliterateInputProvider()
    {
        return [
            [
                'Foo Bar: Not just for breakfast any-more', null,
                'Foo Bar: Not just for breakfast any-more'
            ],
            [
                'A æ Übérmensch på høyeste nivå! И я люблю PHP! есть. ﬁ ¦', null,
                'A ae Ubermensch pa hoyeste niva! I a lublu PHP! estʹ. fi ¦'
            ],
            [
                'Äpfel Über Öl grün ärgert groß öko', null,
                'Apfel Uber Ol grun argert gross oko'
            ],
            [
                'La langue française est un attribut de souveraineté en France', null,
                'La langue francaise est un attribut de souverainete en France'
            ],
            [
                '!@$#exciting stuff! - what !@-# was that?', null,
                '!@$#exciting stuff! - what !@-# was that?'
            ],
            [
                'controller/action/りんご/1', null,
                'controller/action/ringo/1'
            ],
            [
                'の話が出たので大丈夫かなあと', null,
                'no huaga chutanode da zhang fukanaato'
            ],
            [
                'posts/view/한국어/page:1/sort:asc', null,
                'posts/view/hangug-eo/page:1/sort:asc'
            ],
            [
                "non\xc2\xa0breaking\xc2\xa0space", null,
                'non breaking space'
            ]
        ];
    }

    /**
     * testTransliterate method
     *
     * @param string $string String
     * @param string $transliteratorId Transliterator Id
     * @param String $expected Exepected string
     * @return void
     * @dataProvider transliterateInputProvider
     */
    public function testTransliterate($string, $transliteratorId, $expected)
    {
        $result = Text::transliterate($string, $transliteratorId);
        $this->assertEquals($expected, $result);
    }

    public function slugInputProvider()
    {
        return [
            [
                'Foo Bar: Not just for breakfast any-more', [],
                'Foo-Bar-Not-just-for-breakfast-any-more'
            ],
            [
                'Foo Bar: Not just for breakfast any-more', ['replacement' => '_'],
                'Foo_Bar_Not_just_for_breakfast_any_more'
            ],
            [
                'Foo Bar: Not just for breakfast any-more', ['replacement' => '+'],
                'Foo+Bar+Not+just+for+breakfast+any+more'
            ],
            [
                'A æ Übérmensch på høyeste nivå! И я люблю PHP! есть. ﬁ ¦', [],
                'A-ae-Ubermensch-pa-hoyeste-niva-I-a-lublu-PHP-estʹ-fi'
            ],
            [
                'Äpfel Über Öl grün ärgert groß öko', [],
                'Apfel-Uber-Ol-grun-argert-gross-oko'
            ],
            [
                'The truth - and- more- news', [],
                'The-truth-and-more-news'
            ],
            [
                'The truth: and more news', [],
                'The-truth-and-more-news'
            ],
            [
                'La langue française est un attribut de souveraineté en France', [],
                'La-langue-francaise-est-un-attribut-de-souverainete-en-France'
            ],
            [
                '!@$#exciting stuff! - what !@-# was that?', [],
                'exciting-stuff-what-was-that'
            ],
            [
                '20% of profits went to me!', [],
                '20-of-profits-went-to-me'
            ],
            [
                '#this melts your face1#2#3', [],
                'this-melts-your-face1-2-3'
            ],
            [
                'controller/action/りんご/1', ['transliteratorId' => false],
                'controller-action-りんご-1'
            ],
            [
                'の話が出たので大丈夫かなあと', ['transliteratorId' => false],
                'の話が出たので大丈夫かなあと'
            ],
            [
                'posts/view/한국어/page:1/sort:asc', ['transliteratorId' => false],
                'posts-view-한국어-page-1-sort-asc'
            ],
            [
                "non\xc2\xa0breaking\xc2\xa0space", [],
                'non-breaking-space'
            ],
            [
                'Foo Bar: Not just for breakfast any-more', ['replacement' => ''],
                'FooBarNotjustforbreakfastanymore'
            ],
        ];
    }

    /**
     * testSlug method
     *
     * @param string $string String
     * @param array $options Options
     * @param String $expected Exepected string
     * @return void
     * @dataProvider slugInputProvider
     */
    public function testSlug($string, $options, $expected)
    {
        $result = Text::slug($string, $options);
        $this->assertEquals($expected, $result);
    }
}
