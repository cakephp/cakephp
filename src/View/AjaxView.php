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
 * @since         3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

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
     * @param \Cake\Http\ServerRequest|null $request The request object.
     * @param \Cake\Http\Response|null $response The response object.
     * @param \Cake\Event\EventManager|null $eventManager Event manager object.
     * @param array $viewOptions View options.
     */
    public function __construct(
        ServerRequest $request = null,
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
