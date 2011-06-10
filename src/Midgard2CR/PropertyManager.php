<?php

namespace Midgard2CR;

class PropertyHolder
{
    private $values = null;
    public $stored;
    public $modified;
    public $model = null;

    public function __construct()
    {
        $this->values = array();
        $this->stored = false;
        $this->model = null;
        $this->modified = false;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getLiterals()
    {
        $ret = array();
        foreach ($this->getValues() as $v)
        {
            $ret[] = $v->value;
        }
        return $ret;
    }

    public function addLiteral ($val, $id)
    {
        $value = new \midgard_property_value();
        $value->id = $id;
        $value->value = $val;

        $this->addValue ($value);
    }

    public function addValue ($value)
    {
        foreach ($this->values as $k => $v)
        {
            if ($v->value == $value->value)
            { 
                return;
            }
        }
        $this->modified = true; 
        array_unshift($this->values, $value);
    }

    public function setValue ($value)
    {
        $this->modified = true;
        foreach ($this->values as $k => $v)
        {
            if ($v->id == $value->id)
            { 
                $this->values[$k] = $value;
                if ($v->value == $value->value)
                {
                    $this->modified = false; 
                }
            } 
        }      
    }
}

class PropertyManager
{
    protected $cache = array();
    protected $classname = null;
    protected $object = null;
    protected $modifiedModels = null;

    public function __construct ($object)
    {
        $this->object = $object;
        $this->classname = get_class($object);
        $this->populateProperties();
        $this->modifiedModels = array();
    }

    protected function findInCache ($name, $prefix, $type)
    {
        if ($this->cache == null)
        {
            return null;
        }

        foreach ($this->cache as $property)
        {
            if ($property->model->name == $name
                && $property->model->prefix == $prefix)
            {
                if ($type == null)
                {
                    return $property;
                }

                if ($property->model->type == $type)
                {
                    return $property;
                }
            }
        }

        return null;
    }

    private function propertyFactory ($name, $prefix, $type, $multiple)
    {
        $property = $this->findInCache ($name, $prefix, $type);
        if ($property == null)
        {
            $property = new PropertyHolder();
            $property->model = new \midgard_property_model();
            $property->model->name = $name;
            $property->model->prefix = $prefix ? $prefix : "";
            $property->model->type = $type;
            $property->model->multiple = $multiple;
            $property->modified = true;
            array_unshift($this->cache, $property);
        }

        return $property;
    }

    public function factory ($name, $prefix, $type, $multiple, $value = null)
    {
        $property = $this->propertyFactory ($name, $prefix, $type, $multiple);

        if ($value != null)
        {
            $property->addLiteral($value, 0);
        } 

        return $property;
    }

    public function getProperty ($name, $prefix, $type = null)
    {
        return $this->findInCache ($name, $prefix, $type);
    }

    protected function populateProperties()
    {
        /* TODO, validate this->object, if there's ID or GUID property */

        $storage = new \midgard_query_storage("midgard_property_view");
        $q = new \midgard_query_select ($storage);
        $q->set_constraint
            (
                new \midgard_query_constraint
                (
                    new \midgard_query_property('objectguid'),
                    '=',
                    new \midgard_query_value($this->object->guid)
                )
            );
        $q->add_order(new \midgard_query_property("valueid"), SORT_ASC);

        $q->execute();
        if ($q->get_results_count() == 0)
        {
            return;
        }

        $this->cache = array();

        $ret = $q->list_objects();
        foreach ($ret as $p)
        { 
            $property = $this->propertyFactory($p->name, $p->prefix, $p->type, $p->multiple);
            $property->stored = true;
            $property->model->id = $p->modelid;
           
            $value = new \midgard_property_value();
            $value->id = $p->valueid;
            $value->modelid = $p->modelid;
            $value->value = $p->value; 
            $property->addValue($value);
            $property->modified = false;
        }
    }

    public function listModels()
    {
        $ret = array();

        foreach ($this->cache as $property)
        {
            $model = $property->model;
            $name = $model->prefix ? $model->prefix . ":" . $model->name : $model->name;
            $ret[$name] = $model;
        }

        return $ret;
    }

    public function getModel($name, $prefix)
    {
        foreach ($this->cache as $property)
        {
            if ($property->model->name == $name
                && $property->model->prefix == $prefix)
            {
                return $property->model;
            }
        }

        return null;
    }

