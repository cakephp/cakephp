<?php
declare(strict_types=1);

/**
 * XmlViewTest file
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
use Cake\Utility\Xml;

/**
 * XmlViewTest
 */
class XmlViewTest extends TestCase
{
    protected $fixtures = ['core.Authors'];

    public function setUp(): void
    {
        parent::setUp();
        Configure::write('debug', false);
    }

    /**
     * testRenderWithoutView method
     */
    public function testRenderWithoutView(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['users' => ['user' => ['user1', 'user2']]];
        $Controller->set(['users' => $data]);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', 'users');
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame(Xml::build($data)->asXML(), $output);
        $this->assertSame('application/xml', $View->getResponse()->getType());

        $data = [
            [
                'User' => [
                    'username' => 'user1',
                ],
            ],
            [
                'User' => [
                    'username' => 'user2',
                ],
            ],
        ];
        $Controller->set(['users' => $data]);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', 'users');
        $View = $Controller->createView();
        $output = $View->render();

        $expected = Xml::build(['response' => ['users' => $data]])->asXML();
        $this->assertSame($expected, $output);

        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('rootNode', 'custom_name');
        $View = $Controller->createView();
        $output = $View->render();

        $expected = Xml::build(['custom_name' => ['users' => $data]])->asXML();
        $this->assertSame($expected, $output);
    }

    /**
     * Test that rendering with _serialize does not load helpers
     */
    public function testRenderSerializeNoHelpers(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $Controller->set([
            'tags' => ['cakephp', 'framework'],
        ]);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', 'tags');
        $View = $Controller->createView();
        $View->render();
        $this->assertFalse(isset($View->Html), 'No helper loaded.');
    }

    /**
     * Test that rendering with _serialize respects XML options.
     */
    public function testRenderSerializeWithOptions(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = [
            'tags' => [
                    'tag' => [
                        [
                            'id' => '1',
                            'name' => 'defect',
                        ],
                        [
                            'id' => '2',
                            'name' => 'enhancement',
                        ],
                    ],
            ],
        ];
        $xmlOptions = ['format' => ['format' => 'attributes', 'return' => 'domdocument']];

        $Controller->set($data);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', ['tags', 'nope'])
            ->setOption('xmlOptions', $xmlOptions);
        $View = $Controller->createView();
        $result = $View->render();

        $expected = Xml::build(['response' => ['tags' => $data['tags']]], $xmlOptions)->saveXML();
        $this->assertSame($expected, $result);
    }

    /**
     * Test that rendering with _serialize can work with string setting.
     */
    public function testRenderSerializeWithString(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = [
            'tags' => [
                'tags' => [
                    'tag' => [
                        [
                            'id' => '1',
                            'name' => 'defect',
                        ],
                        [
                            'id' => '2',
                            'name' => 'enhancement',
                        ],
                    ],
                ],
            ],
        ];
        $xmlOptions = ['format' => 'attributes'];

        $Controller->set($data);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', 'tags')
            ->setOption('xmlOptions', $xmlOptions);
        $View = $Controller->createView();
        $result = $View->render();

        $expected = Xml::build($data['tags'], $xmlOptions)->asXML();
        $this->assertSame($expected, $result);
    }

    /**
     * Test render with an array in _serialize
     */
    public function testRenderWithoutViewMultiple(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->set($data);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', ['no', 'user']);
        $View = $Controller->createView();
        $this->assertSame('application/xml', $View->getResponse()->getType());
        $output = $View->render();
        $expected = [
            'response' => ['no' => $data['no'], 'user' => $data['user']],
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);

        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('rootNode', 'custom_name');
        $View = $Controller->createView();
        $output = $View->render();
        $expected = [
            'custom_name' => ['no' => $data['no'], 'user' => $data['user']],
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);
    }

    /**
     * Test render with an array in _serialize and alias
     */
    public function testRenderWithoutViewMultipleAndAlias(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['original_name' => 'my epic name', 'user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->set($data);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', ['new_name' => 'original_name', 'user']);
        $View = $Controller->createView();
        $this->assertSame('application/xml', $View->getResponse()->getType());
        $output = $View->render();
        $expected = [
            'response' => ['new_name' => $data['original_name'], 'user' => $data['user']],
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);

        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('rootNode', 'custom_name');
        $View = $Controller->createView();
        $output = $View->render();
        $expected = [
            'custom_name' => ['new_name' => $data['original_name'], 'user' => $data['user']],
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);
    }

    /**
     * test rendering with _serialize true
     */
    public function testRenderWithSerializeTrue(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['users' => ['user' => ['user1', 'user2']]];
        $Controller->set(['users' => $data]);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', true);
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame(Xml::build($data)->asXML(), $output);
        $this->assertSame('application/xml', $View->getResponse()->getType());

        $data = ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller = new Controller($Request, $Response);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', true);
        $Controller->set($data);
        $View = $Controller->createView();
        $output = $View->render();
        $expected = [
            'response' => $data,
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);
    }

    /**
     * testRenderWithView method
     */
    public function testRenderWithView(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $Controller->setName('Posts');

        $data = [
            [
                'User' => [
                    'username' => 'user1',
                ],
            ],
            [
                'User' => [
                    'username' => 'user2',
                ],
            ],
        ];
        $Controller->set('users', $data);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $View->setTemplatePath('Posts');
        $output = $View->render('index');

        $expected = [
            'users' => ['user' => ['user1', 'user2']],
        ];
        $expected = Xml::build($expected)->asXML();
        $this->assertSame($expected, $output);
        $this->assertSame('application/xml', $View->getResponse()->getType());
        $this->assertInstanceOf('Cake\View\HelperRegistry', $View->helpers());
    }

    public function testSerializingResultSet(): void
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);

        $data = $this->getTableLocator()->get('Authors')
            ->find('all')
            ->where(['id' => 1]);
        $Controller->set(['authors' => $data]);
        $Controller->viewBuilder()
            ->setClassName('Xml')
            ->setOption('serialize', true);
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<response><authors><id>1</id><name>mariano</name></authors></response>' . "\n",
            $output
        );
        $this->assertSame('application/xml', $View->getResponse()->getType());
    }
}
