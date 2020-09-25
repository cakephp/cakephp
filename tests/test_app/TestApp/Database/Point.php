<?php
declare(strict_types=1);

namespace TestApp\Database;

class Point
{
    protected $_lat;
    protected $_long;

    public static function fromGeoJSON($value)
    {
        if ($value === null) {
            return null;
        }

        return new static($value['coordinates'][0], $value['coordinates'][1]);
    }

    public function __construct($lat, $long)
    {
        $this->_lat = $lat;
        $this->_long = $long;
    }

    public function lat()
    {
        return $this->_lat;
    }

    public function long()
    {
        return $this->_long;
    }
}
