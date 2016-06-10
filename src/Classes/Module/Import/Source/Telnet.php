<?php
namespace FinXLog\Module\Import\Source;

use FinXLog\Exception;
use FinXLog\Iface;

class Telnet extends AbsSource
{
    /**
     * @param $string
     * @return array
     * @throws Exception\WrongParams
     */
    public static function getFromRaw($string)
    {
        try {
            assert(is_string($string));

            $field = explode(
                ';',
                trim($string)
            );
            if (count($field) < 3) {
                throw new Exception\WrongImport('wrong import');
            }
            assert(count($field) == 3);

            $result = [];
            foreach ($field as $cur) {
                list($name, $value) = explode('=', $cur);
                $result[$name] = $value;
            }
            static::validate($result);
            $result = static::prepare($result);
            $result = static::filter($result);
        } catch (Iface\ExceptionWithParams $e) {
            throw $e->addParams(['import' => $string]);
        }
        return $result;
    }
}