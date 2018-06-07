<?php
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

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
     * Startup process
     *
     * \Cake\Http\Response|null
     */
    public function startupProcess(): ?Response
    {
        parent::startupProcess();
        if ($this->request->getParam('stop') === 'startup') {
            return $this->response->withStringBody('startup stop');
        }

        return null;
    }

    /**
     * Shutdown process
     *
     * \Cake\Http\Response|null
     */
    public function shutdownProcess(): ?Response
    {
        parent::shutdownProcess();
        if ($this->request->getParam('stop') === 'shutdown') {
            return $this->response->withStringBody('shutdown stop');
        }

        return null;
    }
}
