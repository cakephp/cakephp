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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use Cake\Core\InstanceConfigTrait;

/**
 * Abstract transport for sending email
 *
 */
abstract class AbstractTransport
{

    use InstanceConfigTrait;

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Email instance.
     * @return array
     */
    abstract public function send(Email $email);

    /**
     * Constructor
     *
     * @param array $config Configuration options.
     */
    public function __construct($config = [])
    {
        $this->config($config);
    }

    /**
     * Help to convert headers in string
     *
     * @param array $headers Headers in format key => value
     * @param string $eol End of line string.
     * @return string
     */
    protected function _headersToString($headers, $eol = "\r\n")
    {
        $out = '';
        foreach ($headers as $key => $value) {
            if ($value === false || $value === null || $value === '') {
                continue;
            }
            $out .= $key . ': ' . $value . $eol;
        }
        if (!empty($out)) {
            $out = substr($out, 0, -1 * strlen($eol));
        }
        return $out;
    }
}
