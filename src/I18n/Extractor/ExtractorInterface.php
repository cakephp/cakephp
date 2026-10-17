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
 * Extracts translation messages and diagnostics from one PHP source file.
 *
 * @internal
 */
interface ExtractorInterface
{
    /**
     * Find messages without performing console output or writing catalogs.
     *
     * @param string $file Source filename used in diagnostics.
     * @param string $code PHP source code.
     * @return iterable<\Cake\I18n\Extractor\ExtractedMessage|\Cake\I18n\Extractor\ExtractionDiagnostic>
     */
    public function extract(string $file, string $code): iterable;
}
