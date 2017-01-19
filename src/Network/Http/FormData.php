<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http;

class_alias(\Cake\Http\Client\FormData::class, FormData::class);

if (class_exists(FormData::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\FormData instead.
 */
class FormData extends \Cake\Http\Client\FormData
{

}
