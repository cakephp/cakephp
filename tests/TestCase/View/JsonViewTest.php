<?php
declare(strict_types=1);

/**
 * JsonViewTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\SerializationFailureException;

/**
 * JsonViewTest
 */
class JsonViewTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('debug', false);
    }

    /**
     * Generates testRenderWithoutView data.
     *
     * Note: array($data, $serialize, expected)
     *
     * @return array
     */
    public static function renderWithoutViewProvider()
    {
        return [
            // Test render with a valid string in _serialize.
            [
                ['data' => ['user' => 'fake', 'list' => ['item1', 'item2']]],
                'data',
                null,
                json_encode(['user' => 'fake', 'list' => ['item1', 'item2']]),
            ],

            // Test render with a string with an invalid key in _serialize.
            [
                ['data' => ['user' => 'fake', 'list' => ['item1', 'item2']]],
                'no_key',
                null,
                json_encode(null),
            ],

            // Test render with a valid array in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['no', 'user'],
                null,
                json_encode(['no' => 'nope', 'user' => 'fake']),
            ],

            // Test render with an empty array in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                [],
                null,
                json_encode(null),
            ],

            // Test render with a valid array with an invalid key in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['no', 'user', 'no_key'],
                null,
                json_encode(['no' => 'nope', 'user' => 'fake']),
            ],

            // Test render with a valid array with only an invalid key in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['no_key'],
                null,
                json_encode(null),
            ],

            // Test render with True in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                true,
                JSON_HEX_QUOT,
                json_encode(['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']]),
            ],

            // Test render with True in _serialize and single var
            [
                ['no' => 'nope'],
                true,
                null,
                json_encode(['no' => 'nope']),
            ],

            // Test render with empty string in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                '',
                null,
                json_encode(null),
            ],

            // Test render with a valid array in _serialize and alias.
            [
                ['original_name' => 'my epic name', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['new_name' => 'original_name', 'user'],
                null,
                json_encode(['new_name' => 'my epic name', 'user' => 'fake']),
            ],

            // Test render with an a valid array in _serialize and alias of a null value.
            [
                ['null' => null],
                ['null'],
                null,
                json_encode(['null' => null]),
            ],

            // Test render with a False value to be serialized.
            [
                ['false' => false],
                'false',
                null,
                json_encode(false),
            ],

            // Test render with a True value to be serialized.
            [
                ['true' => true],
                'true',
                null,
                json_encode(true),
            ],

            // Test render with an empty string value to be serialized.
            [
                ['empty' => ''],
                'empty',
                null,
                json_encode(''),
            ],

            // Test render with a zero value to be serialized.
            [
                ['zero' => 0],
                'zero',
                null,
                json_encode(0),
            ],

            // Test render with encode <, >, ', &, and " for RFC4627-compliant to be serialized.
            [
                ['rfc4627_escape' => '<tag> \'quote\' "double-quote" &'],
                'rfc4627_escape',
                null,
                json_encode('<tag> \'quote\' "double-quote" &', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            ],

            // Test render with _jsonOptions = false to be serialized.
            [
                ['noescape' => '<tag> \'quote\' "double-quote" &'],
                'noescape',
                false,
                json_encode('<tag> \'quote\' "double-quote" &'),
            ],

            // Test render with setting _jsonOptions to be serialized.
            [
                ['rfc4627_escape' => '<tag> \'quote\' "double-quote" &'],
                'rfc4627_escape',
                JSON_HEX_TAG | JSON_HEX_APOS,
                json_encode('<tag> \'quote\' "double-quote" &', JSON_HEX_TAG | JSON_HEX_APOS),
            ],

            // Test render of NAN
            [
                ['value' => NAN],
                true,
                null,
                '{"value":0}',
            ],

            // Test render of INF
            [
                ['value' => INF],
                true,
                null,
                '{"value":0}',
            ],
        ];
    }

    /**
     * Test render with a valid string in _serialize.
     *
     * @param array $data
     * @param string|null $serialize
     * @param int|false|null $jsonOptions
     * @param string $expected
     * @dataProvider renderWithoutViewProvider
     * @return void
     */
    public function testRenderWithoutView($data, $serialize, $jsonOptions, $expected)
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $this->deprecated(function () use ($Controller, $data, $serialize, $jsonOptions, $expected) {
            $Controller->set($data);
            $Controller->set('_serialize', $serialize);
            $Controller->set('_jsonOptions', $jsonOptions);
            $Controller->viewBuilder()->setClassName('Json');
            $View = $Controller->createView();
            $output = $View->render();

            $this->assertSame($expected, $output);
        });

        $Controller = new Controller($Request, $Response);

        $Controller->set($data);
        $Controller->viewBuilder()
            ->setOptions(compact('serialize', 'jsonOptions'))
            ->setClassName('Json');
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame($expected, $output);
    }

    /**
     * Test that rendering with _serialize does not load helpers.
     *
     * @return void
     */
    public function testRenderSerializeNoHelpers()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $Controller->set([
            'tags' => ['cakephp', 'framework'],
        ]);
        $Controller->viewBuilder()
            ->setClassName('Json')
            ->setOption('serialize', 'tags');
        $View = $Controller->createView();
        $View->render();

        $this->assertFalse(isset($View->Html), 'No helper loaded.');
    }

    /**
     * testJsonpResponse method
     *
     * @return void
     */
    public function testJsonpResponse()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $data = ['user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->set([
            'data' => $data,
        ]);
        $Controller->viewBuilder()
            ->setClassName('Json')
            ->setOptions(['serialize' => 'data', 'jsonp' => true]);
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame(json_encode($data), $output);
        $this->assertSame('application/json', $View->getResponse()->getType());

        $View->setRequest($View->getRequest()->withQueryParams(['callback' => 'jfunc']));
        $output = $View->render();
        $expected = 'jfunc(' . json_encode($data) . ')';
        $this->assertSame($expected, $output);
        $this->assertSame('application/javascript', $View->getResponse()->getType());

        $Controller->viewBuilder()->setOption('jsonp', 'jsonCallback');
        $Controller->setRequest($Controller->getRequest()->withQueryParams(['jsonCallback' => 'jfunc']));
        $View = $Controller->createView();
        $output = $View->render();
        $expected = 'jfunc(' . json_encode($data) . ')';
        $this->assertSame($expected, $output);
    }

    /**
     * Test render with a View file specified.
     *
     * @return void
     */
    public function testRenderWithView()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $Controller->setName('Posts');

        $data = [
            'User' => [
                'username' => 'fake',
            ],
            'Item' => [
                ['name' => 'item1'],
                ['name' => 'item2'],
            ],
        ];
        $Controller->set('user', $data);
        $Controller->viewBuilder()->setClassName('Json');
        $View = $Controller->createView();
        $View->setTemplatePath($Controller->getName());
        $output = $View->render('index');

        $expected = json_encode(['user' => 'fake', 'list' => ['item1', 'item2'], 'paging' => null]);
        $this->assertSame($expected, $output);
        $this->assertSame('application/json', $View->getResponse()->getType());
    }

    public function testSerializationFailureException()
    {
        $this->expectException(SerializationFailureException::class);
        $this->expectExceptionMessage('Serialization of View data failed.');

        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $data = "\xB1\x31";
        $Controller->set('data', $data);
        $Controller->viewBuilder()
            ->setOption('serialize', 'data')
            ->setOption('jsonOptions', false)
            ->setClassName('Json');
        $View = $Controller->createView();
        $View->render();
    }
}
