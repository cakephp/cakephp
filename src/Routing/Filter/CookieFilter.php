<?php

namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;

class CookieFilter extends DispatcherFilter
{
    
    /**
     *
     * @var int
     */
    protected $_priority = 20;

    /**
     *
     * @var string
     */
    protected $_requestJarClassName = 'Cake\Network\Cookie\RequestCookieJar';

    /**
     *
     * @var string
     */
    protected $_responseJarClassName = 'Cake\Network\Cookie\ResponseCookieJar';

    /**
     *
     * @param \Cake\Event\Event $event
     * @return void
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];
        $response = $event->data['response'];

        $this->_setRequestCookieJar($request);
        $this->_setResponseCookieJar($response);
    }

    /**
     *
     * @param \Cake\Network\Request $request
     * @return void
     */
    protected function _setRequestCookieJar($request)
    {
        $requestJar = new $this->_requestJarClassName($request->cookies);
        $request->cookies = $requestJar;
    }

    /**
     *
     * @param \Cake\Network\Response $response
     * @return void
     */
    protected function _setResponseCookieJar($response)
    {
        $responseJar = new $this->_responseJarClassName();
        $response->cookies = $responseJar;
    }
}
