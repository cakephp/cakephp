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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component;

use Cake\Controller\Component;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\Utility\CookieCryptTrait;
use Cake\Utility\Hash;
use Cake\Utility\Security;

/**
 * Cookie Component.
 *
 * Provides enhanced cookie handling features for use in the controller layer.
 * In addition to the basic features offered be Cake\Http\Response, this class lets you:
 *
 * - Create and read encrypted cookies.
 * - Store non-scalar data.
 * - Use hash compatible syntax to read/write/delete values.
 *
 * @link https://book.cakephp.org/3.0/en/controllers/components/cookie.html
 * @deprecated 3.5.0 Use Cake\Http\Middleware\EncryptedCookieMiddleware and Cake\Http\Cookie\Cookie methods instead.
 */
class CookieComponent extends Component
{
    use CookieCryptTrait;

    /**
     * Default config
     *
     * - `expires` - How long the cookies should last for. Defaults to 1 month.
     * - `path` - The path on the server in which the cookie will be available on.
     *   If path is set to '/foo/', the cookie will only be available within the
     *   /foo/ directory and all sub-directories such as /foo/bar/ of domain.
     *   The default value is base path of app. For e.g. if your app is running
     *   under a subfolder "cakeapp" of document root the path would be "/cakeapp/"
     *   else it would be "/".
     * - `domain` - The domain that the cookie is available. To make the cookie
     *   available on all subdomains of example.com set domain to '.example.com'.
     * - `secure` - Indicates that the cookie should only be transmitted over a
     *   secure HTTPS connection. When set to true, the cookie will only be set if
     *   a secure connection exists.
     * - `key` - Encryption key used when encrypted cookies are enabled. Defaults to Security.salt.
     * - `httpOnly` - Set to true to make HTTP only cookies. Cookies that are HTTP only
     *   are not accessible in JavaScript. Default false.
     * - `encryption` - Type of encryption to use. Defaults to 'aes'.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'path' => null,
        'domain' => '',
        'secure' => false,
        'key' => null,
        'httpOnly' => false,
        'encryption' => 'aes',
        'expires' => '+1 month',
    ];

    /**
     * Config specific to a given top level key name.
     *
     * The values in this array are merged with the general config
     * to generate the configuration for a given top level cookie name.
     *
     * @var array
     */
    protected $_keyConfig = [];

    /**
     * Values stored in the cookie.
     *
     * Accessed in the controller using $this->Cookie->read('Name.key');
     *
     * @var array
     */
    protected $_values = [];

    /**
     * A map of keys that have been loaded.
     *
     * Since CookieComponent lazily reads cookie data,
     * we need to track which cookies have been read to account for
     * read, delete, read patterns.
     *
     * @var array
     */
    protected $_loaded = [];

    /**
     * A reference to the Controller's Cake\Http\Response object.
     * Currently unused.
     *
     * @var \Cake\Http\Response|null
     * @deprecated 3.4.0 Will be removed in 4.0.0
     */
    protected $_response;

