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
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 * A view class that is used for JSON responses.
 *
 * It allows you to omit templates and layouts if you just need to emit JSON
 * string as response.
 *
 * In your controller, you could do the following:
 *
 * ```
 * $this->set(['posts' => $posts]);
 * ```
 *
 * By default all view variables will be serialized to JSON. So for above example
 * the `$posts` view variable will be serialized into JSON.
 *
 * You can also define `'_serialize'` to specify which view variables to serialize.
 *
 * ```
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', ['posts', 'users']);
 * ```
 *
 * The above would generate a JSON object that looks like:
 *
 * `{"posts": [...], "users": [...]}`
 *
 * If you need the regular view template processing you can set `_serialize` to
 * false which will turn off automatic serialization and instead view template
 * and layotu will be used.
 *
 * You can also enable JSONP support by setting parameter `_jsonp` to true or a
 * string to specify custom query string parameter name which will contain the
 * callback function name.
 */
class JsonView extends View
{

    /**
     * JSON layouts are located in the json sub directory of `Layouts/`
     *
     * @var string
     */
    public $layoutPath = 'json';

    /**
     * JSON views are located in the 'json' sub directory for controllers' views.
     *
     * @var string
     */
    public $subDir = 'json';

    /**
     * List of special view vars.
     *
     * @var array
     */
    protected $_specialVars = ['_serialize', '_jsonOptions', '_jsonp'];

    /**
     * Constructor
     *
     * @param \Cake\Network\Request $request Request instance.
     * @param \Cake\Network\Response $response Response instance.
     * @param \Cake\Event\EventManager $eventManager EventManager instance.
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
            $response->type('json');
        }
    }

    /**
     * Load helpers only if serialization is disabled.
     *
     * @return void
     */
    public function loadHelpers()
    {
        if (isset($this->viewVars['_serialize']) &&
            $this->viewVars['_serialize'] === false
        ) {
            parent::loadHelpers();
        }
    }

    /**
     * Render a JSON view.
     *
     * ### Special parameters
     * `_serialize` To convert a set of view variables into a JSON response.
     *   Its value can be a string for single variable name or array for multiple names.
     *   It can also be set to false to turn off serialization and use a normal
     *   view + layout as well. If unset or true all view variables will be serialized.
     * `_jsonp` Enables JSONP support and wraps response in callback function provided in query string.
     *   - Setting it to true enables the default query string parameter "callback".
     *   - Setting it to a string value, uses the provided query string parameter for finding the
     *     JSONP callback name.
     *
     * @param string|null $view The view being rendered.
     * @param string|null $layout The layout being rendered.
     * @return string|null The rendered view.
     */
    public function render($view = null, $layout = null)
    {
        $serialize = true;
        if (isset($this->viewVars['_serialize'])) {
            $serialize = $this->viewVars['_serialize'];
        }

        $return = null;
        if ($serialize !== false) {
            $return = $this->_serialize($serialize);
        } elseif ($view !== false && $this->_getViewFileName($view)) {
            $return = parent::render($view, false);
        }

        if (!empty($this->viewVars['_jsonp'])) {
            $jsonpParam = $this->viewVars['_jsonp'];
            if ($this->viewVars['_jsonp'] === true) {
                $jsonpParam = 'callback';
            }
            if (isset($this->request->query[$jsonpParam])) {
                $return = sprintf('%s(%s)', h($this->request->query[$jsonpParam]), $return);
                $this->response->type('js');
            }
        }

        return $return;
    }

    /**
     * Serialize view vars
     *
     * ### Special parameters
     * `_jsonOptions` You can set custom options for json_encode() this way,
     *   e.g. `JSON_HEX_TAG | JSON_HEX_APOS`.
     *
     * @param array|string|bool $serialize The name(s) of the view variable(s)
     *   that need(s) to be serialized. If true all available view variables.
     * @return string The serialized data
     */
    protected function _serialize($serialize)
    {
        $data = $this->_dataToSerialize($serialize);

        $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        if (isset($this->viewVars['_jsonOptions'])) {
            if ($this->viewVars['_jsonOptions'] === false) {
                $jsonOptions = 0;
            } else {
                $jsonOptions = $this->viewVars['_jsonOptions'];
            }
        }

        if (Configure::read('debug')) {
            $jsonOptions = $jsonOptions | JSON_PRETTY_PRINT;
        }
        return json_encode($data, $jsonOptions);
    }

    /**
     * Returns data to be serialized.
     *
     * @param array|string|bool $serialize The name(s) of the view variable(s)
     *   that need(s) to be serialized. If null or true all available view
     *   variables will be used.
     * @return mixed The data to serialize.
     */
    protected function _dataToSerialize($serialize = null)
    {
        if ($serialize === null || $serialize === true) {
            $data = array_diff_key(
                $this->viewVars,
                array_flip($this->_specialVars)
            );

            if (empty($data)) {
                return null;
            }

            if (count($data) === 1) {
                return current($data);
            }

            return $data;
        }

        if (is_array($serialize)) {
            $data = [];
            foreach ($serialize as $alias => $key) {
                if (is_numeric($alias)) {
                    $alias = $key;
                }
                if (array_key_exists($key, $this->viewVars)) {
                    $data[$alias] = $this->viewVars[$key];
                }
            }
            return !empty($data) ? $data : null;
        }

        return isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
    }
}
