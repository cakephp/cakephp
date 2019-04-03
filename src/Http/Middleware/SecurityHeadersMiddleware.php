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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles common security headers in a convenient way
 */
class SecurityHeadersMiddleware
{

    /**
     * Security related headers to set
     *
     * @var array
     */
    protected $headers = [];

    /**
     * X-Content-Type-Options
     *
     * Sets the header value for it to 'nosniff'
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
     * @return $this
     */
    public function noSniff()
    {
        $this->headers['x-content-type-options'] = 'nosniff';

        return $this;
    }

    /**
     * X-Download-Options
     *
     * Sets the header value for it to 'noopen'
     *
     * @link https://msdn.microsoft.com/en-us/library/jj542450(v=vs.85).aspx
     * @return $this
     */
    public function noOpen()
    {
        $this->headers['x-download-options'] = 'noopen';

        return $this;
    }

    /**
     * Referrer-Policy
     *
     * @link https://w3c.github.io/webappsec-referrer-policy
     * @param string $policy Policy value. Available Value: 'no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin',
     *        'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url'
     * @return $this
     */
    public function setReferrerPolicy($policy = 'same-origin')
    {
        $available = [
            'no-referrer', 'no-referrer-when-downgrade', 'origin',
            'origin-when-cross-origin',
            'same-origin', 'strict-origin', 'strict-origin-when-cross-origin',
            'unsafe-url'
        ];

        $this->checkValues($policy, $available);
        $this->headers['referrer-policy'] = $policy;

        return $this;
    }

    /**
     * X-Frame-Options
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
     * @param string $option Option value. Available Values: 'deny', 'sameorigin', 'allow-from <uri>'
     * @param string $url URL if mode is `allow-from`
     * @return $this
     */
    public function setXFrameOptions($option = 'sameorigin', $url = null)
    {
        $this->checkValues($option, ['deny', 'sameorigin', 'allow-from']);

        if ($option === 'allow-from') {
            if (empty($url)) {
                throw new InvalidArgumentException('The 2nd arg $url can not be empty when `allow-from` is used');
            }
            $option .= ' ' . $url;
        }

        $this->headers['x-frame-options'] = $option;

        return $this;
    }

    /**
     * X-XSS-Protection
     *
     * @link https://blogs.msdn.microsoft.com/ieinternals/2011/01/31/controlling-the-xss-filter
     * @param string $mode Mode value. Available Values: '1', '0', 'block'
     * @return $this
     */
    public function setXssProtection($mode = 'block')
    {
        $mode = (string)$mode;

        if ($mode === 'block') {
            $mode = '1; mode=block';
        }

        $this->checkValues($mode, ['1', '0', '1; mode=block']);
        $this->headers['x-xss-protection'] = $mode;

        return $this;
    }

    /**
     * X-Permitted-Cross-Domain-Policies
     *
     * @link https://www.adobe.com/devnet/adobe-media-server/articles/cross-domain-xml-for-streaming.html
     * @param string $policy Policy value. Available Values: 'all', 'none', 'master-only', 'by-content-type', 'by-ftp-filename'
     * @return $this
     */
    public function setCrossDomainPolicy($policy = 'all')
    {
        $this->checkValues($policy, ['all', 'none', 'master-only', 'by-content-type', 'by-ftp-filename']);
        $this->headers['x-permitted-cross-domain-policies'] = $policy;

        return $this;
    }

    /**
     * Convenience method to check if a value is in the list of allowed args
     *
     * @throws \InvalidArgumentException Thrown when a value is invalid.
     * @param string $value Value to check
     * @param array $allowed List of allowed values
     * @return void
     */
    protected function checkValues($value, array $allowed)
    {
        if (!in_array($value, $allowed)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid arg `%s`, use one of these: %s',
                $value,
                implode(', ', $allowed)
            ));
        }
    }

    /**
     * Serve assets if the path matches one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response = $next($request, $response);
        foreach ($this->headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }
}
