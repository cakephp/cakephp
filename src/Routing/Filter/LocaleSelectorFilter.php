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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Routing\Filter;

use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\Routing\DispatcherFilter;
use Locale;

/**
 * Sets the runtime default locale for the request based on the
 * Accept-Language header. The default will only be set if it
 * matches the list of passed valid locales.
 */
class LocaleSelectorFilter extends DispatcherFilter
{

    /**
     * List of valid locales for the request
     *
     * @var array
     */
    protected $_locales = [];

    /**
     * Constructor.
     *
     * @param array $config Settings for the filter.
     * @throws \Cake\Core\Exception\Exception When 'when' conditions are not callable.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        if (!empty($config['locales'])) {
            $this->_locales = $config['locales'];
        }
    }

    /**
     * Inspects the request for the Accept-Language header and sets the
     * Locale for the current runtime if it matches the list of valid locales
     * as passed in the configuration.
     *
     * @param \Cake\Event\Event $event The event instance.
     * @return void
     */
    public function beforeDispatch(Event $event)
    {
        /* @var \Cake\Http\ServerRequest $request */
        $request = $event->getData('request');
        $locale = Locale::acceptFromHttp($request->getHeaderLine('Accept-Language'));

        if (!$locale || (!empty($this->_locales) && !in_array($locale, $this->_locales))) {
            return;
        }

        I18n::locale($locale);
    }
}
