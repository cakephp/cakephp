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
 * @since         0.10.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

/**
 * Request object handling for alternative HTTP requests.
 *
 * This Component checks for requests for different content types like JSON, XML,
 * XMLHttpRequest(AJAX) and configures the response object and view builder accordingly.
 *
 * It can also check for HTTP caching headers like `Last-Modified`, `If-Modified-Since`
 * etc. and return a response accordingly.
 *
 * @link https://book.cakephp.org/4/en/controllers/components/request-handling.html
 */
class RequestHandlerComponent extends Component
{
    /**
     * Contains the file extension parsed out by the Router
     *
     * @var string|null
     * @see \Cake\Routing\Router::extensions()
     */
    protected $ext;

    /**
     * The template type to use when rendering the given content type.
     *
     * @var string|null
     */
    protected $_renderType;

    /**
     * Default config
     *
     * These are merged with user-provided config when the component is used.
     *
     * - `checkHttpCache` - Whether to check for HTTP cache. Default `true`.
     * - `viewClassMap` - Mapping between type and view classes. If undefined
     *   JSON, XML, and AJAX will be mapped. Defining any types will omit the defaults.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'checkHttpCache' => true,
        'viewClassMap' => [],
    ];

    /**
     * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
     *
     * @param \Cake\Controller\ComponentRegistry $registry ComponentRegistry object.
     * @param array $config Array of config.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $config += [
            'viewClassMap' => [
                'json' => 'Json',
                'xml' => 'Xml',
                'ajax' => 'Ajax',
            ],
        ];
        parent::__construct($registry, $config);
    }

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.startup' => 'startup',
            'Controller.beforeRender' => 'beforeRender',
        ];
    }

    /**
     * Set the extension based on the `Accept` header or URL extension.
     *
     * Compares the accepted types and configured extensions.
     * If there is one common type, that is assigned as the ext/content type for the response.
     * The type with the highest weight will be set. If the highest weight has more
     * than one type matching the extensions, the order in which extensions are specified
     * determines which type will be set.
     *
     * If html is one of the preferred types, no content type will be set, this
     * is to avoid issues with browsers that prefer HTML and several other content types.
     *
     * @param \Cake\Http\ServerRequest $request The request instance.
     * @param \Cake\Http\Response $response The response instance.
     * @return void
     */
    protected function _setExtension(ServerRequest $request, Response $response): void
    {
        $accept = $request->parseAccept();
        if (empty($accept) || current($accept)[0] === 'text/html') {
            return;
        }

        /** @var array $accepts */
        $accepts = $response->mapType($accept);
        $preferredTypes = current($accepts);
        if (array_intersect($preferredTypes, ['html', 'xhtml'])) {
            return;
        }

        $extensions = array_unique(
            array_merge(Router::extensions(), array_keys($this->getConfig('viewClassMap')))
        );
        foreach ($accepts as $types) {
            $ext = array_intersect($extensions, $types);
            if ($ext) {
                $this->ext = current($ext);
                break;
            }
        }
    }

    /**
     * The startup method of the RequestHandler enables several automatic behaviors
     * related to the detection of certain properties of the HTTP request, including:
     *
     * If the XML data is POSTed, the data is parsed into an XML object, which is assigned
     * to the $data property of the controller, which can then be saved to a model object.
     *
     * @param \Cake\Event\EventInterface $event The startup event that was fired.
     * @return void
     */
    public function startup(EventInterface $event): void
    {
        $controller = $this->getController();
        $request = $controller->getRequest();
        $response = $controller->getResponse();

        $this->ext = $request->getParam('_ext');
        if (!$this->ext || in_array($this->ext, ['html', 'htm'], true)) {
            $this->_setExtension($request, $response);
        }

        $isAjax = $request->is('ajax');
        $controller->setRequest($request->withAttribute('isAjax', $isAjax));

        if (!$this->ext && $isAjax) {
            $this->ext = 'ajax';
        }
    }

