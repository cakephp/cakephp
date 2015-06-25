<?php

namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;

class CookieFilter extends DispatcherFilter
{

    public $_cookieEncrypterClass = 'Cake\Network\Cookie\CookieEncrypter';
    public $_requestJarClass = 'Cake\Network\Cookie\RequestCookieJar';
    public $_responseJarClass = 'Cake\Network\Cookie\ResponseCookieJar';

    /**
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];
        $response = $event->data['response'];

        $encrypter = new $this->_cookieEncrypterClass;

        $cookies = $request->getCookieParams(); //PSR-7 cookies "accessor" or anything else returning request cookies
        $requestJar = new $this->_requestJarClass($cookies, $encrypter);
        $request->cookies = $requestJar;

        $responseJar = new $this->_responseJarClass([], $encrypter);
        $response->cookies = $responseJar;
    }

    /**
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function afterDispatch(Event $event)
    {
        $response = $event->data['response'];

        $cookies = $response->cookies->raw(); //returns encrypted cookies

        $response->setCookies($cookies); //some wrapper for setcookie() or rather PSR-7 withHeader()
    }
}
