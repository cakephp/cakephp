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
 * @since         3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * A view class that is used for AJAX responses.
 * Currently only switches the default layout and sets the response type - which just maps to
 * text/html by default.
 */
class AjaxView extends View
{

    /**
     *
     * @var string
     */
    public $layout = 'ajax';

    /**
     * Constructor
     *
     * @param \Cake\Network\Request|null $request The request object.
     * @param \Cake\Network\Response|null $response The response object.
     * @param \Cake\Event\EventManager|null $eventManager Event manager object.
     * @param array $viewOptions View options.
     */
    public function __construct(
        Request $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        parent::__construct($request, $response, $eventManager, $viewOptions);

        if ($response && $response instanceof Response) {
            $response->type('ajax');
        }
    }
}
