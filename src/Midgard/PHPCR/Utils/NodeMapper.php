<?php
namespace Midgard\PHPCR\Utils;

use midgard_reflection_property;
use midgard_error_exception;
use PHPCR\PropertyType;

class NodeMapper 
{
    /**
     * Replaces ':' with '_' and returns given type converted to lowercase.
     */ 
    public static function getMidgardName($type)
    {
        if (strpos($type, ':') === false)
        {
            if (strpos($type, '_') !== false)
            {
                return $type;
            }
            return null;
        }

        if (substr($type, 0, 4) == 'mgd:') {
            return substr($type, 4);
        }

        return str_replace(':', '_', $type);
    }

    /**
     * Replaces '_' with ':' in given type 
     */ 
    public static function getPHPCRName($type)
    {
        if (strpos($type, '_') === false)
        {
            if (strpos($type, ':') !== false)
            {
                return $type;
            }
            return null;
        }
        /* TODO, determine uper cases */
        return str_replace('_', ':', $type);
    }

    /**
     * Replaces '-' with ':' in given property name
     */
    public static function getPHPCRProperty($property)
    {
        if (strpos($property, '-') === false)
        {
            if (strpos($property, ':') !== false)
            {
                return $property;
            }
            return null;
        }
        /* TODO, determine uper cases */
        return str_replace('-', ':', $property);
    }

    /**
     * Replaces '-' with ':' in given property name
     */
    public static function getMidgardPropertyName($property)
    {
        if (strpos($property, ':') === false)
        {
            if (strpos($property, '-') !== false)
            {
                return $property;
            }
            return null;
        }
        /* TODO, determine uper cases */
        return str_replace(':', '-', $property);
    }

    public static function getPHPCRPropertyType($classname, $property)
    {
        if (!is_subclass_of($classname, 'MidgardDBObject')) {
            return null;
        }

        $mrp = new midgard_reflection_property($classname);
        $requiredType = $mrp->get_user_value($property, 'RequiredType');
        if ($requiredType) {
            return PropertyType::valueFromName($requiredType);
        }

        $type = $mrp->get_midgard_type($property);
        switch ($type) {
            case \MGD_TYPE_STRING:
            case \MGD_TYPE_LONGTEXT:
            case \MGD_TYPE_GUID:
                $type_id = PropertyType::STRING;
                break;

            case \MGD_TYPE_UINT:
            case \MGD_TYPE_INT:
                $type_id = PropertyType::LONG;
                break;

            case \MGD_TYPE_FLOAT:
                $type_id = PropertyType::DOUBLE;
                break;

            case \MGD_TYPE_BOOLEAN:
                $type_id = PropertyType::BOOLEAN;
                break;

            case \MGD_TYPE_TIMESTAMP:
                $type_id = PropertyType::DATE;
                break;

            default:
                $type_id = 0;
        }

        return $type_id;
    }
}
