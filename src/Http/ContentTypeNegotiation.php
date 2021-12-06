<?php
declare(strict_types=1);

namespace Cake\Http;

use Psr\Http\Message\RequestInterface;

/**
 * Negotiates the prefered content type from what the application
 * provides and what the request has in its Accept header.
 */
class ContentTypeNegotiation
{
    /**
     * Get the most preferred content type from a request.
     *
     * Parse the Accept header preferences and return the most
     * preferred type. If multiple types are tied in preference
     * the first type of that preference value will be returned.
     *
     * You can expect null when the request has no Accept header.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to use.
     * @return string|null The prefered type.
     */
    public function prefers(RequestInterface $request): ?string
    {
        $parsed = $this->parseAccept($request);
        if (empty($parsed)) {
            return null;
        }
        $types = array_shift($parsed);

        return $types[0];
    }

    /**
     * Perform content type negotiation with a list of valid choices.
     *
     * Choose a content-type from a list of values the application
     * can provide. If there are no matches then `null` will be
     * returned. You can also expect null when the request has
     * no `Accept` header.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to use.
     * @param string[] $types The types that the application can respond with for the provided request.
     * @return string|null Either the resolved type or `null` if no decision can be made.
     */
    public function prefersChoice(RequestInterface $request, array $types): ?string
    {
        $parsed = $this->parseAccept($request);
        foreach ($parsed as $acceptTypes) {
            $common = array_intersect($acceptTypes, $types);
            if ($common) {
                return $common[0];
            }
        }

        return null;
    }

    /**
     * Parse Accept* headers with qualifier options.
     *
     * Only qualifiers will be extracted, any other accept extensions will be
     * discarded as they are not frequently used.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to get an accept from.
     * @param string $header The header name to read.
     * @return array
     */
    public function parseAccept(RequestInterface $request, string $header = 'Accept'): array
    {
        $header = $request->getHeaderLine($header);
        $accept = [];
        if (!$header) {
            return $accept;
        }
        $headers = explode(',', $header);
        foreach (array_filter($headers) as $value) {
            $prefValue = '1.0';
            $value = trim($value);

            $semiPos = strpos($value, ';');
            if ($semiPos !== false) {
                $params = explode(';', $value);
                $value = trim($params[0]);
                foreach ($params as $param) {
                    $qPos = strpos($param, 'q=');
                    if ($qPos !== false) {
                        $prefValue = substr($param, $qPos + 2);
                    }
                }
            }

            if (!isset($accept[$prefValue])) {
                $accept[$prefValue] = [];
            }
            if ($prefValue) {
                $accept[$prefValue][] = $value;
            }
        }
        krsort($accept);

        return $accept;
    }
}
