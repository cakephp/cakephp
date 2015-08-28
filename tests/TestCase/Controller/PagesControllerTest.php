<?php
/**
 * PagesControllerTest file
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingTemplateException;
use TestApp\Controller\PagesController;

/**
 * PagesControllerTest class
 *
 */
class PagesControllerTest extends TestCase
{

    /**
     * testDisplay method
     *
     * @return void
     */
    public function testDisplay()
    {
        $Pages = new PagesController(new Request(), new Response());

        $Pages->viewBuilder()->templatePath('Posts');
        $Pages->display('index');
        $this->assertRegExp('/posts index/', $Pages->response->body());
        $this->assertEquals('index', $Pages->viewVars['page']);
    }

    /**
     * Test that missing template renders 404 page in production
     *
     * @expectedException \Cake\Network\Exception\NotFoundException
     * @expectedExceptionCode 404
     * @return void
     */
    public function testMissingTemplate()
    {
        Configure::write('debug', false);
        $Pages = new PagesController(new Request(), new Response());
        $Pages->display('non_existing_page');
    }

    /**
     * Test that missing template in debug mode renders missing_template error page
     *
     * @expectedException \Cake\View\Exception\MissingTemplateException
     * @expectedExceptionCode 500
     * @return void
     */
    public function testMissingTemplateInDebug()
    {
        Configure::write('debug', true);
        $Pages = new PagesController(new Request(), new Response());
        $Pages->display('non_existing_page');
    }
}
