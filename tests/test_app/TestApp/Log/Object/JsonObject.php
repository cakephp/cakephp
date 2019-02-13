<?php

namespace TestApp\Log\Object;

use JsonSerializable;

/**
 * used for testing when an serializable is passed to a logger
 */
class JsonObject implements JsonSerializable
{
    /**
     * String representation of the object
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return ['hello' => 'world'];
    }
}
