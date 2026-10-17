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
 * Describes an extraction problem for the command to report.
 *
 * @internal
 */
final readonly class ExtractionDiagnostic
{
    /**
     * Store a warning or an invalid translation marker diagnostic.
     *
     * @param string $message Diagnostic including the source location.
     * @param bool $invalidMarker Whether this is counted and displayed by the marker-error option.
     */
    public function __construct(public string $message, public bool $invalidMarker = false)
    {
    }
}
