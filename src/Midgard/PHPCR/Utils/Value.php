<?php
namespace Midgard\PHPCR\Utils;

class Value 
{
    protected static function getTransformableTypes()
    { 
        return array();
    }

    private static function __isTransformable($valueClass, $targetType)
    {
        $func = '\\' . $valueClass . '::getTransformableTypes';
        $transformableTypes = call_user_func(__NAMESPACE__ . $func);

        if (empty($transformableTypes))
        {
            return true;
        }

        if (!in_array($targetType, $transformableTypes))
        {
            return false;
        }

        return true;
    }

    public static function checkTransformable($srcType, $destType)
    {
        if ($srcType === $destType)
        {
            return;
        }
        $transformable = self::__isTransformable(ValueFactory::getValueClassName($srcType), $destType);
        if (!$transformable)
        {
            throw new \PHPCR\ValueFormatException("Can not transform " . \PHPCR\PropertyType::nameFromValue($srcType) . " to " . \PHPCR\PropertyType::nameFromValue($destType));
        }
    }

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
        /* Kind of ugly hack, but no idea how to convert 'false' to boolean false. 
         * The solution might be to store '0' in storage instead of literal 'false',
         * but in such case boolean to string conversion should return 'false', not '0'. */
        if (is_string($value)
            && strtolower($value) == 'false')
            return (bool)false;

        $values = \PHPCR\PropertyType::convertType(array($value), \PHPCR\PropertyType::BOOLEAN);
        return (bool) $values[0];
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
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::DOUBLE,
            \PHPCR\PropertyType::DECIMAL,
            \PHPCR\PropertyType::LONG
        );

        return $ta;
    }

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
            if (is_integer($value))
            {
                $value = date('c', $value);
            }

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
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::DOUBLE,
            \PHPCR\PropertyType::DECIMAL,
            \PHPCR\PropertyType::DATE
        );

        return $ta;
    }
}

class BinaryValue extends Value
{
    public static function toString($value)
    {
        if (!is_resource ($value))
        {
            return (string) $value;
        }
        return stream_get_contents($value);
    } 
}

class DoubleValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::DATE,
            \PHPCR\PropertyType::DECIMAL,
            \PHPCR\PropertyType::LONG
        );
        return $ta;
    }
}

class BooleanValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY
        );

        return $ta;
    }

    public static function toString($value)
    {
        $values = \PHPCR\PropertyType::convertType(array($value), \PHPCR\PropertyType::STRING, \PHPCR\PropertyType::BOOLEAN);
        return $values[0];
    }
}

class NameValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::PATH,
            \PHPCR\PropertyType::URI
        );

        return $ta;
    }
}

class PathValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::NAME,
            \PHPCR\PropertyType::URI
        );

        return $ta;
    }
}

class UriValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::NAME,
            \PHPCR\PropertyType::PATH
        );

        return $ta;
    }
}

class ReferenceValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::WEAKREFERENCE
        );

        return $ta;
    }
}

class WeakReferenceValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::REFERENCE
        );

        return $ta;
    }
}

class DecimalValue extends Value
{
    protected static function getTransformableTypes()
    {
        static $ta = array(
            \PHPCR\PropertyType::STRING,
            \PHPCR\PropertyType::BINARY,
            \PHPCR\PropertyType::DOUBLE,
            \PHPCR\PropertyType::DATE,
            \PHPCR\PropertyType::LONG
        );

        return $ta;
    }
}

class ValueFactory
{
    public static function getValueClassName($type)
    {
        $valueClass = 'StringValue';

        switch($type) 
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

            case \PHPCR\PropertyType::BOOLEAN:
                $valueClass = 'BooleanValue';
                break;

            case \PHPCR\PropertyType::NAME:
                $valueClass = 'NameValue';
                break;

            case \PHPCR\PropertyType::REFERENCE:
                $valueClass = 'ReferenceValue';
                break;

            case \PHPCR\PropertyType::PATH:
                $valueClass = 'PathValue';
                break;

            case \PHPCR\PropertyType::WEAKREFERENCE:
                $valueClass = 'WeakReferenceValue';
                break;

            case \PHPCR\PropertyType::URI:
                $valueClass = 'UriValue';
                break;
        }

        return $valueClass;

    }

    public static function transformValue($value, $srcType, $dstType)
    {
        $valueClass = "Value";
        $valueMethod = "toString";

        Value::checkTransformable($srcType, $dstType);

        $valueClass = self::getValueClassName($srcType);

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

