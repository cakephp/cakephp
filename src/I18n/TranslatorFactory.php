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
 * @since         3.3.12
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\FormatterInterface;
use Aura\Intl\TranslatorFactory as BaseTranslatorFactory;
use Aura\Intl\TranslatorInterface;

/**
 * Factory to create translators
 *
 * @internal
 */
class TranslatorFactory extends BaseTranslatorFactory
{
    /**
     * The class to use for new instances.
     *
     * @var string
     */
    protected $class = 'Cake\I18n\Translator';
}
