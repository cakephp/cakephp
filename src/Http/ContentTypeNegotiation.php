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
     * Parse Accept* headers with qualifier options.
     *
     * Only qualifiers will be extracted, any other accept extensions will be
     * discarded as they are not frequently used.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to get an accept from.
     * @return array<string, array<string>> A mapping of preference values => content types
     */
    public function parseAccept(RequestInterface $request): array
    {
        $header = $request->getHeaderLine('Accept');

        return $this->parseQualifiers($header);
    }

    /**
     * Parse the Accept-Language header
     *
     * Only qualifiers will be extracted, other extensions will be ignored
     * as they are not frequently used.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to get an accept from.
     * @return array<string, array<string>> A mapping of preference values => languages
     */
    public function parseAcceptLanguage(RequestInterface $request): array
    {
        $header = $request->getHeaderLine('Accept-Language');

        return $this->parseQualifiers($header);
    }

    /**
     * Parse a header value into preference => value mapping
     *
     * @param string $header The header value to parse
     * @return array<string, array<string>>
     */
    protected function parseQualifiers(string $header): array
    {
        return HeaderUtility::parseAccept($header);
    }

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
     * @param list<string> $choices The supported content type choices.
     * @return string|null The prefered type or null if there is no match with choices or if the
     *   request had no Accept header.
     */
    public function preferredType(RequestInterface $request, array $choices = []): ?string
    {
        $parsed = $this->parseAccept($request);
        if (!$parsed) {
            return null;
        }
        if (!$choices) {
            $preferred = array_shift($parsed);

            return $preferred[0];
        }

        foreach ($parsed as $acceptTypes) {
            $common = array_intersect($acceptTypes, $choices);
            if ($common) {
                return array_shift($common);
            }
        }

        return null;
    }

    /**
     * Get the normalized list of accepted languages
     *
     * Language codes in the request will be normalized to lower case and have
     * `_` replaced with `-`.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to read headers from.
     * @return array<string> A list of language codes that are accepted.
     */
    public function acceptedLanguages(RequestInterface $request): array
    {
        $raw = $this->parseAcceptLanguage($request);
        $accept = [];
        foreach ($raw as $languages) {
            foreach ($languages as &$lang) {
                if (strpos($lang, '_')) {
                    $lang = str_replace('_', '-', $lang);
                }
                $lang = strtolower($lang);
            }
            $accept = array_merge($accept, $languages);
        }

        return $accept;
    }

    /**
     * Check if the request accepts a given language code.
     *
     * Language codes in the request will be normalized to lower case and have `_` replaced
     * with `-`.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to read headers from.
     * @param string $lang The language code to check.
     * @return bool Whether the request accepts $lang
     */
    public function acceptLanguage(RequestInterface $request, string $lang): bool
    {
        $accept = $this->acceptedLanguages($request);

        return in_array(strtolower($lang), $accept, true);
    }
}
