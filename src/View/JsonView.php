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
use function Cake\Core\h;

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
     * JSON layouts are located in the JSON subdirectory of `Layouts/`
     *
     * @var string
     */
    protected string $layoutPath = 'json';

    /**
     * JSON views are located in the 'json' subdirectory for controllers' views.
     *
     * @var string
     */
    protected string $subDir = 'json';

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
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'serialize' => null,
        'jsonOptions' => null,
        'jsonp' => null,
    ];

    /**
     * Mime-type this view class renders as.
     *
     * @return string The JSON content type.
     */
    public static function contentType(): string
    {
        return 'application/json';
    }

    /**
     * Render a JSON view.
     *
     * @param string|null $template The template being rendered.
     * @param string|false|null $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render(?string $template = null, string|false|null $layout = null): string
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
    protected function _serialize(array|string $serialize): string
    {
        $data = $this->_dataToSerialize($serialize);

        $jsonOptions = $this->getConfig('jsonOptions')
            ?? JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if ($jsonOptions === false) {
            $jsonOptions = 0;
        }
        $jsonOptions |= JSON_THROW_ON_ERROR;

        if (Configure::read('debug')) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        return (string)json_encode($data, $jsonOptions);
    }

    /**
     * Returns data to be serialized.
     *
     * @param array|string $serialize The name(s) of the view variable(s) that need(s) to be serialized.
     * @return mixed The data to serialize.
     */
    protected function _dataToSerialize(array|string $serialize): mixed
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

            return $data ?: null;
        }

        return $this->viewVars[$serialize] ?? null;
    }
}
