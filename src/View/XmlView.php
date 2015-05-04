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
 * $this->set(['posts' => $posts, '_serialize' => 'posts']);
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
 * $this->set('_serialize', ['posts', 'users']);
 * ```
 *
 * The above would generate a XML object that looks like:
 *
 * `<response><posts>...</posts><users>...</users></response>`
 *
 * If you don't use the `_serialize` key, you will need a view. You can use extended
 * views to provide layout like functionality.
 */
class XmlView extends View
{

    /**
     * XML layouts are located in the xml sub directory of `Layouts/`
     *
     * @var string
     */
    public $layoutPath = 'xml';

    /**
     * XML views are located in the 'xml' sub directory for controllers' views.
     *
     * @var string
     */
    public $subDir = 'xml';

    /**
     * Constructor
     *
     * @param \Cake\Network\Request|null $request Request instance
     * @param \Cake\Network\Response|null $response Response instance
     * @param \Cake\Event\EventManager|null $eventManager Event Manager
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
            $response->type('xml');
        }
    }

    /**
     * Skip loading helpers if this is a _serialize based view.
     *
     * @return void
     */
    public function loadHelpers()
    {
        if (isset($this->viewVars['_serialize'])) {
            return;
        }
        parent::loadHelpers();
    }

    /**
     * Render a XML view.
     *
     * Uses the special '_serialize' parameter to convert a set of
     * view variables into a XML response. Makes generating simple
     * XML responses very easy. You can omit the '_serialize' parameter,
     * and use a normal view + layout as well.
     *
     * @param string|null $view The view being rendered.
     * @param string|null $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render($view = null, $layout = null)
    {
        if (isset($this->viewVars['_serialize'])) {
            return $this->_serialize($this->viewVars['_serialize']);
        }
        if ($view !== false && $this->_getViewFileName($view)) {
            return parent::render($view, false);
        }
    }

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

        if (is_array($serialize)) {
            $data = [$rootNode => []];
            foreach ($serialize as $alias => $key) {
                if (is_numeric($alias)) {
                    $alias = $key;
                }
                $data[$rootNode][$alias] = $this->viewVars[$key];
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

        return Xml::fromArray($data, $options)->asXML();
    }
}
