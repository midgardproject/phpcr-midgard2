<?php

class MidgardNodeMapper 
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
        if (!class_exists('\midgard_reflector_property'))
        {
            try 
            {
                $mrp = new \midgard_reflection_property($classname);
            }
            catch (\midgard_error_exception $e)
            {
                throw new \Exception ($classname. " not registered as MidgardObject derived one"); 
            }
        }
        else 
        {
            try 
            {
                $mrp = new \midgard_reflector_property ($classname);
            }
            catch (\midgard_error_exception $e)
            {
                throw new \Exception ($classname . " not registered as MidgardObject derived one"); 
            }
        }
        $type = $mrp->get_midgard_type ($property);

        $type_id = 0;

        switch ($type) 
        {
            case \MGD_TYPE_STRING:
            case \MGD_TYPE_LONGTEXT:
            case \MGD_TYPE_GUID:
                $type_id = \PHPCR\PropertyType::STRING;
                break;

            case \MGD_TYPE_UINT:
            case \MGD_TYPE_INT:
                $type_id = \PHPCR\PropertyType::LONG;
                break;

            case \MGD_TYPE_FLOAT:
                $type_id = \PHPCR\PropertyType::DOUBLE;
                break;

            case \MGD_TYPE_BOOLEAN:
                $type_id = \PHPCR\PropertyType::BOOLEAN;
                break;

            case \MGD_TYPE_TIMESTAMP:
                $type_id = \PHPCR\PropertyType::DATE;
                break;
        }

        /* Try schema value */
        $type = $mrp->get_user_value($property, 'RequiredType');
        if ($type != '' || $type != null)
        {
            $type_id = \PHPCR\PropertyType::valueFromName($type);
        }

        return $type_id;
    }
}

?>
