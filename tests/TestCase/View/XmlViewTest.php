<?php
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

    public function setUp()
    {
        parent::setUp();
        Configure::write('debug', false);
    }

    /**
     * testRenderWithoutView method
     *
     * @return void
     */
    public function testRenderWithoutView()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['users' => ['user' => ['user1', 'user2']]];
        $Controller->set(['users' => $data, '_serialize' => 'users']);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $output = $View->render(false);

        $this->assertSame(Xml::build($data)->asXML(), $output);
        $this->assertSame('application/xml', $View->response->getType());

        $data = [
            [
                'User' => [
                    'username' => 'user1'
                ]
            ],
            [
                'User' => [
                    'username' => 'user2'
                ]
            ]
        ];
        $Controller->set(['users' => $data, '_serialize' => 'users']);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $output = $View->render(false);

        $expected = Xml::build(['response' => ['users' => $data]])->asXML();
        $this->assertSame($expected, $output);

        $Controller->set('_rootNode', 'custom_name');
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $output = $View->render(false);

        $expected = Xml::build(['custom_name' => ['users' => $data]])->asXML();
        $this->assertSame($expected, $output);
    }

    /**
     * Test that rendering with _serialize does not load helpers
     *
     * @return void
     */
    public function testRenderSerializeNoHelpers()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $Controller->helpers = ['Html'];
        $Controller->set([
            '_serialize' => 'tags',
            'tags' => ['cakephp', 'framework']
        ]);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $View->render();
        $this->assertFalse(isset($View->Html), 'No helper loaded.');
    }

    /**
     * Test that rendering with _serialize respects XML options.
     *
     * @return void
     */
    public function testRenderSerializeWithOptions()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = [
            '_serialize' => ['tags', 'nope'],
            '_xmlOptions' => ['format' => 'attributes', 'return' => 'domdocument'],
            'tags' => [
                    'tag' => [
                        [
                            'id' => '1',
                            'name' => 'defect'
                        ],
                        [
                            'id' => '2',
                            'name' => 'enhancement'
                        ]
                    ]
            ]
        ];
        $Controller->set($data);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $result = $View->render();

        $expected = Xml::build(['response' => ['tags' => $data['tags']]], $data['_xmlOptions'])->saveXML();
        $this->assertSame($expected, $result);
    }

    /**
     * Test that rendering with _serialize can work with string setting.
     *
     * @return void
     */
    public function testRenderSerializeWithString()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = [
            '_serialize' => 'tags',
            '_xmlOptions' => ['format' => 'attributes'],
            'tags' => [
                'tags' => [
                    'tag' => [
                        [
                            'id' => '1',
                            'name' => 'defect'
                        ],
                        [
                            'id' => '2',
                            'name' => 'enhancement'
                        ]
                    ]
                ]
            ]
        ];
        $Controller->set($data);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $result = $View->render();

        $expected = Xml::build($data['tags'], $data['_xmlOptions'])->asXML();
        $this->assertSame($expected, $result);
    }

    /**
     * Test render with an array in _serialize
     *
     * @return void
     */
    public function testRenderWithoutViewMultiple()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->set($data);
        $Controller->set('_serialize', ['no', 'user']);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $this->assertSame('application/xml', $View->response->getType());
        $output = $View->render(false);
        $expected = [
            'response' => ['no' => $data['no'], 'user' => $data['user']]
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);

        $Controller->set('_rootNode', 'custom_name');
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $output = $View->render(false);
        $expected = [
            'custom_name' => ['no' => $data['no'], 'user' => $data['user']]
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);
    }

    /**
     * Test render with an array in _serialize and alias
     *
     * @return void
     */
    public function testRenderWithoutViewMultipleAndAlias()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['original_name' => 'my epic name', 'user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->set($data);
        $Controller->set('_serialize', ['new_name' => 'original_name', 'user']);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $this->assertSame('application/xml', $View->response->getType());
        $output = $View->render(false);
        $expected = [
            'response' => ['new_name' => $data['original_name'], 'user' => $data['user']]
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);

        $Controller->set('_rootNode', 'custom_name');
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $output = $View->render(false);
        $expected = [
            'custom_name' => ['new_name' => $data['original_name'], 'user' => $data['user']]
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);
    }

    /**
     * test rendering with _serialize true
     *
     * @return void
     */
    public function testRenderWithSerializeTrue()
    {
        $Request = new ServerRequest();
        $Response = new Response();
        $Controller = new Controller($Request, $Response);
        $data = ['users' => ['user' => ['user1', 'user2']]];
        $Controller->set(['users' => $data, '_serialize' => true]);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $output = $View->render();

        $this->assertSame(Xml::build($data)->asXML(), $output);
        $this->assertSame('application/xml', $View->response->getType());

        $data = ['no' => 'nope', 'user' => 'fake', 'list' => ['item1', 'item2']];
        $Controller->viewVars = [];
        $Controller->set($data);
        $Controller->set('_serialize', true);
        $View = $Controller->createView();
        $output = $View->render();
        $expected = [
            'response' => $data
        ];
        $this->assertSame(Xml::build($expected)->asXML(), $output);
    }

    /**
     * testRenderWithView method
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
            [
                'User' => [
                    'username' => 'user1'
                ]
            ],
            [
                'User' => [
                    'username' => 'user2'
                ]
            ]
        ];
        $Controller->set('users', $data);
        $Controller->viewBuilder()->setClassName('Xml');
        $View = $Controller->createView();
        $View->setTemplatePath('Posts');
        $output = $View->render('index');

        $expected = [
            'users' => ['user' => ['user1', 'user2']]
        ];
        $expected = Xml::build($expected)->asXML();
        $this->assertSame($expected, $output);
        $this->assertSame('application/xml', $View->response->getType());
        $this->assertInstanceOf('Cake\View\HelperRegistry', $View->helpers());
    }
}