    /**
     * Checks if the response can be considered different according to the request
     * headers, and the caching response headers. If it was not modified, then the
     * render process is skipped. And the client will get a blank response with a
     * "304 Not Modified" header.
     *
     * - If Router::extensions() is enabled, the layout and template type are
     *   switched based on the parsed extension or `Accept` header. For example,
     *   if `controller/action.xml` is requested, the view path becomes
     *   `templates/Controller/xml/action.php`. Also if `controller/action` is
     *   requested with `Accept: application/xml` in the headers the view
     *   path will become `templates/Controller/xml/action.php`. Layout and template
     *   types will only switch to mime-types recognized by Cake\Http\Response.
     *   If you need to declare additional mime-types, you can do so using
     *   Cake\Http\Response::setTypeMap() in your controller's beforeFilter() method.
     * - If a helper with the same name as the extension exists, it is added to
     *   the controller.
     * - If the extension is of a type that RequestHandler understands, it will
     *   set that Content-type in the response header.
     *
     * @param \Cake\Event\EventInterface $event The Controller.beforeRender event.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException If invoked extension is not configured.
     */
    public function beforeRender(EventInterface $event): void
    {
        $controller = $this->getController();
        $response = $controller->getResponse();

        if ($this->ext && !in_array($this->ext, ['html', 'htm'], true)) {
            if (!$response->getMimeType($this->ext)) {
                throw new NotFoundException('Invoked extension not recognized/configured: ' . $this->ext);
            }

            $this->renderAs($controller, $this->ext);
            $response = $controller->getResponse();
        } else {
            $response = $response->withCharset(Configure::read('App.encoding'));
        }

        if (
            $this->_config['checkHttpCache'] &&
            $response->checkNotModified($controller->getRequest())
        ) {
            $controller->setResponse($response);
            $event->stopPropagation();

            return;
        }

        $controller->setResponse($response);
    }

