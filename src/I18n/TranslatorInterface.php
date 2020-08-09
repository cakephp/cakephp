<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @copyright     Copyright (c) 2017 Aura for PHP
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

/**
 * TranslatorInterface
 */
interface TranslatorInterface
{
    /**
     * Translates the message formatting any placeholders.
     *
     * @param string $key The message key.
     * @param array $tokensValues Token values to interpolate into the
     *   message.
     * @return string The translated message with tokens replaced.
     */
    public function translate($key, array $tokensValues = []): string;
}