    public function setModelType($name, $prefix, $type)
    {
        $model = $this->getModel($name, $prefix);
        if (!$model)
        {
            return;
        }

        if ($model->type == $type)
        {
            return;
        }

        $model->type = $type;

        $this->modifiedModels[] = $model;
    }

    private function createProperty ($property)
    {
        /* Check if there's property model */
        $model = null;
        $storage = new \midgard_query_storage("midgard_property_model");
        $q = new \midgard_query_select ($storage);
        $name_constraint = new \midgard_query_constraint
            (
                new \midgard_query_property('name'),
                '=',
                new \midgard_query_value($property->model->name)
            );
        $prefix_constraint = new \midgard_query_constraint
            (
                new \midgard_query_property('prefix'),
                '=',
                new \midgard_query_value($property->model->prefix)
            );
        $type_constraint = new \midgard_query_constraint
            (
                new \midgard_query_property('type'),
                '=',
                new \midgard_query_value($property->model->type)
            );
        $group = new \midgard_query_constraint_group();
        $group->add_constraint($name_constraint);
        $group->add_constraint($prefix_constraint);
        $group->add_constraint($type_constraint);
        $q->set_constraint($group);
        $q->set_limit(1);
        $q->execute();

        if ($q->get_results_count() > 0)
        {
            $ret = $q->list_objects();
            $model = $ret[0];
        }

        $replication_disabled = false;
        $mgd = \midgard_connection::get_instance();

        try {

            /* Disable replication, we do not need to replicate properties */
            /*if ($mgd->is_enabled_replication())
            {
                $mgd->enable_replication(false);
                $replication_disabled = true;
            }*/

            /* If there's no model, create new */
            if (!$model) 
            {
                $model = $property->model;
                $model->create(); 
            } 
    
            /* associate property model with object */
            $node_property = new \midgard_property();
            $node_property->modelid = $model->id;
            $node_property->propertyguid = $model->guid;
            $node_property->nodeid = $this->object->id;
            $node_property->nodeguid = $this->object->guid;
    
            $node_property->create();

            foreach ($property->getValues() as $value)
            { 
                $value->modelid = $model->id;
                $value->modelguid = $model->guid;
                $value->objectguid = $this->object->guid;
                $value->create();
            }

            $property->stored = true;
        }
        catch (midgard_error_exception $e)
        {
            if ($replication_disabled)
            {
                //$mgd->enable_replication(true);
                throw new \midgard_error_exception($e->getMessage());
            }
        }

        if ($replication_disabled)
        {
            //$mgd->enable_replication(true);
        }
    }

    private function populateValues ($property)
    {
        $populate = false;
        foreach ($property->getValues() as $value)
        {
            if (!$value->guid)
            {
                $populate = true;
                break;
            }
        }

        if (!$populate)
        {
            return;
        }

        $storage = new \midgard_query_storage("midgard_property_value");
        $q = new \midgard_query_select ($storage);
        $q->set_constraint
            (
                new \midgard_query_constraint
                (
                    new \midgard_query_property('modelid'),
                    '=',
                    new \midgard_query_value($property->model->id)
                )
            );

        $q->execute();
        if ($q->get_results_count() == 0)
        {
            return;
        }

        $this->cache = array();

        $ret = $q->list_objects();
        foreach ($ret as $p)
        { 
            $property->setValue($p);
        }
    }

    private function updateProperty ($property)
    {
        /* Replace values with objects fetched from storage */
        $this->populateValues($property);

        foreach ($property->getValues() as $value)
        {
            if (!$value->guid)
            {
                $value->modelid = $property->model->id;
                $value->modelguid = $property->model->guid;
                $value->objectguid = $this->object->guid;
                $value->create();
                return;
            }

            $value->update();
        }
    }

    private function deleteProperty ($property)
    {
        
    }

    public function save()
    { 
        foreach ($this->cache as $property)
        {
            if (!$property->modified)
            {
                continue;
            }

            if ($property->stored)
            {
                $this->updateProperty($property);
            }
            else 
            {
                $this->createProperty($property); 
            }
        }

        /* TODO, Optimize this.
         *
         * Models should be updated before property is saved, 
         * so calculate which models should be updated using 
         * properties info.
         */
        foreach ($this->modifiedModels as $model)
        {
            $model_object = new \midgard_property_model($model->id);
            $model_object->type = $model->type;
            $model_object->update();
        }
    }
}
?>
