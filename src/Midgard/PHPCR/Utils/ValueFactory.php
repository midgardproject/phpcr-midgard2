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
