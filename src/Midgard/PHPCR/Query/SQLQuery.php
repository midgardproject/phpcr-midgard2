<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeInterface;

class SQLQuery implements \PHPCR\Query\QueryInterface
{
    protected $session = null;
    protected $qs = null;
    protected $statement = null;
    protected $selectors = array();
    protected $node = null;
    protected $converter = null;
    protected $query = null;
    protected $storageType = null;
    
    public function __construct (\Midgard\PHPCR\Session $session, $statement)
    {
        $this->session = $session;
        $this->statement = $statement;
        $this->converter = new \PHPCR\Util\QOM\Sql2ToQomQueryConverter(new QOM\QueryObjectModelFactory());
        $this->query = $this->converter->parse($statement);
        $this->QBFromStatement();
    }

    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }

    private function addConstraintSingle(\midgard_query_constraint $constraint)
    {
        $this->qs->set_constraint($constraint);
    }

    private function addConstraintMultiple(array $constraints)
    {
        $cg = new \midgard_query_constraint_group('AND');
        foreach ($constraints as $constraint) {
            $cg->add_constraint($constraint);
        }
        $this->qs->set_constraint($cg);
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

        if (count($constraints) > 1) {
            return $this->addConstraintMultiple($constraints);
        }
        $this->addConstraintSingle($constraints[0]);
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
        if (!$this->node) {
            throw new \PHPCR\ItemNotFoundException("Query not stored");
 
        }
        return $this->node->getPath();
    }
  
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

}
