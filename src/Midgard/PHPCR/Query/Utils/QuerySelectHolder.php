<?php
namespace Midgard\PHPCR\Query\Utils;
use Midgard\PHPCR\Query\SQLQuery;

class QuerySelectholder
{
    protected $query = null;
    protected $querySelect = null;
    protected $propertyStorage = null;
    protected $defaultNodeStorage = null;
    protected $defaultConstraintGroup = null;
    protected $midgardStorageName = null;

    public function __construct (SQLQuery $query)
    {
        $this->query = $query;
    }

    public function getSQLQuery()
    {
        return $this->query;
    }

    public function setMidgardStorageName($name)
    {
        $this->midgardStorageName = $name;
    }

    public function getMidgardStorageName()
    {
        return $this->midgardStorageName;
    }

    public function getDefaultNodeStorage()
    {
        if ($this->defaultNodeStorage == null)
            $this->defaultNodeStorage = new \midgard_query_storage('midgard_node');
        return $this->defaultNodeStorage;
    }

    private function addStorageNamesConstraints($storageNames)
    {
        $cg = new \midgard_query_constraint_group("OR");

        foreach ($storageNames as $name) {
            $constraint = new \midgard_query_constraint(
                new \midgard_query_property("typename"),
                "=",
                new \midgard_query_value($name)
            );
            $cg->add_constraint($constraint);
        }
        $this->getDefaultConstraintGroup()->add_constraint($cg);

        return $this->querySelect;
    }

    public function getQuerySelect()
    {
        if ($this->querySelect == null) {
            $this->querySelect = new \midgard_query_select($this->getDefaultNodeStorage());

            /* This is workaround.
             * Initialy this routines has been written for MidgardQuerySelect.
             * MidgardQuerySelectData should be involved, in case of multiple selectors, columns, etc
             * SqlQuery should provide "best match" storage name. */
            $storageNames = $this->query->getMidgard2StorageNames();
            if (count($storageNames) > 1) {
                return $this->addStorageNamesConstraints($storageNames);
            }

            /* Implictly add nodetype constraint */
            $this->getDefaultConstraintGroup()->add_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property("typename"),
                    "=",
                    new \midgard_query_value($storageNames[0])
                )
            );

            /* Workaround for 'invalid number of operands' */
            $this->getDefaultConstraintGroup()->add_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property("typename"),
                    "<>",
                    new \midgard_query_value("")
                )
            );
        }
        return $this->querySelect;
    }

    public function getPropertyStorage()
    {
        if ($this->propertyStorage == null)
            $this->propertyStorage = new \midgard_query_storage('midgard_node_property');
        return $this->propertyStorage;
    }

    public function getDefaultConstraintGroup()
    {
        if ($this->defaultConstraintGroup == null)
            $this->defaultConstraintGroup = new \midgard_query_constraint_group("AND");
        return $this->defaultConstraintGroup;
    }
}
