<?php
namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Network\Exception\NotFoundException;

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
     * @return \Cake\Network\Response
     */
    public function index()
    {
        $this->response->body('Hello Jane');
        return $this->response;
    }

    /**
     * invalid method
     *
     * @return \Cake\Network\Response
     */
    public function invalid()
    {
        return 'Some string';
    }
}
