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

/**
 * A view class that is used for JSON responses.
 *
 * It allows you to omit templates if you just need to emit JSON string as response.
 *
 * In your controller, you could do the following:
 *
 * ```
 * $this->set(['posts' => $posts]);
 * $this->set('_serialize', true);
 * ```
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into JSON.
 *
 * You can also set multiple view variables for serialization. This will create
 * a top level object containing all the named view variables:
 *
 * ```
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', true);
 * ```
 *
 * The above would generate a JSON object that looks like:
 *
 * `{"posts": [...], "users": [...]}`
 *
 * You can also set `'_serialize'` to a string or array to serialize only the
 * specified view variables.
 *
 * If you don't use the `_serialize`, you will need a view template. You can use
 * extended views to provide layout-like functionality.
 *
 * You can also enable JSONP support by setting parameter `_jsonp` to true or a
 * string to specify custom query string parameter name which will contain the
 * callback function name.
 */
class JsonView extends SerializedView
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
     * Response type.
     *
     * @var string
     */
    protected $_responseType = 'json';

    /**
     * List of special view vars.
     *
     * @var array
     */
    protected $_specialVars = ['_serialize', '_jsonOptions', '_jsonp'];

    /**
     * Render a JSON view.
     *
     * ### Special parameters
     * `_serialize` To convert a set of view variables into a JSON response.
     *   Its value can be a string for single variable name or array for multiple
     *   names. If true all view variables will be serialized. It unset normal
     *   view template will be rendered.
     * `_jsonp` Enables JSONP support and wraps response in callback function
     *   provided in query string.
     *   - Setting it to true enables the default query string parameter "callback".
     *   - Setting it to a string value, uses the provided query string parameter
     *     for finding the JSONP callback name.
     *
     * @param string|null $view The view being rendered.
     * @param string|null $layout The layout being rendered.
     * @return string|null The rendered view.
     */
    public function render($view = null, $layout = null)
    {
        $return = parent::render($view, $layout);

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
     * @return string|false The serialized data, or boolean false if not serializable.
     */
    protected function _serialize($serialize)
    {
        $data = $this->_dataToSerialize($serialize);

        $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT |
            JSON_PARTIAL_OUTPUT_ON_ERROR;

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
     * @param array|string|bool $serialize The name(s) of the view variable(s) that
     *   need(s) to be serialized. If true all available view variables will be used.
     * @return mixed The data to serialize.
     */
    protected function _dataToSerialize($serialize = true)
    {
        if ($serialize === true) {
            $data = array_diff_key(
                $this->viewVars,
                array_flip($this->_specialVars)
            );

            if (empty($data)) {
                return null;
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
