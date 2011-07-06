<?php
namespace Midgard2CR;

class Value 
{
    public static function toString($value)
    {
        return (string)$value;
    }

    public static function toLong($value)
    {
        return intval($value);
    }

    public static function toBinary($value)
    {
        $f = fopen('php://memory', 'rwb+');
        fwrite($f, $value);
        rewind($f);

        return $f;
    }

    public static function toDouble($value)
    {
        return floatval($value);
    }

    public static function toDecimal($value)
    {
        $v = self::toDouble($value);
        $current = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'C');

        $v = self::toString($v);
        setlocale(LC_ALL, $current);
        
        return $v;
    }

    public static function toBoolean($value)
    {
        return (bool)$value;
    }

    public static function toDate($value)
    {

    }

    public static function fromArray($values, $method)
    {
        $ret = array();
        foreach ($values as $value)
        {
            $ret[] = static::$method($value);
        }
        return $ret;
    }
}

class StringValue extends Value
{

}

class DateValue extends Value
{
    public static function toDate($value)
    {
        if ($value instanceof \DateTime)
        {
            return $value;
        }
        else
        {
            $date = new \DateTime(self::toString($value));
        }
    }

    public static function toString($value)
    {
        if ($value instanceof \DateTime)
        {
            $date = $value;
        }
        else
        {
            $date = new \DateTime($value);
        }

        $tmp = $date->format('Y-m-d\TH:i:s.');
        $tmp .= substr($date->format('u'), 0, 3);
        $tmp .= $date->format('P');

        return $tmp;
    }
}

class LongValue extends Value
{

}

class BinaryValue extends Value
{
    public static function toString($value)
    { 
        return stream_get_contents($value);
    } 
}

class ValueFactory
{
    public static function transformValue($value, $srcType, $dstType)
    {
        $valueClass = "Value";
        $valueMethod = "toString";
        
        switch($srcType) 
        {
            case \PHPCR\PropertyType::BINARY:
                $valueClass = 'BinaryValue';
                break;

            case \PHPCR\PropertyType::STRING:
                $valueClass = 'StringValue';
                break;

            case \PHPCR\PropertyType::LONG:
                $valueClass = 'LongValue';
                break;

            case \PHPCR\PropertyType::DOUBLE:
                $valueClass = 'DoubleValue';
                break;

            case \PHPCR\PropertyType::DATE:
                $valueClass = 'DateValue';
                break;

        }

        switch($dstType)
        {
            case \PHPCR\PropertyType::BINARY:
                $valueMethod = 'toBinary';
                break;

            case \PHPCR\PropertyType::STRING:
                $valueMethod = 'toString';
                break;

            case \PHPCR\PropertyType::LONG:
                $valueMethod = 'toLong';
                break;

            case \PHPCR\PropertyType::DOUBLE:
                $valueMethod = 'toDouble';
                break;

            case \PHPCR\PropertyType::DATE:
                $valueMethod = 'toDate';
                break;

            case \PHPCR\PropertyType::BOOLEAN:
                $valueMethod = 'toBoolean';
                break;
        }

        if (is_array($value))
        {
            $func = '\\' . $valueClass . '::fromArray';
            return call_user_func(__NAMESPACE__ . $func, $value, $valueMethod); 
        }

        $func = '\\' . $valueClass . '::' . $valueMethod;
        return call_user_func(__NAMESPACE__ . $func, $value);
    }
}

