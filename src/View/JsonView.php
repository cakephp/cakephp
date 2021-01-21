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
use RuntimeException;

/**
 * A view class that is used for JSON responses.
 *
 * It allows you to omit templates if you just need to emit JSON string as response.
 *
 * In your controller, you could do the following:
 *
 * ```
 * $this->set(['posts' => $posts]);
 * $this->viewBuilder()->setOption('serialize', true);
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
 * $this->viewBuilder()->setOption('serialize', true);
 * ```
 *
 * The above would generate a JSON object that looks like:
 *
 * `{"posts": [...], "users": [...]}`
 *
 * You can also set `'serialize'` to a string or array to serialize only the
 * specified view variables.
 *
 * If you don't set the `serialize` option, you will need a view template.
 * You can use extended views to provide layout-like functionality.
 *
 * You can also enable JSONP support by setting `jsonp` option to true or a
 * string to specify custom query string parameter name which will contain the
 * callback function name.
 */
class JsonView extends SerializedView
{
    /**
     * JSON layouts are located in the JSON sub directory of `Layouts/`
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
     * Default config options.
     *
     * Use ViewBuilder::setOption()/setOptions() in your controller to set these options.
     *
     * - `serialize`: Option to convert a set of view variables into a serialized response.
     *   Its value can be a string for single variable name or array for multiple
     *   names. If true all view variables will be serialized. If null or false
     *   normal view template will be rendered.
     * - `jsonOptions`: Options for json_encode(). For e.g. `JSON_HEX_TAG | JSON_HEX_APOS`.
     * - `jsonp`: Enables JSONP support and wraps response in callback function provided in query string.
     *   - Setting it to true enables the default query string parameter "callback".
     *   - Setting it to a string value, uses the provided query string parameter
     *     for finding the JSONP callback name.
     *
     * @var array
     * @pslam-var array{serialize:string|bool|null, jsonOptions: int|null, jsonp: bool|string|null}
     */
    protected $_defaultConfig = [
        'serialize' => null,
        'jsonOptions' => null,
        'jsonp' => null,
    ];

    /**
     * Render a JSON view.
     *
     * @param string|null $template The template being rendered.
     * @param string|false|null $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render(?string $template = null, $layout = null): string
    {
        $return = parent::render($template, $layout);

        $jsonp = $this->getConfig('jsonp');
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
     * @inheritDoc
     */
    protected function _serialize($serialize): string
    {
        $data = $this->_dataToSerialize($serialize);

        $jsonOptions = $this->getConfig('jsonOptions');
        if ($jsonOptions === null) {
            $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR;
        } elseif ($jsonOptions === false) {
            $jsonOptions = 0;
        }

        if (Configure::read('debug')) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        if (defined('JSON_THROW_ON_ERROR')) {
            $jsonOptions |= JSON_THROW_ON_ERROR;
        }

        $return = json_encode($data, $jsonOptions);
        if ($return === false) {
            throw new RuntimeException(json_last_error_msg(), json_last_error());
        }

        return $return;
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