    /**
     * Initialize config data and properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        if (!$this->_config['key']) {
            $this->setConfig('key', Security::getSalt());
        }

        $controller = $this->_registry->getController();

        if ($controller === null) {
            $this->request = ServerRequest::createFromGlobals();
        }

        if (empty($this->_config['path'])) {
            $this->setConfig('path', $this->request->webroot);
        }
    }

    /**
     * Set the configuration for a specific top level key.
     *
     * ### Examples:
     *
     * Set a single config option for a key:
     *
     * ```
     * $this->Cookie->configKey('User', 'expires', '+3 months');
     * ```
     *
     * Set multiple options:
     *
     * ```
     * $this->Cookie->configKey('User', [
     *   'expires', '+3 months',
     *   'httpOnly' => true,
     * ]);
     * ```
     *
     * @param string $keyname The top level keyname to configure.
     * @param null|string|array $option Either the option name to set, or an array of options to set,
     *   or null to read config options for a given key.
     * @param string|null $value Either the value to set, or empty when $option is an array.
     * @return array|null
     */
    public function configKey($keyname, $option = null, $value = null)
    {
        if ($option === null) {
            $default = $this->_config;
            $local = isset($this->_keyConfig[$keyname]) ? $this->_keyConfig[$keyname] : [];

            return $local + $default;
        }
        if (!is_array($option)) {
            $option = [$option => $value];
        }
        $this->_keyConfig[$keyname] = $option;

        return null;
    }

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }

    /**
     * Write a value to the response cookies.
     *
     * You must use this method before any output is sent to the browser.
     * Failure to do so will result in header already sent errors.
     *
     * @param string|array $key Key for the value
     * @param mixed $value Value
     * @return void
     */
    public function write($key, $value = null)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        $keys = [];
        foreach ($key as $name => $value) {
            $this->_load($name);

            $this->_values = Hash::insert($this->_values, $name, $value);
            $parts = explode('.', $name);
            $keys[] = $parts[0];
        }

        foreach ($keys as $name) {
            $this->_write($name, $this->_values[$name]);
        }
    }

    /**
     * Read the value of key path from request cookies.
     *
     * This method will also allow you to read cookies that have been written in this
     * request, but not yet sent to the client.
     *
     * @param string|null $key Key of the value to be obtained.
     * @return string or null, value for specified key
     */
    public function read($key = null)
    {
        $this->_load($key);

        return Hash::get($this->_values, $key);
    }

    /**
     * Load the cookie data from the request and response objects.
     *
     * Based on the configuration data, cookies will be decrypted. When cookies
     * contain array data, that data will be expanded.
     *
     * @param string|array $key The key to load.
     * @return void
     */
    protected function _load($key)
    {
        $parts = explode('.', $key);
        $first = array_shift($parts);
        if (isset($this->_loaded[$first])) {
            return;
        }
        if (!isset($this->request->cookies[$first])) {
            return;
        }
        $cookie = $this->request->cookies[$first];
        $config = $this->configKey($first);
        $this->_loaded[$first] = true;
        $this->_values[$first] = $this->_decrypt($cookie, $config['encryption'], $config['key']);
    }

    /**
     * Returns true if given key is set in the cookie.
     *
     * @param string|null $key Key to check for
     * @return bool True if the key exists
     */
    public function check($key = null)
    {
        if (empty($key)) {
            return false;
        }

        return $this->read($key) !== null;
    }

    /**
     * Delete a cookie value
     *
     * You must use this method before any output is sent to the browser.
     * Failure to do so will result in header already sent errors.
     *
     * Deleting a top level key will delete all keys nested within that key.
     * For example deleting the `User` key, will also delete `User.email`.
     *
     * @param string $key Key of the value to be deleted
     * @return void
     */
    public function delete($key)
    {
        $this->_load($key);

        $this->_values = Hash::remove($this->_values, $key);
        $parts = explode('.', $key);
        $top = $parts[0];

        if (isset($this->_values[$top])) {
            $this->_write($top, $this->_values[$top]);
        } else {
            $this->_delete($top);
        }
    }

    /**
     * Set cookie
     *
     * @param string $name Name for cookie
     * @param string $value Value for cookie
     * @return void
     */
    protected function _write($name, $value)
    {
        $config = $this->configKey($name);
        $expires = new Time($config['expires']);

        $response = $this->getController()->response;
        $response->cookie([
            'name' => $name,
            'value' => $this->_encrypt($value, $config['encryption'], $config['key']),
            'expire' => $expires->format('U'),
            'path' => $config['path'],
            'domain' => $config['domain'],
            'secure' => $config['secure'],
            'httpOnly' => $config['httpOnly']
        ]);
    }

    /**
     * Sets a cookie expire time to remove cookie value.
     *
     * This is only done once all values in a cookie key have been
     * removed with delete.
     *
     * @param string $name Name of cookie
     * @return void
     */
    protected function _delete($name)
    {
        $config = $this->configKey($name);
        $expires = new Time('now');

        $response = $this->getController()->response;
        $response->cookie([
            'name' => $name,
            'value' => '',
            'expire' => $expires->format('U') - 42000,
            'path' => $config['path'],
            'domain' => $config['domain'],
            'secure' => $config['secure'],
            'httpOnly' => $config['httpOnly']
        ]);
    }

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    protected function _getCookieEncryptionKey()
    {
        return $this->_config['key'];
    }
}
