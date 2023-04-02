<?php
declare(strict_types=1);

namespace Cake\Http;

/**
 * Provides methods which parse HTTP header strings
 */
class HeaderParser
{
    /**
     * Get an array representation of the HTTP Link header values
     *
     * @param array $linkHeaders An array of Link Headers
     * @return array
     */
    public static function link(array $linkHeaders): array
    {
        $result = [];
        foreach ($linkHeaders as $linkHeader) {
            $result[] = HeaderParser::linkItem($linkHeader);
        }

        return $result;
    }

    /**
     * Parses one item of the HTTP link header into an array
     *
     * @param string $value The HTTP Link header part
     * @return array[]
     */
    protected static function linkItem(string $value): array
    {
        preg_match('/<(.*)>[; ]?[; ]?(.*)?/i', $value, $matches);

        $url = $matches[1];
        $parsedParams = [];

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

        return [
            [
                'link' => $url,
            ] + $parsedParams,
        ];
    }

    /**
     * Parse a header value into preference => value mapping
     *
     * @param string $header The header value to parse
     * @return array<string, array<string>>
     */
    public static function qualifiers(string $header): array
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
}