    /**
     * Determines which content types the client accepts. Acceptance is based on
     * the file extension parsed by the Router (if present), and by the HTTP_ACCEPT
     * header. Unlike Cake\Http\ServerRequest::accepts() this method deals entirely with mapped content types.
     *
     * Usage:
     *
     * ```
     * $this->RequestHandler->accepts(['xml', 'html', 'json']);
     * ```
     *
     * Returns true if the client accepts any of the supplied types.
     *
     * ```
     * $this->RequestHandler->accepts('xml');
     * ```
     *
     * Returns true if the client accepts XML.
     *
     * @param string|array|null $type Can be null (or no parameter), a string type name, or an
     *   array of types
     * @return mixed If null or no parameter is passed, returns an array of content
     *   types the client accepts. If a string is passed, returns true
     *   if the client accepts it. If an array is passed, returns true
     *   if the client accepts one or more elements in the array.
     */
    public function accepts($type = null)
    {
        $controller = $this->getController();
        /** @var array $accepted */
        $accepted = $controller->getRequest()->accepts();

        if (!$type) {
            return $controller->getResponse()->mapType($accepted);
        }

        if (is_array($type)) {
            foreach ($type as $t) {
                $t = $this->mapAlias($t);
                if (in_array($t, $accepted, true)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($type)) {
            return in_array($this->mapAlias($type), $accepted, true);
        }

        return false;
    }

    /**
     * Determines the content type of the data the client has sent (i.e. in a POST request)
     *
     * @param string|array|null $type Can be null (or no parameter), a string type name, or an array of types
     * @return mixed If a single type is supplied a boolean will be returned. If no type is provided
     *   The mapped value of CONTENT_TYPE will be returned. If an array is supplied the first type
     *   in the request content type will be returned.
     */
    public function requestedWith($type = null)
    {
        $controller = $this->getController();
        $request = $controller->getRequest();

        if (
            !$request->is('post') &&
            !$request->is('put') &&
            !$request->is('patch') &&
            !$request->is('delete')
        ) {
            return null;
        }
        if (is_array($type)) {
            foreach ($type as $t) {
                if ($this->requestedWith($t)) {
                    return $t;
                }
            }

            return false;
        }

        [$contentType] = explode(';', $request->contentType() ?? '');
        if ($type === null) {
            return $controller->getResponse()->mapType($contentType);
        }

        if (is_string($type)) {
            return $type === $controller->getResponse()->mapType($contentType);
        }
    }

    /**
     * Determines which content-types the client prefers. If no parameters are given,
     * the single content-type that the client most likely prefers is returned. If $type is
     * an array, the first item in the array that the client accepts is returned.
     * Preference is determined primarily by the file extension parsed by the Router
     * if provided, and secondarily by the list of content-types provided in
     * HTTP_ACCEPT.
     *
     * @param string|array|null $type An optional array of 'friendly' content-type names, i.e.
     *   'html', 'xml', 'js', etc.
     * @return string|bool|null If $type is null or not provided, the first content-type in the
     *    list, based on preference, is returned. If a single type is provided
     *    a boolean will be returned if that type is preferred.
     *    If an array of types are provided then the first preferred type is returned.
     *    If no type is provided the first preferred type is returned.
     */
    public function prefers($type = null)
    {
        $controller = $this->getController();

        $acceptRaw = $controller->getRequest()->parseAccept();
        if (empty($acceptRaw)) {
            return $type ? $type === $this->ext : $this->ext;
        }

        /** @var array $accepts */
        $accepts = $controller->getResponse()->mapType(array_shift($acceptRaw));
        if (!$type) {
            if (empty($this->ext) && !empty($accepts)) {
                return $accepts[0];
            }

            return $this->ext;
        }

        $types = (array)$type;
        if (count($types) === 1) {
            if ($this->ext) {
                return in_array($this->ext, $types, true);
            }

            return in_array($types[0], $accepts, true);
        }

        $intersect = array_values(array_intersect($accepts, $types));
        if (!$intersect) {
            return false;
        }

        return $intersect[0];
    }

    /**
     * Sets either the view class if one exists or the layout and template path of the view.
     * The names of these are derived from the $type input parameter.
     *
     * ### Usage:
     *
     * Render the response as an 'ajax' response.
     *
     * ```
     * $this->RequestHandler->renderAs($this, 'ajax');
     * ```
     *
     * Render the response as an XML file and force the result as a file download.
     *
     * ```
     * $this->RequestHandler->renderAs($this, 'xml', ['attachment' => 'myfile.xml'];
     * ```
     *
     * @param \Cake\Controller\Controller $controller A reference to a controller object
     * @param string $type Type of response to send (e.g: 'ajax')
     * @param array $options Array of options to use
     * @return void
     * @see \Cake\Controller\Component\RequestHandlerComponent::respondAs()
     */
    public function renderAs(Controller $controller, string $type, array $options = []): void
    {
        $defaults = ['charset' => 'UTF-8'];
        $viewClassMap = $this->getConfig('viewClassMap');

        if (Configure::read('App.encoding') !== null) {
            $defaults['charset'] = Configure::read('App.encoding');
        }
        $options += $defaults;

        $builder = $controller->viewBuilder();
        if (array_key_exists($type, $viewClassMap)) {
            $view = $viewClassMap[$type];
        } else {
            $view = Inflector::classify($type);
        }

        $viewClass = null;
        if ($builder->getClassName() === null) {
            $viewClass = App::className($view, 'View', 'View');
        }

        if ($viewClass) {
            $builder->setClassName($viewClass);
        } else {
            if (!$this->_renderType) {
                $builder->setTemplatePath((string)$builder->getTemplatePath() . DIRECTORY_SEPARATOR . $type);
            } else {
                $builder->setTemplatePath(preg_replace(
                    "/([\/\\\\]{$this->_renderType})$/",
                    DIRECTORY_SEPARATOR . $type,
                    (string)$builder->getTemplatePath()
                ));
            }

            $this->_renderType = $type;
            $builder->setLayoutPath($type);
        }

        if ($controller->getResponse()->getMimeType($type)) {
            $this->respondAs($type, $options);
        }
    }

    /**
     * Sets the response header based on type map index name. This wraps several methods
     * available on Cake\Http\Response. It also allows you to use Content-Type aliases.
     *
     * @param string $type Friendly type name, i.e. 'html' or 'xml', or a full content-type,
     *    like 'application/x-shockwave'.
     * @param array $options If $type is a friendly type name that is associated with
     *    more than one type of content, $index is used to select which content-type to use.
     * @return bool Returns false if the friendly type name given in $type does
     *    not exist in the type map, or if the Content-type header has
     *    already been set by this method.
     */
    public function respondAs($type, array $options = []): bool
    {
        $defaults = ['index' => null, 'charset' => null, 'attachment' => false];
        $options += $defaults;

        $cType = $type;
        $controller = $this->getController();
        $response = $controller->getResponse();

        if (strpos($type, '/') === false) {
            $cType = $response->getMimeType($type);
        }
        if (is_array($cType)) {
            if (isset($cType[$options['index']])) {
                $cType = $cType[$options['index']];
            }

            if ($this->prefers($cType)) {
                $cType = $this->prefers($cType);
            } else {
                $cType = $cType[0];
            }
        }

        if (!$cType) {
            return false;
        }

        $response = $response->withType($cType);

        if (!empty($options['charset'])) {
            $response = $response->withCharset($options['charset']);
        }
        if (!empty($options['attachment'])) {
            $response = $response->withDownload($options['attachment']);
        }
        $controller->setResponse($response);

        return true;
    }

    /**
     * Maps a content type alias back to its mime-type(s)
     *
     * @param string|array $alias String alias to convert back into a content type. Or an array of aliases to map.
     * @return string|array|null Null on an undefined alias. String value of the mapped alias type. If an
     *   alias maps to more than one content type, the first one will be returned. If an array is provided
     *   for $alias, an array of mapped types will be returned.
     */
    public function mapAlias($alias)
    {
        if (is_array($alias)) {
            return array_map([$this, 'mapAlias'], $alias);
        }

        $type = $this->getController()->getResponse()->getMimeType($alias);
        if ($type) {
            if (is_array($type)) {
                return $type[0];
            }

            return $type;
        }

        return null;
    }
}
