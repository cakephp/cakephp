<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Event\Event;
use Cake\Routing\Router;

/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 */
class ErrorController extends Controller
{

    /**
     * Constructor
     *
     * @param \Cake\Network\Request|null $request Request instance.
     * @param \Cake\Network\Response|null $response Response instance.
     */
    public function __construct($request = null, $response = null)
    {
        parent::__construct($request, $response);
        if (!isset($this->RequestHandler)) {
            $this->loadComponent('RequestHandler');
        }
        $eventManager = $this->eventManager();
        if (isset($this->Auth)) {
            $eventManager->off($this->Auth);
        }
        if (isset($this->Security)) {
            $eventManager->off($this->Security);
        }
    }

    /**
     * beforeRender callback.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $this->viewBuilder()->templatePath('Error');
    }
}
