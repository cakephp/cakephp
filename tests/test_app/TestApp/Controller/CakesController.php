<?php
namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * CakesController class
 */
class CakesController extends Controller
{
    /**
     * The default model to use.
     *
     * @var string
     */
    public $modelClass = 'Posts';

    /**
     * index method
     *
     * @return \Cake\Http\Response
     */
    public function index()
    {
        return $this->response->withStringBody('Hello Jane');
    }

    /**
     * No autoRender
     *
     * @return void
     */
    public function noRender()
    {
        $this->autoRender = false;
        $this->response = $this->response->withStringBody('autoRender false body');
    }

    /**
     * invalid method
     *
     * @return \Cake\Http\Response
     */
    public function invalid()
    {
        return 'Some string';
    }

    /**
     * startup process.
     */
    public function startupProcess()
    {
        parent::startupProcess();
        if ($this->request->getParam('stop') === 'startup') {
            return $this->response->withStringBody('startup stop');
        }
    }

    /**
     * shutdown process.
     */
    public function shutdownProcess()
    {
        parent::shutdownProcess();
        if ($this->request->getParam('stop') === 'shutdown') {
            return $this->response->withStringBody('shutdown stop');
        }
    }
}
