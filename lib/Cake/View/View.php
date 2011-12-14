<?php
/**
 * Methods for displaying presentation data in the view.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('HelperCollection', 'View');
App::uses('AppHelper', 'View/Helper');
App::uses('Router', 'Routing');

/**
 * View, the V in the MVC triad. View interacts with Helpers and view variables passed
 * in from the controller to render the results of the controller action.  Often this is HTML,
 * but can also take the form of JSON, XML, PDF's or streaming files.
 *
 * CakePHP uses a two-step-view pattern.  This means that the view content is rendered first,
 * and then inserted into the selected layout.  A special `$content_for_layout` variable is available
 * in the layout, and it contains the rendered view.  This also means you can pass data from the view to the
 * layout using `$this->set()`
 *
 * @package       Cake.View
 * @property      CacheHelper $Cache
 * @property      FormHelper $Form
 * @property      HtmlHelper $Html
 * @property      JsHelper $Js
 * @property      NumberHelper $Number
 * @property      PaginatorHelper $Paginator
 * @property      RssHelper $Rss
 * @property      SessionHelper $Session
 * @property      TextHelper $Text
 * @property      TimeHelper $Time
 */
class View extends Object {

/**
 * Helpers collection
 *
 * @var HelperCollection
 */
	public $Helpers;

/**
 * Name of the plugin.
 *
 * @link http://manual.cakephp.org/chapter/plugins
 * @var string
 */
	public $plugin = null;

/**
 * Name of the controller.
 *
 * @var string Name of controller
 */
	public $name = null;

/**
 * Current passed params
 *
 * @var mixed
 */
	public $passedArgs = array();

/**
 * An array of names of built-in helpers to include.
 *
 * @var mixed A single name as a string or a list of names as an array.
 */
	public $helpers = array('Html');

/**
 * Path to View.
 *
 * @var string Path to View
 */
	public $viewPath = null;

/**
 * Variables for the view
 *
 * @var array
 */
	public $viewVars = array();

/**
 * Name of view to use with this View.
 *
 * @var string
 */
	public $view = null;

/**
 * Name of layout to use with this View.
 *
 * @var string
 */
	public $layout = 'default';

/**
 * Path to Layout.
 *
 * @var string Path to Layout
 */
	public $layoutPath = null;

/**
 * Turns on or off Cake's conventional mode of applying layout files. On by default.
 * Setting to off means that layouts will not be automatically applied to rendered views.
 *
 * @var boolean
 */
	public $autoLayout = true;

/**
 * File extension. Defaults to Cake's template ".ctp".
 *
 * @var string
 */
	public $ext = '.ctp';

/**
 * Sub-directory for this view file.  This is often used for extension based routing.
 * Eg. With an `xml` extension, $subDir would be `xml/`
 *
 * @var string
 */
	public $subDir = null;

/**
 * Theme name.  If you are using themes, you should remember to use ThemeView as well.
 *
 * @var string
 */
	public $theme = null;

/**
 * Used to define methods a controller that will be cached.
 *
 * @see Controller::$cacheAction
 * @var mixed
 */
	public $cacheAction = false;

/**
 * Holds current errors for the model validation.
 *
 * @var array
 */
	public $validationErrors = array();

/**
 * True when the view has been rendered.
 *
 * @var boolean
 */
	public $hasRendered = false;

/**
 * List of generated DOM UUIDs.
 *
 * @var array
 */
	public $uuids = array();

/**
 * Holds View output.
 *
 * @var string
 */
	public $output = false;

/**
 * An instance of a CakeRequest object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request.
 *
 * @var CakeRequest
 */
	public $request;

/**
 * The Cache configuration View will use to store cached elements.  Changing this will change
 * the default configuration elements are stored under.  You can also choose a cache config
 * per element.
 *
 * @var string
 * @see View::element()
 */
	public $elementCache = 'default';

/**
 * List of variables to collect from the associated controller.
 *
 * @var array
 */
	protected $_passedVars = array(
		'viewVars', 'autoLayout', 'ext', 'helpers', 'view', 'layout', 'name',
		'layoutPath', 'viewPath', 'request', 'plugin', 'passedArgs', 'cacheAction'
	);

/**
 * Scripts (and/or other <head /> tags) for the layout.
 *
 * @var array
 */
	protected $_scripts = array();

/**
 * Holds an array of paths.
 *
 * @var array
 */
	protected $_paths = array();

/**
 * Indicate that helpers have been loaded.
 *
 * @var boolean
 */
	protected $_helpersLoaded = false;

/**
 * Constructor
 *
 * @param Controller $controller A controller object to pull View::_passedVars from.
 */
	public function __construct($controller) {
		if (is_object($controller)) {
			$count = count($this->_passedVars);
			for ($j = 0; $j < $count; $j++) {
				$var = $this->_passedVars[$j];
				$this->{$var} = $controller->{$var};
			}
		}
		$this->Helpers = new HelperCollection($this);
		parent::__construct();
	}

/**
 * Renders a piece of PHP with provided parameters and returns HTML, XML, or any other string.
 *
 * This realizes the concept of Elements, (or "partial layouts") and the $params array is used to send
 * data to be used in the element. Elements can be cached improving performance by using the `cache` option.
 *
 * @param string $name Name of template file in the/app/View/Elements/ folder
 * @param array $data Array of data to be made available to the rendered view (i.e. the Element)
 * @param array $options Array of options. Possible keys are:
 * - `cache` - Can either be `true`, to enable caching using the config in View::$elementCache. Or an array
 *   If an array, the following keys can be used:
 *   - `config` - Used to store the cached element in a custom cache configuration.
 *   - `key` - Used to define the key used in the Cache::write().  It will be prefixed with `element_`
 * - `plugin` - Load an element from a specific plugin.
 * - `callbacks` - Set to true to fire beforeRender and afterRender helper callbacks for this element.
 *   Defaults to false.
 * @return string Rendered Element
 */
	public function element($name, $data = array(), $options = array()) {
		$file = $plugin = $key = null;
		$callbacks = false;

		if (isset($options['plugin'])) {
			$plugin = Inflector::camelize($options['plugin']);
		}
		if (isset($this->plugin) && !$plugin) {
			$plugin = $this->plugin;
		}
		if (isset($options['callbacks'])) {
			$callbacks = $options['callbacks'];
		}

		if (isset($options['cache'])) {
			$underscored = null;
			if ($plugin) {
				$underscored = Inflector::underscore($plugin);
			}
			$keys = array_merge(array($underscored, $name), array_keys($options), array_keys($data));
			$caching = array(
				'config' => $this->elementCache,
				'key' => implode('_', $keys)
			);
			if (is_array($options['cache'])) {
				$defaults = array(
					'config' => $this->elementCache,
					'key' => $caching['key']
				);
				$caching = array_merge($defaults, $options['cache']);
			}
			$key = 'element_' . $caching['key'];
			$contents = Cache::read($key, $caching['config']);
			if ($contents !== false) {
				return $contents;
			}
		}

		$file = $this->_getElementFilename($name, $plugin);

		if ($file) {
			if (!$this->_helpersLoaded) {
				$this->loadHelpers();
			}
			if ($callbacks) {
				$this->Helpers->trigger('beforeRender', array($file));
			}
			$element = $this->_render($file, array_merge($this->viewVars, $data));
			if ($callbacks) {
				$this->Helpers->trigger('afterRender', array($file, $element));
			}
			if (isset($options['cache'])) {
				Cache::write($key, $element, $caching['config']);
			}
			return $element;
		}
		$file = 'Elements' . DS . $name . $this->ext;

		if (Configure::read('debug') > 0) {
			return "Element Not Found: " . $file;
		}
	}

/**
 * Renders view for given view file and layout.
 *
 * Render triggers helper callbacks, which are fired before and after the view are rendered,
 * as well as before and after the layout.  The helper callbacks are called:
 *
 * - `beforeRender`
 * - `afterRender`
 * - `beforeLayout`
 * - `afterLayout`
 *
 * If View::$autoRender is false and no `$layout` is provided, the view will be returned bare.
 *
 * @param string $view Name of view file to use
 * @param string $layout Layout to use.
 * @return string Rendered Element
 * @throws CakeException if there is an error in the view.
 */
	public function render($view = null, $layout = null) {
		if ($this->hasRendered) {
			return true;
		}
		if (!$this->_helpersLoaded) {
			$this->loadHelpers();
		}
		$this->output = null;

		if ($view !== false && $viewFileName = $this->_getViewFileName($view)) {
			$this->Helpers->trigger('beforeRender', array($viewFileName));
			$this->output = $this->_render($viewFileName);
			$this->Helpers->trigger('afterRender', array($viewFileName));
		}

		if ($layout === null) {
			$layout = $this->layout;
		}
		if ($this->output === false) {
			throw new CakeException(__d('cake_dev', "Error in view %s, got no content.", $viewFileName));
		}
		if ($layout && $this->autoLayout) {
			$this->output = $this->renderLayout($this->output, $layout);
		}
		$this->hasRendered = true;
		return $this->output;
	}

/**
 * Renders a layout. Returns output from _render(). Returns false on error.
 * Several variables are created for use in layout.
 *
 * - `title_for_layout` - A backwards compatible place holder, you should set this value if you want more control.
 * - `content_for_layout` - contains rendered view file
 * - `scripts_for_layout` - contains scripts added to header
 *
 * @param string $content_for_layout Content to render in a view, wrapped by the surrounding layout.
 * @param string $layout Layout name
 * @return mixed Rendered output, or false on error
 * @throws CakeException if there is an error in the view.
 */
	public function renderLayout($content_for_layout, $layout = null) {
		$layoutFileName = $this->_getLayoutFileName($layout);
		if (empty($layoutFileName)) {
			return $this->output;
		}
		if (!$this->_helpersLoaded) {
			$this->loadHelpers();
		}
		$this->Helpers->trigger('beforeLayout', array($layoutFileName));

		$this->viewVars = array_merge($this->viewVars, array(
			'content_for_layout' => $content_for_layout,
			'scripts_for_layout' => implode("\n\t", $this->_scripts),
		));

		if (!isset($this->viewVars['title_for_layout'])) {
			$this->viewVars['title_for_layout'] = Inflector::humanize($this->viewPath);
		}

		$this->output = $this->_render($layoutFileName);

		if ($this->output === false) {
			throw new CakeException(__d('cake_dev', "Error in layout %s, got no content.", $layoutFileName));
		}

		$this->Helpers->trigger('afterLayout', array($layoutFileName));
		return $this->output;
	}

/**
 * Render cached view. Works in concert with CacheHelper and Dispatcher to
 * render cached view files.
 *
 * @param string $filename the cache file to include
 * @param string $timeStart the page render start time
 * @return boolean Success of rendering the cached file.
 */
	public function renderCache($filename, $timeStart) {
		ob_start();
		include ($filename);

		if (Configure::read('debug') > 0 && $this->layout != 'xml') {
			echo "<!-- Cached Render Time: " . round(microtime(true) - $timeStart, 4) . "s -->";
		}
		$out = ob_get_clean();

		if (preg_match('/^<!--cachetime:(\\d+)-->/', $out, $match)) {
			if (time() >= $match['1']) {
				@unlink($filename);
				unset ($out);
				return false;
			} else {
				if ($this->layout === 'xml') {
					header('Content-type: text/xml');
				}
				$commentLength = strlen('<!--cachetime:' . $match['1'] . '-->');
				echo substr($out, $commentLength);
				return true;
			}
		}
	}

/**
 * Returns a list of variables available in the current View context
 *
 * @return array Array of the set view variable names.
 */
	public function getVars() {
		return array_keys($this->viewVars);
	}

/**
 * Returns the contents of the given View variable(s)
 *
 * @param string $var The view var you want the contents of.
 * @return mixed The content of the named var if its set, otherwise null.
 */
	public function getVar($var) {
		if (!isset($this->viewVars[$var])) {
			return null;
		} else {
			return $this->viewVars[$var];
		}
	}

/**
 * Adds a script block or other element to be inserted in $scripts_for_layout in
 * the `<head />` of a document layout
 *
 * @param string $name Either the key name for the script, or the script content. Name can be used to
 *   update/replace a script element.
 * @param string $content The content of the script being added, optional.
 * @return void
 */
	public function addScript($name, $content = null) {
		if (empty($content)) {
			if (!in_array($name, array_values($this->_scripts))) {
				$this->_scripts[] = $name;
			}
		} else {
			$this->_scripts[$name] = $content;
		}
	}

/**
 * Generates a unique, non-random DOM ID for an object, based on the object type and the target URL.
 *
 * @param string $object Type of object, i.e. 'form' or 'link'
 * @param string $url The object's target URL
 * @return string
 */
	public function uuid($object, $url) {
		$c = 1;
		$url = Router::url($url);
		$hash = $object . substr(md5($object . $url), 0, 10);
		while (in_array($hash, $this->uuids)) {
			$hash = $object . substr(md5($object . $url . $c), 0, 10);
			$c++;
		}
		$this->uuids[] = $hash;
		return $hash;
	}

/**
 * Allows a template or element to set a variable that will be available in
 * a layout or other element. Analogous to Controller::set().
 *
 * @param mixed $one A string or an array of data.
 * @param mixed $two Value in case $one is a string (which then works as the key).
 *    Unused if $one is an associative array, otherwise serves as the values to $one's keys.
 * @return void
 */
	public function set($one, $two = null) {
		$data = null;
		if (is_array($one)) {
			if (is_array($two)) {
				$data = array_combine($one, $two);
			} else {
				$data = $one;
			}
		} else {
			$data = array($one => $two);
		}
		if ($data == null) {
			return false;
		}
		$this->viewVars = $data + $this->viewVars;
	}

/**
 * Magic accessor for helpers. Provides access to attributes that were deprecated.
 *
 * @param string $name Name of the attribute to get.
 * @return mixed
 */
	public function __get($name) {
		if (isset($this->Helpers->{$name})) {
			return $this->Helpers->{$name};
		}
		switch ($name) {
			case 'base':
			case 'here':
			case 'webroot':
			case 'data':
				return $this->request->{$name};
			case 'action':
				return isset($this->request->params['action']) ? $this->request->params['action'] : '';
			case 'params':
				return $this->request;
		}
		return null;
	}

/**
 * Interact with the HelperCollection to load all the helpers.
 *
 * @return void
 */
	public function loadHelpers() {
		$helpers = HelperCollection::normalizeObjectArray($this->helpers);
		foreach ($helpers as $name => $properties) {
			list($plugin, $class) = pluginSplit($properties['class']);
			$this->{$class} = $this->Helpers->load($properties['class'], $properties['settings']);
		}
		$this->_helpersLoaded = true;
	}

/**
 * Renders and returns output for given view filename with its
 * array of data.
 *
 * @param string $___viewFn Filename of the view
 * @param array $___dataForView Data to include in rendered view. If empty the current View::$viewVars will be used.
 * @return string Rendered output
 */
	protected function _render($___viewFn, $___dataForView = array()) {
		if (empty($___dataForView)) {
			$___dataForView = $this->viewVars;
		}

		extract($___dataForView, EXTR_SKIP);
		ob_start();

		include $___viewFn;

		return ob_get_clean();
	}

/**
 * Loads a helper.  Delegates to the `HelperCollection::load()` to load the helper
 *
 * @param string $helperName Name of the helper to load.
 * @param array $settings Settings for the helper
 * @return Helper a constructed helper object.
 * @see HelperCollection::load()
 */
	public function loadHelper($helperName, $settings = array()) {
		return $this->Helpers->load($helperName, $settings);
	}

/**
 * Returns filename of given action's template file (.ctp) as a string.
 * CamelCased action names will be under_scored! This means that you can have
 * LongActionNames that refer to long_action_names.ctp views.
 *
 * @param string $name Controller action to find template filename for
 * @return string Template filename
 * @throws MissingViewException when a view file could not be found.
 */
	protected function _getViewFileName($name = null) {
		$subDir = null;

		if (!is_null($this->subDir)) {
			$subDir = $this->subDir . DS;
		}

		if ($name === null) {
			$name = $this->view;
		}
		$name = str_replace('/', DS, $name);

		if (strpos($name, DS) === false && $name[0] !== '.') {
			$name = $this->viewPath . DS . $subDir . Inflector::underscore($name);
		} elseif (strpos($name, DS) !== false) {
			if ($name[0] === DS || $name[1] === ':') {
				if (is_file($name)) {
					return $name;
				}
				$name = trim($name, DS);
			} else if ($name[0] === '.') {
				$name = substr($name, 3);
			} else {
				$name = $this->viewPath . DS . $subDir . $name;
			}
		}
		$paths = $this->_paths($this->plugin);

		$exts = $this->_getExtensions();
		foreach ($exts as $ext) {
			foreach ($paths as $path) {
				if (file_exists($path . $name . $ext)) {
					return $path . $name . $ext;
				}
			}
		}
		$defaultPath = $paths[0];

		if ($this->plugin) {
			$pluginPaths = App::path('plugins');
			foreach ($paths as $path) {
				if (strpos($path, $pluginPaths[0]) === 0) {
					$defaultPath = $path;
					break;
				}
			}
		}
		throw new MissingViewException(array('file' => $defaultPath . $name . $this->ext));
	}

/**
 * Returns layout filename for this template as a string.
 *
 * @param string $name The name of the layout to find.
 * @return string Filename for layout file (.ctp).
 * @throws MissingLayoutException when a layout cannot be located
 */
	protected function _getLayoutFileName($name = null) {
		if ($name === null) {
			$name = $this->layout;
		}
		$subDir = null;

		if (!is_null($this->layoutPath)) {
			$subDir = $this->layoutPath . DS;
		}
		$paths = $this->_paths($this->plugin);
		$file = 'Layouts' . DS . $subDir . $name;

		$exts = $this->_getExtensions();
		foreach ($exts as $ext) {
			foreach ($paths as $path) {
				if (file_exists($path . $file . $ext)) {
					return $path . $file . $ext;
				}
			}
		}
		throw new MissingLayoutException(array('file' => $paths[0] . $file . $this->ext));
	}


/**
 * Get the extensions that view files can use.
 *
 * @return array Array of extensions view files use.
 */
	protected function _getExtensions() {
		$exts = array($this->ext);
		if ($this->ext !== '.ctp') {
			array_push($exts, '.ctp');
		}
		return $exts;
	}

/**
 * Finds an element filename, returns false on failure.
 *
 * @param string $name The name of the element to find.
 * @param string $plugin The plugin name the element is in.
 * @return mixed Either a string to the element filename or false when one can't be found.
 */
	protected function _getElementFileName($name, $plugin = null) {
		$paths = $this->_paths($plugin);
		$exts = $this->_getExtensions();
		foreach ($exts as $ext) {
			foreach ($paths as $path) {
				if (file_exists($path . 'Elements' . DS . $name . $ext)) {
					return $path . 'Elements' . DS . $name . $ext;
				}
			}
		}
		return false;
	}

/**
 * Return all possible paths to find view files in order
 *
 * @param string $plugin Optional plugin name to scan for view files.
 * @param boolean $cached Set to true to force a refresh of view paths.
 * @return array paths
 */
	protected function _paths($plugin = null, $cached = true) {
		if ($plugin === null && $cached === true && !empty($this->_paths)) {
			return $this->_paths;
		}
		$paths = array();
		$viewPaths = App::path('View');
		$corePaths = array_flip(App::core('View'));
		if (!empty($plugin)) {
			$count = count($viewPaths);
			for ($i = 0; $i < $count; $i++) {
				if (!isset($corePaths[$viewPaths[$i]])) {
					$paths[] = $viewPaths[$i] . 'Plugin' . DS . $plugin . DS;
				}
			}
			$paths = array_merge($paths, App::path('View', $plugin));
		}

		$this->_paths = array_unique(array_merge($paths, $viewPaths, array_keys($corePaths)));
		return $this->_paths;
	}
}
