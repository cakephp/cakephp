<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
     * @return array The token data.
     */
    protected function _buildFieldToken($url, $fields, $unlockedFields = [])
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
        sort($fields, SORT_STRING);
        ksort($locked, SORT_STRING);
        $fields += $locked;

        $locked = implode(array_keys($locked), '|');
        $unlocked = implode($unlockedFields, '|');
        $hashParts = [
            $url,
            serialize($fields),
            $unlocked,
            Security::salt()
        ];
        $fields = Security::hash(implode('', $hashParts), 'sha1');

        return [
            'fields' => urlencode($fields . ':' . $locked),
            'unlocked' => urlencode($unlocked),
        ];
    }
}
