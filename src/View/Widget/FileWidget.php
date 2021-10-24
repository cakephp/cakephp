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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Widget;

use Cake\Core\Configure;
use Cake\View\Form\ContextInterface;

/**
 * Input widget class for generating a file upload control.
 *
 * This class is usually used internally by `Cake\View\Helper\FormHelper`,
 * it but can be used to generate standalone file upload controls.
 */
class FileWidget extends BasicWidget
{
    /**
     * Data defaults.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [
        'name' => '',
        'escape' => true,
        'templateVars' => [],
    ];

    /**
     * Render a file upload form widget.
     *
     * Data supports the following keys:
     *
     * - `name` - Set the input name.
     * - `escape` - Set to false to disable HTML escaping.
     *
     * All other keys will be converted into HTML attributes.
     * Unlike other input objects the `val` property will be specifically
     * ignored.
     *
     * @param array<string, mixed> $data The data to build a file input with.
     * @param \Cake\View\Form\ContextInterface $context The current form context.
     * @return string HTML elements.
     */
    public function render(array $data, ContextInterface $context): string
    {
        $data += $this->mergeDefaults($data, $context);

        unset($data['val']);

        return $this->_templates->format('file', [
            'name' => $data['name'],
            'templateVars' => $data['templateVars'],
            'attrs' => $this->_templates->formatAttributes(
                $data,
                ['name']
            ),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function secureFields(array $data): array
    {
        // PSR7 UploadedFileInterface objects are used.
        if (Configure::read('App.uploadedFilesAsObjects', true)) {
            return [$data['name']];
        }

        // Backwards compatibility for array files.
        $fields = [];
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $suffix) {
            $fields[] = $data['name'] . '[' . $suffix . ']';
        }

        return $fields;
    }
}
