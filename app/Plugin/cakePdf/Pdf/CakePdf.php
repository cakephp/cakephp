<?php
App::Uses('CakeRequest', 'Network');
App::uses('Controller', 'Controller');
App::uses('File', 'Utility');
App::uses('View', 'View');

class CakePdf {

/**
 * Layout for the View
 *
 * @var string
 */
	protected $_layout = 'default';

/**
 * Template for the view
 *
 * @var string
 */
	protected $_template = null;

/**
 * View for render
 *
 * @var string
 */
	protected $_viewRender = 'View';

/**
 * Vars to sent to render
 *
 * @var array
 */
	protected $_viewVars = array();

/**
 * Theme for the View
 *
 * @var array
 */
	protected $_theme = null;

/**
 * Helpers to be used in the render
 *
 * @var array
 */
	protected $_helpers = array('Html');

/**
 * Instance of PdfEngine class
 *
 * @var AbstractPdfEngine
 */
	protected $_engineClass = null;

/**
 * Instance of PdfCrypto class
 *
 * @var AbstractPdfCrypto
 */
	protected $_cryptoClass = null;

/**
 * Html to be rendered
 *
 * @var string
 */
	protected $_html = null;

/**
 * Page size of the pdf
 *
 * @var string
 */
	protected $_pageSize = 'A4';

/**
 * Orientation of the pdf
 *
 * @var string
 */
	protected $_orientation = 'portrait';

/**
 * Encoding
 *
 * @var string
 */
	protected $_encoding = 'UTF-8';

/**
 * Bottom margin in mm
 *
 * @var number
 */
	protected $_marginBottom = null;

/**
 * Left margin in mm
 *
 * @var number
 */
	protected $_marginLeft = null;

/**
 * Right margin in mm
 *
 * @var number
 */
	protected $_marginRight = null;

/**
 * Top margin in mm
 *
 * @var number
 */
	protected $_marginTop = null;

/**
 * Title of the document
 *
 * @var string
 */
	protected $_title = null;

/**
 * Flag that tells if we need to pass it through crypto
 *
 * @var boolean
 */
	protected $_protect = false;

/**
 * User password, used with crypto
 *
 * @var boolean
 */
	protected $_userPassword = null;

/**
 * Owner password, used with crypto
 *
 * @var boolean
 */
	protected $_ownerPassword = null;

/**
 * Cache config name, if set to false cache is disabled
 *
 * @var string|boolean
 */
	protected $_cache = false;

/**
 * Permissions that are allowed, used with crypto
 *
 * false: none
 * true: all
 * array: List of permissions that are allowed
 *
 * @var mixed
 */
	protected $_allow = false;


/**
 * Available permissions
 *
 * @var array
 */
	private $__availablePermissions = array(
		'print',
		'degraded_print',
		'modify',
		'assembly',
		'copy_contents',
		'screen_readers',
		'annotate',
		'fill_in'
	);

/**
 * Constructor
 *
 * @param array $config Pdf configs to use
 */
	public function __construct($config = array()) {
		$config = array_merge(array(
			'engine' => Configure::read('CakePdf.engine'),
			'crypto' => Configure::read('CakePdf.crypto')
		), $config);
		if ($config['engine']) {
			$this->engine($config['engine'])->config($config);
		}

		if ($config['crypto']) {
			$this->crypto($config['crypto'])->config($config);
		}

		$options = array('pageSize', 'orientation', 'margin', 'title', 'encoding', 'protect', 'userPassword', 'ownerPassword', 'permissions', 'cache');
		foreach ($options as $option) {
			if (isset($config[$option])) {
				$this->{$option}($config[$option]);
			}
		}
	}

/**
 * Create pdf content from html. Can be used to write to file or with PdfView to display
 *
 * @param mixed $html Html content to render. If left empty, the template will be rendered with viewVars and layout that have been set.
 * @return string
 */
	public function output($html = null) {
		$Engine = $this->engine();
		if (!$Engine) {
			throw new CakeException(__d('cake_pdf', 'Engine is not loaded'));
		}

		if (!$html) {
			$html = $this->_render();
		}
		$this->html($html);

		$cache = $this->cache();
		if ($cache) {
			$cacheKey = md5(serialize($this));
			$cached = Cache::read($cacheKey, $cache);
			if ($cached) {
				return $cached;
			}
		}

		$output = $Engine->output();

		if ($this->protect()) {
			$output = $this->crypto()->encrypt($output);
		}

		if ($cache) {
			Cache::write($cacheKey, $output, $cache);
		}

		return $output;
	}

/**
 * Get/Set Html.
 *
 * @param null|string $html
 * @return mixed
 */
	public function html($html = null) {
		if ($html === null) {
			return $this->_html;
		}
		$this->_html = $html;
		return $this;
	}

/**
 * Writes output to file
 *
 * @param srting $destination Absolute file path to write to
 * @param boolean $create Create file if it does not exist (if true)
 * @return boolean
 */
	public function write($destination, $create = true, $html = null) {
		$output = $this->output($html);
		$File = new File($destination, $create);
		return $File->write($output) && $File->close();
	}

/**
 * Load PdfEngine
 *
 * @param string $name Classname of pdf engine without `Engine` suffix. For example `CakePdf.DomPdf`
 * @return object PdfEngine
 */
	public function engine($name = null) {
		if (!$name) {
			return $this->_engineClass;
		}

		list($pluginDot, $engineClassName) = pluginSplit($name, true);
		$engineClassName = $engineClassName . 'Engine';
		App::uses($engineClassName, $pluginDot . 'Pdf/Engine');
		if (!class_exists($engineClassName)) {
			throw new CakeException(__d('cake_pdf', 'Pdf engine "%s" not found', $name));
		}
		if (!is_subclass_of($engineClassName, 'AbstractPdfEngine')) {
			throw new CakeException(__d('cake_pdf', 'Pdf engines must extend "AbstractPdfEngine"'));
		}
		$this->_engineClass = new $engineClassName($this);
		return $this->_engineClass;
	}

/**
 * Load PdfCrypto
 *
 * @param string $name Classname of crypto engine without `Crypto` suffix. For example `CakePdf.Pdftk`
 * @return object PdfCrypto
 */
	public function crypto($name = null) {
		if ($name === null) {
			if ($this->_cryptoClass) {
				return $this->_cryptoClass;
			}
			throw new CakeException(__d('cake_pdf', 'Crypto is not loaded'));
		}

		list($pluginDot, $engineClassName) = pluginSplit($name, true);
		$engineClassName = $engineClassName . 'Crypto';
		App::uses($engineClassName, $pluginDot . 'Pdf/Crypto');
		if (!class_exists($engineClassName)) {
			throw new CakeException(__d('cake_pdf', 'Pdf crypto "%s" not found', $name));
		}
		if (!is_subclass_of($engineClassName, 'AbstractPdfCrypto')) {
			throw new CakeException(__d('cake_pdf', 'Crypto engine must extend "AbstractPdfCrypto"'));
		}
		$this->_cryptoClass = new $engineClassName($this);
		return $this->_cryptoClass;
	}

/**
 * Get/Set Page size.
 *
 * @param null|string $pageSize
 * @return mixed
 */
	public function pageSize($pageSize = null) {
		if ($pageSize === null) {
			return $this->_pageSize;
		}
		$this->_pageSize = $pageSize;
		return $this;
	}

/**
 * Get/Set Orientation.
 *
 * @param null|string $orientation
 * @return mixed
 */
	public function orientation($orientation = null) {
		if ($orientation === null) {
			return $this->_orientation;
		}
		$this->_orientation = $orientation;
		return $this;
	}

/**
 * Get/Set Encoding.
 *
 * @param null|string $encoding
 * @return mixed
 */
	public function encoding($encoding = null) {
		if ($encoding === null) {
			return $this->_encoding;
		}
		$this->_encoding = $encoding;
		return $this;
	}

/**
 * Get/Set page margins.
 *
 * Several options are available
 *
 * Array format
 * ------------
 * First param can be an array with the following options:
 * - bottom
 * - left
 * - right
 * - top
 *
 * Set margin for all borders
 * --------------------------
 * $bottom is set to a string
 * Leave all other parameters empty
 *
 * Set margin for horizontal and vertical
 * --------------------------------------
 * $bottom value will be set to bottom and top
 * $left value will be set to left and right
 *
 * @param null|string|array $bottom
 * @param null|string $left
 * @param null|string $right
 * @param null|string $top
 * @return mixed
 */
	public function margin($bottom = null, $left = null, $right = null, $top = null) {
		if ($bottom === null) {
			return array(
				'bottom' => $this->_marginBottom,
				'left' => $this->_marginLeft,
				'right' => $this->_marginRight,
				'top' => $this->_marginTop
			);
		}

		if (is_array($bottom)) {
			extract($bottom, EXTR_IF_EXISTS);
		}

		if ($bottom && $left === null && $right === null && $top === null) {
			$left = $right = $top = $bottom;
		}

		if ($bottom && $top === null) {
			$top = $bottom;
		}

		if ($left && $right === null) {
			$right = $left;
		}

		$this->marginBottom($bottom);
		$this->marginLeft($left);
		$this->marginRight($right);
		$this->marginTop($top);

		return $this;
	}

/**
 * Get/Set bottom margin.
 *
 * @param null|string $margin
 * @return mixed
 */
	public function marginBottom($margin = null) {
		if ($margin === null) {
			return $this->_marginBottom;
		}
		$this->_marginBottom = $margin;
		return $this;
	}

/**
 * Get/Set left margin.
 *
 * @param null|string $margin
 * @return mixed
 */
	public function marginLeft($margin = null) {
		if ($margin === null) {
			return $this->_marginLeft;
		}
		$this->_marginLeft = $margin;
		return $this;
	}

/**
 * Get/Set right margin.
 *
 * @param null|string $margin
 * @return mixed
 */
	public function marginRight($margin = null) {
		if ($margin === null) {
			return $this->_marginRight;
		}
		$this->_marginRight = $margin;
		return $this;
	}

/**
 * Get/Set bottom margin.
 *
 * @param null|string $margin
 * @return mixed
 */
	public function marginTop($margin = null) {
		if ($margin === null) {
			return $this->_marginTop;
		}
		$this->_marginTop = $margin;
		return $this;
	}

/**
 * Get/Set document title.
 *
 * @param null|string $title
 * @return mixed
 */
	public function title($title = null) {
		if ($title === null) {
			return $this->_title;
		}
		$this->_title = $title;
		return $this;
	}

/**
 * Get/Set protection.
 *
 * @param null|boolean $protect
 * @return mixed
 */
	public function protect($protect = null) {
		if ($protect === null) {
			return $this->_protect;
		}
		$this->_protect = $protect;
		return $this;
	}

/**
 * Get/Set userPassword
 *
 * The user password is used to control who can open the PDF document.
 *
 * @param null|string $password
 * @return mixed
 */
	public function userPassword($password = null) {
		if ($password === null) {
			return $this->_userPassword;
		}
		$this->_userPassword = $password;

		return $this;
	}

/**
 * Get/Set ownerPassword.
 *
 * The owner password is used to control who can modify, print, manage the PDF document.
 *
 * @param null|string $password
 * @return mixed
 */
	public function ownerPassword($password = null) {
		if ($password === null) {
			return $this->_ownerPassword;
		}
		$this->_ownerPassword = $password;
		return $this;
	}

/**
 * Get/Set permissions.
 *
 * all: allow all permissions
 * none: allow no permissions
 * array: list of permissions that are allowed
 *

 * @param null|bool|array $permissions
 * @return mixed
 */
	public function permissions($permissions = null) {
		if (!$this->protect()) {
			return $this;
		}

		if ($permissions === null) {
			return $this->_allow;
		}

		if (is_string($permissions) && $permissions == 'all') {
			$permissions = true;
		}

		if (is_string($permissions) && $permissions == 'none') {
			$permissions = false;
		}

		if (is_array($permissions)) {
			foreach ($permissions as $permission) {
				if (!in_array($permission, $this->__availablePermissions)) {
					throw new CakeException(sprintf('Invalid permission: %s', $permission));
				}

				if (!$this->crypto()->permissionImplemented($permission)) {
					throw new CakeException(sprintf('Permission not implemented in crypto engine: %s', $permission));
				}
			}
		}

		$this->_allow = $permissions;

		return $this;
	}

/**
 * Get/Set caching.
 *
 * @param null|boolean|string $cache Cache config name to use, If true is passed, 'cake_pdf' will be used.
 * @return mixed
 */
	public function cache($cache = null) {
		if ($cache === null) {
			return $this->_cache;
		}

		if ($cache === false) {
			$this->_cache = false;
			return $this;
		}

		if ($cache === true) {
			$cache = 'cake_pdf';
		}

		if (!in_array($cache, Cache::configured())) {
			throw new CakeException(sprintf('CakePdf cache is not configured: %s', $cache));
		}

		$this->_cache = $cache;
		return $this;
	}
/**
 * Template and layout
 *
 * @param mixed $template Template name or null to not use
 * @param mixed $layout Layout name or null to not use
 * @return mixed
 */
	public function template($template = false, $layout = null) {
		if ($template === false) {
			return array(
				'template' => $this->_template,
				'layout' => $this->_layout
			);
		}
		$this->_template = $template;
		if ($layout !== null) {
			$this->_layout = $layout;
		}

		return $this;
	}

/**
 * View class for render
 *
 * @param string $viewClass
 * @return mixed
 */
	public function viewRender($viewClass = null) {
		if ($viewClass === null) {
			return $this->_viewRender;
		}
		$this->_viewRender = $viewClass;
		return $this;
	}

/**
 * Variables to be set on render
 *
 * @param array $viewVars
 * @return mixed
 */
	public function viewVars($viewVars = null) {
		if ($viewVars === null) {
			return $this->_viewVars;
		}
		$this->_viewVars = array_merge($this->_viewVars, (array)$viewVars);
		return $this;
	}

/**
 * Theme to use when rendering
 *
 * @param string $theme
 * @return mixed
 */
	public function theme($theme = null) {
		if ($theme === null) {
			return $this->_theme;
		}
		$this->_theme = $theme;
		return $this;
	}

/**
 * Helpers to be used in render
 *
 * @param array $helpers
 * @return mixed
 */
	public function helpers($helpers = null) {
		if ($helpers === null) {
			return $this->_helpers;
		}
		$this->_helpers = (array)$helpers;
		return $this;
	}

/**
 * Build and set all the view properties needed to render the layout and template.
 *
 * @return array The rendered template wrapped in layout.
 */
	protected function _render() {
		$viewClass = $this->viewRender();
		if ($viewClass !== 'View') {
			list($pluginDot, $viewClass) = pluginSplit($viewClass, true);
			$viewClass .= 'View';
			App::uses($viewClass, $pluginDot . 'View');
		}
		$Controller = new Controller(new CakeRequest());
		$View = new $viewClass($Controller);
		$View->viewVars = $this->_viewVars;
		$View->helpers = $this->_helpers;
		$View->theme = $this->_theme;
		$View->layoutPath = 'pdf';
		$View->viewPath = 'Pdf';
		$View->view = $this->_template;
		$View->layout = $this->_layout;
		return $View->render();
	}
}
