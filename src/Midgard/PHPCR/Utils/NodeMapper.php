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
        static $midgardNames = array();
        if (isset($midgardNames[$type])) {
            return $midgardNames[$type];
        }
        if (strpos($type, ':') === false)
        {
            if (strpos($type, '_') !== false)
            {
                $midgardNames[$type] = $type;
                return $type;
            }
            return null;
        }

        if (substr($type, 0, 4) == 'mgd:') {
            $midgardNames[$type] = substr($type, 4);
            return $midgardNames[$type];
        }

        $midgardNames[$type] = str_replace(':', '_', $type);
        return $midgardNames[$type];
    }

    /**
     * Replaces '_' with ':' in given type 
     */ 
    public static function getPHPCRName($type)
    {
        static $phpcrNames = array();
        if (isset($phpcrNames[$type])) {
            return $phpcrNames[$type];
        }

        if (substr($type, 0, 7) == 'midgard') {
            $phpcrNames[$type] = "mgd:{$type}";
            return $phpcrNames[$type]; 
        }

        if (strpos($type, '_') === false) {
            if (strpos($type, ':') !== false) {
                $phpcrNames[$type] = $type;;
                return $type;
            }
            return null;
        }
        /* TODO, determine uper cases */
        $phpcrNames[$type] = str_replace('_', ':', $type);
        return $phpcrNames[$type]; 
    }

    /**
     * Replaces first '-' with ':' in given property name
     */
    public static function getPHPCRProperty($property)
    {
        $pos = strpos($property, '-');
        if ($pos === false) {
            return $property;
        }
        /* TODO, determine uper cases */
        return substr_replace($property, ':', $pos, 1);
    }

    /**
     * Replaces all instances of ':' with '-' in given property name
     */
    public static function getMidgardPropertyName($property)
    {
        if (strpos($property, ':') === false) {
            return $property;
        }
        /* TODO, determine uper cases */
        return str_replace(':', '-', $property);
    }

    public static function getPHPCRPropertyType($classname, $property, midgard_reflection_property $reflector = null)
    {
        if (!$reflector) {
            if (!is_subclass_of($classname, 'MidgardDBObject')) {
                return null;
            }
            $reflector = new midgard_reflection_property($classname);
        }

        $requiredType = $reflector->get_user_value($property, 'RequiredType');
        if ($requiredType) {
            return PropertyType::valueFromName($requiredType);
        }

        $type = $reflector->get_midgard_type($property);
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
