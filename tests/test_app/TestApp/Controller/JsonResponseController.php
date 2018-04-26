<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Http\Exception\InternalErrorException;

/**
 * PostsController class
 */
class JsonResponseController extends AppController
{
    /**
     * Components array
     *
     * @var array
     */
    public $components = [
        'Flash',
        'RequestHandler' => [
            'enableBeforeRedirect' => false
        ],
    ];

    public function apiGetData(){
        if(!$this->getRequest()->accepts('application/json')){
            throw new InternalErrorException( "Client MUST sent the Accept: application/json header");
        }

        $data = ['a','b','c','d'];
        $this->set(compact('data'));
        $this->set('_serialize', ['data']);
        $this->RequestHandler->renderAs($this, 'json');
    }
}
