<?php
declare(strict_types=1);

namespace Cake\Http;

/**
 * Provides helper methods related to HTTP headers
 */
class HeaderUtility
{
    /**
     * Get an array representation of the HTTP Link header values.
     *
     * @param array $linkHeaders An array of Link header strings.
     * @return array
     */
    public static function parseLinks(array $linkHeaders): array
    {
        $result = [];
        foreach ($linkHeaders as $linkHeader) {
            $result[] = static::parseLinkItem($linkHeader);
        }

        return $result;
    }

    /**
     * Parses one item of the HTTP link header into an array
     *
     * @param string $value The HTTP Link header part
     * @return array<string, mixed>
     */
    protected static function parseLinkItem(string $value): array
    {
        preg_match('/<(.*)>[; ]?[; ]?(.*)?/i', $value, $matches);

        $url = $matches[1];
        $parsedParams = ['link' => $url];

        $params = $matches[2];
        if ($params) {
            $explodedParams = explode(';', $params);
            foreach ($explodedParams as $param) {
                $explodedParam = explode('=', $param);
                $trimedKey = trim($explodedParam[0]);
                $trimedValue = trim($explodedParam[1], '"');
                if ($trimedKey === 'title*') {
                    // See https://www.rfc-editor.org/rfc/rfc8187#section-3.2.3
                    preg_match('/(.*)\'(.*)\'(.*)/i', $trimedValue, $matches);
                    $trimedValue = [
                        'language' => $matches[2],
                        'encoding' => $matches[1],
                        'value' => urldecode($matches[3]),
                    ];
                }
                $parsedParams[$trimedKey] = $trimedValue;
            }
        }

        return $parsedParams;
    }

    /**
     * Parse the Accept header value into weight => value mapping.
     *
     * @param string $header The header value to parse
     * @return array<string, array<string>>
     */
    public static function parseAccept(string $header): array
    {
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

    /**
     * @param string $value The WWW-Authenticate header
     * @return array
     */
    public static function parseWwwAuthenticate(string $value): array
    {
        preg_match_all(
            '@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@',
            $value,
            $matches,
            PREG_SET_ORDER
        );

        $return = [];
        foreach ($matches as $match) {
            $return[$match[1]] = $match[3] ?? $match[2];
        }

        return $return;
    }
}
