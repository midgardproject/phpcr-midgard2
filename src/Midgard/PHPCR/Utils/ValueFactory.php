<?php
namespace Midgard\PHPCR\Utils;

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
        if (is_array($value)) {
            $ret = array();
            foreach ($value as $val) {
                $ret[] = self::transformValue($val, $srcType, $dstType);
            }
            return $ret;
        }
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

        $func = '\\' . $valueClass . '::' . $valueMethod;
        return call_user_func(__NAMESPACE__ . $func, $value);
    }
}
