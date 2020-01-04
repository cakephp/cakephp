<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Psr\Http\Message\ResponseInterface;

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
    protected $modelClass = 'Posts';

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
     * @return string
     */
    public function invalid()
    {
        return 'Some string';
    }

    /**
     * Startup process
     *
     * \Psr\Http\Message\ResponseInterface|null
     */
    public function startupProcess(): ?ResponseInterface
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
     * \Psr\Http\Message\ResponseInterface|null
     */
    public function shutdownProcess(): ?ResponseInterface
    {
        parent::shutdownProcess();
        if ($this->request->getParam('stop') === 'shutdown') {
            return $this->response->withStringBody('shutdown stop');
        }

        return null;
    }
}
