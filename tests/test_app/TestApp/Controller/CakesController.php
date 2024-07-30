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
     */
    protected ?string $modelClass = 'Posts';

    /**
     * index method
     */
    public function index(): \Cake\Http\Response
    {
        return $this->response->withStringBody('Hello Jane');
    }

    /**
     * No autoRender
     */
    public function noRender(): void
    {
        $this->autoRender = false;
        $this->response = $this->response->withStringBody('autoRender false body');
    }

    /**
     * invalid method
     */
    public function invalid(): string
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
