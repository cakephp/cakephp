<?php
declare(strict_types=1);

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
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
    protected $layoutPath = 'json';

    /**
     * JSON views are located in the 'json' sub directory for controllers' views.
     *
     * @var string
     */
    protected $subDir = 'json';

    /**
     * Response type.
     *
     * @var string
     */
    protected $_responseType = 'json';

    /**
     * List of special view vars.
     *
     * Use ViewBuilder::setOption()/setOptions() to set these vars.
     *
     * @var array
     */
    protected $_specialVars = ['serialize', 'jsonOptions', 'jsonp'];

    /**
     * Options for json_encode().
     *
     * e.g. `JSON_HEX_TAG | JSON_HEX_APOS`.
     *
     * @var int|null
     */
    protected $jsonOptions;

    /**
     * Enables JSONP support and wraps response in callback function provided in query string.
     *
     * - Setting it to true enables the default query string parameter "callback".
     * - Setting it to a string value, uses the provided query string parameter
     *     for finding the JSONP callback name.
     *
     * @var true|string|null
     */
    protected $jsonp;

    /**
     * Render a JSON view.
     *
     * @param string|null $template The template being rendered.
     * @param string|null|false $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render(?string $template = null, $layout = null): string
    {
        $return = parent::render($template, $layout);

        $jsonp = $this->getOption('jsonp');
        if ($jsonp) {
            if ($jsonp === true) {
                $jsonp = 'callback';
            }
            if ($this->request->getQuery($jsonp)) {
                $return = sprintf('%s(%s)', h($this->request->getQuery($jsonp)), $return);
                $this->response = $this->response->withType('js');
            }
        }

        return $return;
    }

    /**
     * Serialize view vars
     *
     * @param array|string $serialize The name(s) of the view variable(s) that need(s) to be serialized.
     * @return string|false The serialized data, or boolean false if not serializable.
     */
    protected function _serialize($serialize)
    {
        $data = $this->_dataToSerialize($serialize);

        $jsonOptions = $this->getOption('jsonOptions');
        if ($jsonOptions === null) {
            $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR;
        } elseif ($jsonOptions === false) {
            $jsonOptions = 0;
        }

        if (Configure::read('debug')) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $jsonOptions);
    }

    /**
     * Returns data to be serialized.
     *
     * @param array|string $serialize The name(s) of the view variable(s) that need(s) to be serialized.
     * @return mixed The data to serialize.
     */
    protected function _dataToSerialize($serialize)
    {
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

        return $this->viewVars[$serialize] ?? null;
    }
}
