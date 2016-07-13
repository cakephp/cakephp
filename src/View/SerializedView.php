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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use RuntimeException;

/**
 * Parent class for view classes generating serialized outputs like JsonView and XmlView.
 */
class SerializedView extends View
{

    /**
     * Response type.
     *
     * @var string
     */
    protected $_responseType;

    /**
     * Constructor
     *
     * @param \Cake\Network\Request|null $request Request instance.
     * @param \Cake\Network\Response|null $response Response instance.
     * @param \Cake\Event\EventManager|null $eventManager EventManager instance.
     * @param array $viewOptions An array of view options
     */
    public function __construct(
        Request $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        parent::__construct($request, $response, $eventManager, $viewOptions);

        if ($response && $response instanceof Response) {
            $response->type($this->_responseType);
        }
    }

    /**
     * Load helpers only if serialization is disabled.
     *
     * @return void
     */
    public function loadHelpers()
    {
        if (empty($this->viewVars['_serialize'])) {
            parent::loadHelpers();
        }
    }

    /**
     * Render view template or return serialized data.
     *
     * ### Special parameters
     * `_serialize` To convert a set of view variables into a serialized form.
     *   Its value can be a string for single variable name or array for multiple
     *   names. If true all view variables will be serialized. If unset normal
     *   view template will be rendered.
     *
     * @param string|null $view The view being rendered.
     * @param string|null $layout The layout being rendered.
     * @return string|null The rendered view.
     */
    public function render($view = null, $layout = null)
    {
        $serialize = false;
        if (isset($this->viewVars['_serialize'])) {
            $serialize = $this->viewVars['_serialize'];
        }

        if ($serialize !== false) {
            $result = $this->_serialize($serialize);
            if ($result === false) {
                throw new RuntimeException('Serialization of View data failed.');
            }

            return (string)$result;
        }
        if ($view !== false && $this->_getViewFileName($view)) {
            return parent::render($view, false);
        }
    }
}
