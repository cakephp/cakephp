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
 * @since         2.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Utility\Xml;

/**
 * A view class that is used for creating XML responses.
 *
 * By setting the '_serialize' key in your controller, you can specify a view variable
 * that should be serialized to XML and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the XML response.
 *
 * In your controller, you could do the following:
 *
 * ```
 * $this->set(['posts' => $posts, '_serialize' => true]);
 * ```
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into XML.
 *
 * **Note** The view variable you specify must be compatible with Xml::fromArray().
 *
 * You can also define `'_serialize'` as an array. This will create an additional
 * top level element named `<response>` containing all the named view variables:
 *
 * ```
 * $this->set(compact('posts', 'users', 'stuff'));
 * $this->set('_serialize', true);
 * ```
 *
 * The above would generate a XML object that looks like:
 *
 * `<response><posts>...</posts><users>...</users></response>`
 *
 * You can also set `'_serialize'` to a string or array to serialize only the
 * specified view variables.
 *
 * If you don't use the `_serialize` key, you will need a view. You can use extended
 * views to provide layout like functionality.
 */
class XmlView extends SerializedView
{
    /**
     * XML layouts are located in the xml sub directory of `Layouts/`
     *
     * @var string
     */
    protected $layoutPath = 'xml';

    /**
     * XML views are located in the 'xml' sub directory for controllers' views.
     *
     * @var string
     */
    protected $subDir = 'xml';

    /**
     * Response type.
     *
     * @var string
     */
    protected $_responseType = 'xml';

    /**
     * List of special view vars.
     *
     * @var array
     */
    protected $_specialVars = ['_serialize', '_rootNode', '_xmlOptions'];

    /**
     * Serialize view vars.
     *
     * ### Special parameters
     * `_xmlOptions` You can set an array of custom options for Xml::fromArray() this way, e.g.
     *   'format' as 'attributes' instead of 'tags'.
     *
     * @param array|string $serialize The name(s) of the view variable(s) that need(s) to be serialized
     * @return string The serialized data
     */
    protected function _serialize($serialize)
    {
        $rootNode = isset($this->viewVars['_rootNode']) ? $this->viewVars['_rootNode'] : 'response';

        if ($serialize === true) {
            $serialize = array_diff(
                array_keys($this->viewVars),
                $this->_specialVars
            );

            if (empty($serialize)) {
                $serialize = null;
            } elseif (count($serialize) === 1) {
                $serialize = current($serialize);
            }
        }

        if (is_array($serialize)) {
            $data = [$rootNode => []];
            foreach ($serialize as $alias => $key) {
                if (is_numeric($alias)) {
                    $alias = $key;
                }
                if (array_key_exists($key, $this->viewVars)) {
                    $data[$rootNode][$alias] = $this->viewVars[$key];
                }
            }
        } else {
            $data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
            if (is_array($data) && Hash::numeric(array_keys($data))) {
                $data = [$rootNode => [$serialize => $data]];
            }
        }

        $options = [];
        if (isset($this->viewVars['_xmlOptions'])) {
            $options = $this->viewVars['_xmlOptions'];
        }
        if (Configure::read('debug')) {
            $options['pretty'] = true;
        }

        if (isset($options['return']) && strtolower($options['return']) === 'domdocument') {
            return Xml::fromArray($data, $options)->saveXML();
        }

        return Xml::fromArray($data, $options)->asXML();
    }
}
