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
 * @since         3.1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Utility\Security;

/**
 * Provides methods for building token data that is
 * compatible with SecurityComponent.
 */
trait SecureFieldTokenTrait
{
    /**
     * Generate the token data for the provided inputs.
     *
     * @param string $url The URL the form is being submitted to.
     * @param array $fields If set specifies the list of fields to use when
     *    generating the hash.
     * @param array $unlockedFields The list of fields that are excluded from
     *    field validation.
     * @param array $optionalFields The list of fields that are not required
     *    but must retain their value if they are present.
     * @return array The token data.
     */
    protected function _buildFieldToken($url, $fields, $unlockedFields = [], $optionalFields = [])
    {
        $locked = [];
        foreach ($fields as $key => $value) {
            if (is_numeric($value)) {
                $value = (string)$value;
            }
            if (!is_int($key)) {
                $locked[$key] = $value;
                unset($fields[$key]);
            }
        }

        sort($unlockedFields, SORT_STRING);
        ksort($optionalFields, SORT_STRING);
        sort($fields, SORT_STRING);
        ksort($locked, SORT_STRING);
        $fields += $locked;

        $locked = implode('|', array_keys($locked));
        $unlocked = implode('|', $unlockedFields);
        $optional = implode('|', array_keys($optionalFields));
        $hashParts = [
            $url,
            serialize($fields),
            $unlocked,
            $optional,
            Security::salt()
        ];
        $fields = Security::hash(implode('', $hashParts), 'sha1');

        $optional = [];
        foreach ($optionalFields as $name => $value) {
            // Include more than just the value and salt in the string we hash,
            // because that would be easily subject to replay attacks.
            $optional[] = $name . '=' . Security::hash($name . $value . Security::salt() . $fields);
        }

        return [
            'fields' => urlencode($fields . ':' . $locked),
            'unlocked' => urlencode($unlocked),
            'optional' => urlencode(implode('|', $optional)),
        ];
    }
}
