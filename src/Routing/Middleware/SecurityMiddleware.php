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
namespace Cake\Routing\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles common security headers in a convenient way
 */
class SecurityMiddleware
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
     * Reference: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
     *
     * Available Value: 'nosniff'
     *
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
     * Reference: https://msdn.microsoft.com/en-us/library/jj542450(v=vs.85).aspx
     *
     * Available Value: 'noopen'
     *
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
     * Reference: https://w3c.github.io/webappsec-referrer-policy
     *
     * Available Value: 'no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin',
     *                  'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url'
     *
     * @param string $policy Policy value
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
     * Reference: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
     *
     * Available Value: 'deny', 'sameorigin', 'allow-from <uri>'
     *
     * @param string $mode Mode value
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
     * Reference: https://blogs.msdn.microsoft.com/ieinternals/2011/01/31/controlling-the-xss-filter
     *
     * Available Value: '1', '0', '1; mode=block'
     *
     * @param string $mode Mode value
     * @return $this
     */
    public function setXssProtection($mode = '1; mode=block')
    {
        if ($mode === 'block') {
            $mode = '1; mode=block';
        }

        $this->checkValues($mode, ['all', 'none', 'master-only', 'by-content-type', 'by-ftp-filename']);
        $this->headers['x-permitted-cross-domain-policies'] = $mode;

        return $this;
    }

    /**
     * X-Permitted-Cross-Domain-Policies
     *
     * Reference: https://www.adobe.com/devnet/adobe-media-server/articles/cross-domain-xml-for-streaming.html
     *
     * Available Value: 'all', 'none', 'master-only', 'by-content-type', 'by-ftp-filename'
     *
     * @param string $policy Policy value
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
     * @throws \InvalidArgumentException Thown when a value is invalid.
     * @param string $value
     * @param array $allowed
     * @return void
     */
    protected function checkValues($value, array $allowed)
    {
        if (!in_array($value, $allowed)) {
            throw new InvalidArgumentException(
                sprintf('Invalid arg `%s`, use one of these: %s',
                    $value,
                    implode(', ', $allowed)
                )
            );
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
        foreach ($this->headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $next($request, $response);
    }
}
