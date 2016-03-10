<?php
/**
 * JsonViewTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;

/**
 * JsonViewTest
 *
 */
class JsonViewTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        Configure::write('debug', false);
    }

    /**
     * Generates testRenderWithoutView data.
     *
     * Note: array($data, $serialize, expected)
     *
     * @return void
     */
    public static function renderWithoutViewProvider()
    {
        return [
            // Test render with a valid string in _serialize.
            [
                ['data' => ['user' => 'fake', 'list' => ['item1', 'item2']]],
                'data',
                null,
                json_encode(['user' => 'fake', 'list' => ['item1', 'item2']])
            ],

            // Test render with a string with an invalid key in _serialize.
            [
                ['data' => ['user' => 'fake', 'list' => ['item1', 'item2']]],
                'no_key',
                null,
                json_encode(null)
            ],

            // Test render with a valid array in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['no', 'user'],
                null,
                json_encode(['no' => 'nope', 'user' => 'fake'])
            ],

            // Test render with an empty array in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                [],
                null,
                json_encode(null)
            ],

            // Test render with a valid array with an invalid key in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['no', 'user', 'no_key'],
                null,
                json_encode(['no' => 'nope', 'user' => 'fake'])
            ],

            // Test render with a valid array with only an invalid key in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['no_key'],
                null,
                json_encode(null)
            ],

            // Test render with Null in _serialize (unset).
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                null,
                null,
                null
            ],

            // Test render with False in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                false,
                null,
                null
            ],

            // Test render with True in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                true,
                JSON_HEX_QUOT,
                json_encode(['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']])
            ],

            // Test render with True in _serialize and single var
            [
                ['no' => 'nope'],
                true,
                null,
                json_encode(['no' => 'nope'])
            ],

            // Test render with empty string in _serialize.
            [
                ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
                '',
                null,
                json_encode(null)
            ],

            // Test render with a valid array in _serialize and alias.
            [
                ['original_name' => 'my epic name', 'user' => 'fake', 'list' => ['item1', 'item2']],
                ['new_name' => 'original_name', 'user'],
                null,
                json_encode(['new_name' => 'my epic name', 'user' => 'fake'])
            ],

            // Test render with an a valid array in _serialize and alias of a null value.
            [
                ['null' => null],
                ['null'],
                null,
                json_encode(['null' => null])
            ],

            // Test render with a False value to be serialized.
            [
                ['false' => false],
                'false',
                null,
                json_encode(false)
            ],

            // Test render with a True value to be serialized.
            [
                ['true' => true],
                'true',
                null,
                json_encode(true)
            ],

            // Test render with an empty string value to be serialized.
            [
                ['empty' => ''],
                'empty',
                null,
                json_encode('')
            ],

            // Test render with a zero value to be serialized.
            [
                ['zero' => 0],
                'zero',
                null,
                json_encode(0)
            ],

            // Test render with encode <, >, ', &, and " for RFC4627-compliant to be serialized.
            [
                ['rfc4627_escape' => '<tag> \'quote\' "double-quote" &'],
                'rfc4627_escape',
                null,
                json_encode('<tag> \'quote\' "double-quote" &', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
            ],

            // Test render with _jsonOptions = false to be serialized.
            [
                ['noescape' => '<tag> \'quote\' "double-quote" &'],
                'noescape',
                false,
                json_encode('<tag> \'quote\' "double-quote" &')
            ],

            // Test render with setting _jsonOptions to be serialized.
            [
                ['rfc4627_escape' => '<tag> \'quote\' "double-quote" &'],
                'rfc4627_escape',
                JSON_HEX_TAG | JSON_HEX_APOS,
                json_encode('<tag> \'quote\' "double-quote" &', JSON_HEX_TAG | JSON_HEX_APOS)
            ],
        ];
    }

    /**
     * Test render with a valid string in _serialize.
     *
     * @dataProvider renderWithoutViewProvider
     * @return void
     */
    public function testRenderWithoutView($data, $serialize, $jsonOptions, $expected)
    {
        $Request = new Request();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $Controller->set($data);
        $Controller->set('_serialize', $serialize);
        $Controller->set('_jsonOptions', $jsonOptions);
        $Controller->viewClass = 'Json';
        $View = $Controller->createView();
        $output = $View->render(false);

        $this->assertSame($expected, $output);
    }

    /**
     * Test that rendering with _serialize does not load helpers.
     *
     * @return void
     */
    public function testRenderSerializeNoHelpers()
    {
        $Request = new Request();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $Controller->helpers = ['Html'];
        $Controller->set([
            'tags' => ['cakephp', 'framework'],
            '_serialize' => 'tags'
        ]);
        $Controller->viewClass = 'Json';
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
        $Request = new Request();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $data = ['user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->set([
            'data' => $data,
            '_serialize' => 'data',
            '_jsonp' => true
        ]);
        $Controller->viewClass = 'Json';
        $View = $Controller->createView();
        $output = $View->render(false);

        $this->assertSame(json_encode($data), $output);
        $this->assertSame('application/json', $Response->type());

        $View->request->query = ['callback' => 'jfunc'];
        $output = $View->render(false);
        $expected = 'jfunc(' . json_encode($data) . ')';
        $this->assertSame($expected, $output);
        $this->assertSame('application/javascript', $Response->type());

        $View->request->query = ['jsonCallback' => 'jfunc'];
        $View->viewVars['_jsonp'] = 'jsonCallback';
        $output = $View->render(false);
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
        $Request = new Request();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $Controller->name = 'Posts';

        $data = [
            'User' => [
                'username' => 'fake'
            ],
            'Item' => [
                ['name' => 'item1'],
                ['name' => 'item2']
            ]
        ];
        $Controller->set('user', $data);
        $Controller->viewClass = 'Json';
        $View = $Controller->createView();
        $View->viewPath = $Controller->name;
        $output = $View->render('index');

        $expected = json_encode(['user' => 'fake', 'list' => ['item1', 'item2'], 'paging' => null]);
        $this->assertSame($expected, $output);
        $this->assertSame('application/json', $Response->type());
    }
}
