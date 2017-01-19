<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http\FormData;

class_alias(\Cake\Http\Client\FormDataPart::class, Part::class);

if (class_exists(Part::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\FormDataPart instead.
 */
class Part extends \Cake\Http\Client\FormDataPart
{
}
