<?php
namespace FinXLog\Module\ImportQuotation\Source;
use FinXLog\Exception;

abstract class AbsSource
{
    const DATETIME_FORMAT = 'Y/m/d H:i:s';

    protected static $valid_quotation = [
        'S' => 'MSG',
        'T' => '2001-01-01',
        'B' => 1
    ];

    public static function getValidQuotation()
    {
        return static::$valid_quotation;
    }

    public static function validate(array $result)
    {
        //empty value not needed
        $result = array_filter($result);
        if (
            $diff = array_diff_key(
                static::getValidQuotation(),
                $result
            )
        ) {
            throw (new Exception\WrongImport(
                'import quotatin with empty param: '
                . implode(', ', array_keys($diff))
            ))
                ->setParams(['quotation' =>$result]);
        }
        if (!strtotime($result['T'])) {
            throw (new Exception\WrongParams('is not a valid datetime: ' . $result['T']))
                ->setParams(['T' => $result['T']]);
        }
    }

    public static function filter(array $result)
    {
        if (getenv('FINXLOG_FILTER_OTHER')) {
            $result = array_diff_key(
                $result,
                static::getValidQuotation()
            );
        }

        return $result;
    }

    public static function prepare(array $result)
    {
        $result['T'] = static::getTime($result['T']);
        $result['B'] = (float) $result['B'];
        assert(!empty($result['B']));

        return $result;
    }
    public static function getTime($string)
    {
        $timestamp = strtotime($string);
        if (!$timestamp) {
            throw (new Exception\WrongParams('is not a valid datetime: ' . $string))
                ->setParams(['T' => $string]);
        }
        return date(static::DATETIME_FORMAT, $timestamp);
    }
}