<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;

class SQLQuery implements \PHPCR\Query\QueryInterface
{
    protected $session = null;
    protected $qs = null;
    protected $statement = null;
    protected $selectors = array();
    protected $converter = null;
    protected $query = null;
    protected $propertyStorage = null;
    protected $defaultNodeStorage = null;
    protected $defaultGroupConstraint = null;

    protected $storageType = null;
    
    public function __construct (\Midgard\PHPCR\Session $session, $statement)
    {
        $this->session = $session;
        $this->statement = $statement;
        $this->converter = new \PHPCR\Util\QOM\Sql2ToQomQueryConverter(new QOM\QueryObjectModelFactory());
        $this->query = $this->converter->parse($statement);
        $this->QBFromStatement();
    }

    private function getDefaultNodeStorage()
    {
        if ($this->defaultNodeStorage == null)
            $this->defaultNodeStorage = new \midgard_query_storage('midgard_node');
        return $this->defaultNodeStorage;
    }

    private function getQuerySelect()
    {
        if ($this->qs == null) 
            $this->qs = new \midgard_query_select($this->getDefaultNodeStorage());
        return $this->qs;
    }

    private function getPropertyStorage()
    {
        if ($this->propertyStorage == null)
            $this->propertyStorage = new \midgard_query_storage('midgard_node_property');
        return $this->propertyStorage;
    }

    private function getDefaultGroupConstraint()
    {
        if ($this->defaultGroupConstraint == null) 
            $this->defaultGroupConstraint = new \midgard_query_constraint_group("AND");
        return $this->defaultGroupConstraint;
    }

    /* Add implicit join.
     * We join midgard_node_property.parent on midgard_node.id */
    private function addJoinIDToParent()
    {
        static $joined = false;
        if ($joined == true)
            return;

        $this->getQuerySelect()->add_join(
            'INNER',
            new \midgard_query_property('id'),
            new \midgard_query_property('parent', $this->getPropertyStorage())
        );
        $joined = true;
    }

    private function addConstraintSingle(\midgard_query_constraint $constraint)
    {
        $this->qs->set_constraint($constraint);
    }

    private function addConstraintMultiple(array $constraints)
    {
        var_dump($constraints);
        die();
        $cg = new \midgard_query_constraint_group('AND');
        foreach ($constraints as $constraint) {
            $cg->add_constraint($constraint);
        }
        $this->qs->set_constraint($cg);
    }

    private function addConstraintChildNode()
    {
        /* There's a path specified so we need to find node under parent's path */
        $parentPath = $this->query->getConstraint()->getParentPath();
        $parts = explode("/", $parentPath);
        if (empty($parts))
            return;
        /* Reverse path's elements so we can generate correct joins */
        $parts = array_reverse($parts);
        $currentStorage = null;
        /* For each element in path, add join and constraint with parent's name */
        foreach ($parts as $name) {
            $nodeStorage = new \midgard_query_storage("midgard_node");
            $this->getQuerySelect()->add_join(
                'INNER',
                $currentStorage == null ? new \midgard_query_property('parent') : new \midgard_query_property('parent', $currentStorage),
                new \midgard_query_property('id', $nodeStorage)
            );
            $name = str_replace('"', '', $name);
            $constraint = new \midgard_query_constraint(
                new \midgard_query_property("name", $nodeStorage),
                "=",
                new \midgard_query_value ($name == '' ? 'root' : $name)
            );
            $this->getDefaultGroupConstraint()->add_constraint($constraint);
            $currentStorage = $nodeStorage;
        } 
    }

    private function addOrders()
    {
        $orderings = $this->query->getOrderings();
        if (empty($orderings))
            return;

        //$this->addJoinIDToParent();

        foreach ($orderings as $order) {
            print_r($order->getOperand()->getPropertyName());
            $constraint = new \midgard_query_constraint (
                new \midgard_query_property ("value"),
                "=",
                new \midgard_query_value ($order->getOperand()->getPropertyName()),
                $this->getPropertyStorage()
            );
            $propertyStorage = new \midgard_query_storage ("midgard_node_property");
            $this->getQuerySelect()->add_join(
                'INNER',
                new \midgard_query_property('id'),
                new \midgard_query_property('parent', $propertyStorage)
            );
            $this->getQuerySelect()->add_order (new \midgard_query_property('value', $propertyStorage));
        }
    }

    private function QBFromStatement()
    {
        $scanner = new \PHPCR\Util\QOM\Sql2Scanner($this->statement);
        $type = null;
        $inTree = null;
        do {
            $token = $scanner->fetchNextToken(); 
            if ($token == 'FROM') {
                $type = $scanner->fetchNextToken();
            }
            if ($token == 'ISCHILDNODE') {
                $scanner->fetchNextToken();
                $inTree = substr($scanner->fetchNextToken(), 1, -1);
            }
        } while ($token != '');

        if (is_null($type)) {
            throw new \PHPCR\Query\InvalidQueryException('No content types defined in query');
        }

        $this->storageType = NodeMapper::getMidgardName($this->query->getSource()->getNodeTypeName()); 
        $this->selectors[] = $this->query->getSource()->getNodeTypeName();

        $storage = new \midgard_query_storage('midgard_node');
        $this->qs = new \midgard_query_select($storage);
        $constraints = array();

        if ($this->storageType != null) {
            $constraints[] = new \midgard_query_constraint(
                new \midgard_query_property('typename'),
                '=',
                new \midgard_query_value($this->storageType)
            );
        }

        if ($inTree) {
            $parent = $this->session->getNode($inTree);
            $constraints[] = new \midgard_query_constraint(
                new \midgard_query_property('parent'),
                '=',
                new \midgard_query_value($parent->getMidgard2Node()->id)
            );
        }

        /*if (count($constraints) > 1) {
            return $this->addConstraintMultiple($constraints);
        }
        $this->addConstraintSingle($constraints[0]);*/
        if (is_a($this->query->getConstraint(), 'Midgard\PHPCR\Query\QOM\ChildNode'))
            $this->addConstraintChildNode(); 

        echo "\n\n EXECUTE : " . $this->statement . "\n";

        $this->addOrders();

        \midgard_connection::get_instance()->set_loglevel("debug");
        $this->qs->set_constraint($this->getDefaultGroupCOnstraint());
        $this->qs->execute();
        \midgard_connection::get_instance()->set_loglevel("warn");
        die();
    } 

    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function execute()
    {
        $this->qs->execute();
        return new QueryResult($this->selectors, $this->qs, $this->session);
    }

    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function setLimit($limit)
    {
        $this->qs->set_limit($limit);
    }
   
    public function setOffset($offset)
    {
        $this->qs->set_offfset($offset);
    }
  
    public function getStatement()
    {
        return $this->statement;
    }
     
    public function getLanguage()
    {
        return \PHPCR\Query\QueryInterface::JCR_SQL2; 
    }
     
    public function getStoredQueryPath()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
  
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

}
