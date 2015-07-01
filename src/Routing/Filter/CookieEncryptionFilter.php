<?php

namespace Cake\Routing\Filter;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;

class CookieEncryptionFilter extends DispatcherFilter
{

    const CONFIG_KEY = 'Cookie';

    /**
     * Cookies should be decrypted after jars have been set.
     *
     * @var int
     */
    protected $_priority = 25;

    /**
     *
     * @var string
     */
    protected $_cookieEncrypterClassName = 'Cake\Network\Cookie\CookieEncrypter';

    /**
     *
     * @var \Cake\Network\Cookie\CookieEncrypter
     */
    protected $_encrypter;

    /**
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];
        $this->_decryptCookies($request);
    }

    /**
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function afterDispatch(Event $event)
    {
        $response = $event->data['response'];
        $this->_encryptCookies($response);
    }

    /**
     *
     * @param \Cake\Network\Request $request
     * @return void
     */
    protected function _decryptCookies($request)
    {
        $encrypter = $this->encrypter();
        foreach ($request->cookies as $name => $value) {
            $request->cookies[$name] = $encrypter->decrypt($name, $value);
        }
    }

    /**
     *
     * @param \Cake\Network\Response $response
     * @return void
     */
    protected function _encryptCookies($response)
    {
        $encrypter = $this->encrypter();
        foreach ($response->cookies as $cookie) {
            $value = $encrypter->encrypt($cookie->name(), $cookie->read());
            $cookie->write($value);
        }
    }

    /**
     *
     * @param \Cake\Network\Cookie\CookieEncrypter $encrypter
     * @return \Cake\Network\Cookie\CookieEncrypter
     */
    public function encrypter($encrypter = null)
    {
        if ($encrypter !== null) {
            $this->_encrypter = $encrypter;
        }

        if ($this->_encrypter === null) {
            $config = Configure::read(static::CONFIG_KEY);
            $this->_encrypter = new $this->_cookieEncrypterClassName((array) $config);
        }

        return $this->_encrypter;
    }
}
