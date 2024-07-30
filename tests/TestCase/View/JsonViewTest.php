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

use ArrayIterator;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\SerializationFailureException;

/**
 * JsonViewTest
 */
class JsonViewTest extends TestCase
{
    protected function setUp(): void
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
    public static function renderWithoutViewProvider(): \Iterator
    {
        // Test render with a valid string in _serialize.
        yield [
            ['data' => ['user' => 'fake', 'list' => ['item1', 'item2']]],
            'data',
            null,
            json_encode(['user' => 'fake', 'list' => ['item1', 'item2']]),
        ];
        // Test render with a string with an invalid key in _serialize.
        yield [
            ['data' => ['user' => 'fake', 'list' => ['item1', 'item2']]],
            'no_key',
            null,
            json_encode(null),
        ];
        // Test render with a valid array in _serialize.
        yield [
            ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
            ['no', 'user'],
            null,
            json_encode(['no' => 'nope', 'user' => 'fake']),
        ];
        // Test render with a PaginatedResultset in _serialize.
        yield [
            ['users' => new PaginatedResultSet(new ArrayIterator([1 => 'a', 2 => 'b', 3 => 'c']), [])],
            ['users'],
            null,
            json_encode(['users' => [1 => 'a', 2 => 'b', 3 => 'c']]),
        ];
        // Test render with an empty array in _serialize.
        yield [
            ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
            [],
            null,
            json_encode(null),
        ];
        // Test render with a valid array with an invalid key in _serialize.
        yield [
            ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
            ['no', 'user', 'no_key'],
            null,
            json_encode(['no' => 'nope', 'user' => 'fake']),
        ];
        // Test render with a valid array with only an invalid key in _serialize.
        yield [
            ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
            ['no_key'],
            null,
            json_encode(null),
        ];
        // Test render with True in _serialize.
        yield [
            ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
            true,
            JSON_HEX_QUOT,
            json_encode(['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']]),
        ];
        // Test render with True in _serialize and single var
        yield [
            ['no' => 'nope'],
            true,
            null,
            json_encode(['no' => 'nope']),
        ];
        // Test render with empty string in _serialize.
        yield [
            ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']],
            '',
            null,
            json_encode(null),
        ];
        // Test render with a valid array in _serialize and alias.
        yield [
            ['original_name' => 'my epic name', 'user' => 'fake', 'list' => ['item1', 'item2']],
            ['new_name' => 'original_name', 'user'],
            null,
            json_encode(['new_name' => 'my epic name', 'user' => 'fake']),
        ];
        // Test render with an a valid array in _serialize and alias of a null value.
        yield [
            ['null' => null],
            ['null'],
            null,
            json_encode(['null' => null]),
        ];
        // Test render with a False value to be serialized.
        yield [
            ['false' => false],
            'false',
            null,
            json_encode(false),
        ];
        // Test render with a True value to be serialized.
        yield [
            ['true' => true],
            'true',
            null,
            json_encode(true),
        ];
        // Test render with an empty string value to be serialized.
        yield [
            ['empty' => ''],
            'empty',
            null,
            json_encode(''),
        ];
        // Test render with a zero value to be serialized.
        yield [
            ['zero' => 0],
            'zero',
            null,
            json_encode(0),
        ];
        // Test render with encode <, >, ', &, and " for RFC4627-compliant to be serialized.
        yield [
            ['rfc4627_escape' => '<tag> \'quote\' "double-quote" &'],
            'rfc4627_escape',
            null,
            json_encode('<tag> \'quote\' "double-quote" &', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
        ];
        // Test render with _jsonOptions = false to be serialized.
        yield [
            ['noescape' => '<tag> \'quote\' "double-quote" &'],
            'noescape',
            false,
            json_encode('<tag> \'quote\' "double-quote" &'),
        ];
        // Test render with setting _jsonOptions to be serialized.
        yield [
            ['rfc4627_escape' => '<tag> \'quote\' "double-quote" &'],
            'rfc4627_escape',
            JSON_HEX_TAG | JSON_HEX_APOS,
            json_encode('<tag> \'quote\' "double-quote" &', JSON_HEX_TAG | JSON_HEX_APOS),
        ];
        // Test render of NAN
        yield [
            ['value' => NAN],
            true,
            null,
            '{"value":0}',
        ];
        // Test render of INF
        yield [
            ['value' => INF],
            true,
            null,
            '{"value":0}',
        ];
    }

    /**
     * Test render with a valid string in _serialize.
     *
     * @param string|null $serialize
     * @param int|false|null $jsonOptions
     * @param string $expected
     * @dataProvider renderWithoutViewProvider
     */
    public function testRenderWithoutView(array $data, string|bool|array $serialize, int|bool|null $jsonOptions, bool|string $expected): void
    {
        $Request = new ServerRequest();
        $Controller = new Controller($Request);

        $Controller->set($data);
        $Controller->viewBuilder()
            ->setOptions(['serialize' => $serialize, 'jsonOptions' => $jsonOptions])
            ->setClassName('Json');
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame($expected, $output);
    }

    /**
     * Test that rendering with _serialize does not load helpers.
     */
    public function testRenderSerializeNoHelpers(): void
    {
        $Request = new ServerRequest();
        $Controller = new Controller($Request);

        $Controller->set([
            'tags' => ['cakephp', 'framework'],
        ]);
        $Controller->viewBuilder()
            ->setClassName('Json')
            ->setOption('serialize', 'tags');
        $View = $Controller->createView();
        $View->render();

        $this->assertNull($View->Html, 'No helper loaded.');
    }

    /**
     * testJsonpResponse method
     */
    public function testJsonpResponse(): void
    {
        $Request = new ServerRequest();
        $Controller = new Controller($Request);

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
     */
    public function testRenderWithView(): void
    {
        $Request = new ServerRequest();
        $Controller = new Controller($Request);
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

    public function testSerializationFailureException(): void
    {
        $this->expectException(SerializationFailureException::class);
        $this->expectExceptionMessage('Serialization of View data failed.');

        $Request = new ServerRequest();
        $Controller = new Controller($Request);

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
