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
        $this->response->body('Hello Jane');

        return $this->response;
    }

    /**
     * No autoRender
     *
     * @return void
     */
    public function noRender()
    {
        $this->autoRender = false;
        $this->response->body('autoRender false body');
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
        if ($this->request->param('stop') === 'startup') {
            $this->response->body('startup stop');

            return $this->response;
        }
    }

    /**
     * shutdown process.
     */
    public function shutdownProcess()
    {
        parent::shutdownProcess();
        if ($this->request->param('stop') === 'shutdown') {
            $this->response->body('shutdown stop');

            return $this->response;
        }
    }
}
