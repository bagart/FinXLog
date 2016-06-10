<?php
namespace FinXLog\Module\Import\Source;

use FinXLog\Exception;

class Telnet extends AbsSource
{
    /**
     * @param $string
     * @return array
     * @throws Exception\WrongParam
     */
    public static function getFromRaw($string)
    {
        assert(is_string($string));

        $field = explode(
            ';',
            trim($string)
        );
        assert(count($field) == 3);
        $result = [];
        foreach ($field as $cur) {
            list($name, $value) = explode('=', $cur);
            $result[$name] = $value;
        }
        static::validate($result);
        $result = static::prepare($result);
        $result = static::filter($result);

        return $result;
    }
}