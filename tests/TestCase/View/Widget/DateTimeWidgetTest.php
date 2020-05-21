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
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\Form\NullContext;
use Cake\View\StringTemplate;
use Cake\View\Widget\DateTimeWidget;

/**
 * DateTimeWidget test case
 */
class DateTimeWidgetTest extends TestCase
{
    /**
     * @setUp
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $templates = [
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
        ];
        $this->templates = new StringTemplate($templates);
        $this->context = new NullContext([]);
        $this->DateTime = new DateTimeWidget($this->templates);
    }

    /**
     * Data provider for testing various types of invalid selected values.
     *
     * @return array
     */
    public static function invalidSelectedValuesProvider()
    {
        return [
            'false' => [false],
            'true' => [true],
            'string' => ['Bag of poop'],
            'array' => [[
                'derp' => 'hurt',
            ]],
        ];
    }

    /**
     * test rendering selected values.
     *
     * @dataProvider invalidSelectedValuesProvider
     * @return void
     */
    public function testRenderInvalid($selected)
    {
        $result = $this->DateTime->render(['val' => $selected, 'type' => 'month'], $this->context);
        $now = new \DateTime();
        $expected = [
            'input' => ['type' => 'month', 'name' => '', 'value' => $now->format('Y-m')],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Data provider for testing various acceptable selected values.
     *
     * @return array
     */
    public static function selectedValuesProvider()
    {
        $date = new \DateTime('2014-01-20 12:30:45');

        return [
            'DateTime' => [$date],
            'string' => [$date->format('Y-m-d H:i:s')],
            'int string' => [$date->format('U')],
            'int' => [$date->getTimestamp()],
        ];
    }

    /**
     * test rendering selected values.
     *
     * @dataProvider selectedValuesProvider
     * @return void
     */
    public function testRenderValid($selected)
    {
        $result = $this->DateTime->render(['val' => $selected], $this->context);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => '',
                'value' => '2014-01-20T12:30:45',
                'step' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTimezoneOption
     *
     * @return void
     */
    public function testTimezoneOption()
    {
        $result = $this->DateTime->render([
            'val' => '2019-02-03 10:00:00',
            'timezone' => 'Asia/Kolkata',
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => '',
                'value' => '2019-02-03T15:30:00',
                'step' => '1',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    public function testUnsettingStep()
    {
        $result = $this->DateTime->render([
            'val' => '2019-02-03 10:11:12',
            'step' => null,
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => '',
                'value' => '2019-02-03T10:11:12',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->DateTime->render([
            'val' => '2019-02-03 10:11:12',
            'step' => false,
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => '',
                'value' => '2019-02-03T10:11:12',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    public function testDatetimeFormat()
    {
        $result = $this->DateTime->render([
            'val' => '2019-02-03 10:11:12',
            'format' => 'Y-m-d\TH:i',
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => '',
                'value' => '2019-02-03T10:11',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->DateTime->render([
            'val' => '2019-02-03 10:11:12',
            'format' => 'Y-m-d\TH:i',
            'step' => 120,
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'datetime-local',
                'name' => '',
                'step' => '120',
                'value' => '2019-02-03T10:11',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->DateTime->render([
            'type' => 'time',
            'val' => '10:11:12',
            'format' => 'H:i',
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'time',
                'name' => '',
                'value' => '10:11',
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->DateTime->render([
            'type' => 'time',
            'val' => '10:11:12',
            'format' => 'H:i',
            'step' => 120,
        ], $this->context);
        $expected = [
            'input' => [
                'type' => 'time',
                'name' => '',
                'step' => '120',
                'value' => '10:11',
            ],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test rendering with templateVars
     *
     * @return void
     */
    public function testRenderTemplateVars()
    {
        $templates = [
            'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}><span>{{help}}</span>',
        ];
        $this->templates->add($templates);
        $result = $this->DateTime->render([
            'name' => 'date',
            'templateVars' => ['help' => 'some help'],
        ], $this->context);

        $this->assertStringContainsString('<span>some help</span>', $result);
    }

    /**
     * testRenderInvalidTypeException
     *
     * @return void
     */
    public function testRenderInvalidTypeException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid type `foo` for input tag, expected datetime-local, date, time, month or week');
        $result = $this->DateTime->render(['type' => 'foo', 'val' => new \DateTime()], $this->context);
    }

    /**
     * Test that secureFields omits removed selects
     *
     * @return void
     */
    public function testSecureFields()
    {
        $data = [
            'name' => 'date',
        ];
        $result = $this->DateTime->secureFields($data);
        $expected = [
            'date',
        ];
        $this->assertEquals($expected, $result);
    }
}
