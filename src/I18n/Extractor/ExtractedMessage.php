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
namespace Cake\I18n\Extractor;

/**
 * Carries unescaped message metadata and its source location to the catalog writer.
 *
 * @internal
 */
final readonly class ExtractedMessage
{
    /**
     * Store the metadata shared by all extraction strategies.
     *
     * @param string $message Untranslated source text.
     * @param string $domain Catalog domain.
     * @param string $context Translation context.
     * @param string|null $plural Plural source text, when present.
     * @param int $line Source line, or zero when reflection cannot provide it.
     */
    public function __construct(
        public string $message,
        public string $domain = 'default',
        public string $context = '',
        public ?string $plural = null,
        public int $line = 0,
    ) {
    }
}
