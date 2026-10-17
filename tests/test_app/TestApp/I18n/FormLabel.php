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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\I18n;

use Cake\I18n\TranslatableMessage;

/**
 * Specializes message metadata for an application's form labels.
 */
readonly class FormLabel extends TranslatableMessage
{
    /**
     * Supply the application's form translation domain and label context.
     *
     * @param string $message Source label text.
     * @param array<array-key, mixed> $arguments Label formatting arguments.
     */
    public function __construct(string $message, array $arguments = [])
    {
        parent::__construct($message, $arguments, domain: 'forms', context: 'heading');
    }
}
