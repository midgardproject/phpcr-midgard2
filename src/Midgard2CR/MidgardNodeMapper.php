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

        return strtolower(str_replace(':', '_', $type));
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
}

?>
