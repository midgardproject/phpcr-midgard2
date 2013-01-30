<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use PHPCR\NodeInterface;
use PHPCR\Util\QOM\Sql2ToQomQueryConverter;

class SQLQuery implements \PHPCR\Query\QueryInterface
{
    protected $session = null;
    protected $qs = null;
    protected $statement = null;
    protected $selectors = array();
    protected $node = null;
    protected $converter = null;
    protected $holder = null;
    protected $storageType = null;
    protected $source = null;
    protected $constraint = null;
    protected $orderings = null;
    protected $columns = null;
    protected $nodeTypeName = null;
    protected $limit = 0;
    protected $offset = -1;
    
    public function __construct (\Midgard\PHPCR\Session $session, $statement = null, \PHPCR\Query\QOM\SourceInterface $source = null,
            \PHPCR\Query\QOM\ConstraintInterface $constraint = null, array $orderings = null, array $columns = null)
    {
        $this->session = $session;
        $this->statement = $statement;
        $QOMFactory = new QOM\QueryObjectModelFactory($session);
        $this->converter = new Sql2ToQomQueryConverter($QOMFactory);
        $this->source = $source;
        $this->constraint = $constraint;
        $this->orderings = $orderings;
        $this->columns = $columns;

        if ($this->statement != null) {
            $this->validateStatement();
            $query = $this->converter->parse(trim($statement));
            $this->source = $query->getSource();
            //$this->source->computeQuerySelectConstraints($this->getQuerySelectholder());
            $this->nodeTypeName = $this->source->getNodeTypeName();
            $this->constraint = $query->getConstraint(); 
            $this->orderings = $query->getOrderings();
            $this->columns = $query->getColumns();
        }

        if (is_object($this->getSource())) { /* https://github.com/phpcr/phpcr-api-tests/issues/50 */
            $this->source->computeQuerySelectConstraints($this->getQuerySelectholder());
            $nodeTypeName = $this->source->getNodeTypeName();
            $this->storageType = NodeMapper::getMidgardName($nodeTypeName); 
            $this->nodeTypeName = $nodeTypeName;
        }
    }

    private function validateStatement()
    {
        if ($this->statement) { 
            if (strpos($this->statement, 'SELECT') === false)
                throw new \PHPCR\Query\InvalidQueryException("Invalid statement");
        }
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getConstraint()
    {
        return $this->constraint;
    }

    public function getOrderings()
    {
        return $this->orderings;
    }

    public function getSelectors()
    {
        if ($this->selectors != null) {
            return $this->selectors;
        }

        if ($this->source instanceOf \Midgard\PHPCR\Query\QOM\Selector) {
            $this->selectors[] = $this->source; 
        } else {
            /* TODO, get selectors recursively */
            $this->selectors[] = $this->source->getLeft();
            $this->selectors[] = $this->source->getRight();
        }
        
        return $this->selectors;
    }

    public function getMidgardStorageName()
    {
        /* TODO, this either should return one, strictly defined storage name
         * or best match should be implemented */
        return $this->storageType;
    }

    public function getMidgard2StorageNames()
    {
        $selectors = $this->getSelectors();
        $names = array();
        foreach ($selectors as $sel) {
            $names[] = NodeMapper::getMidgardName($sel->getNodeTypeName());
        }

        return $names;
    }

    private function getQuerySelectHolder()
    {
        if ($this->holder == null)
            $this->holder = new Utils\QuerySelectHolder($this);
        return $this->holder;
    }

    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    private function validateQOM()
    {
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        if (!$ntm->hasNodeType($this->nodeTypeName)) {
            throw new \PHPCR\Query\InvalidQueryException("Invalid node type '{$this->nodeTypeName}'");
        }
    }

    public function execute()
    {
        $this->validateQOM();

        $holder = $this->getQuerySelectHolder(); 

        if (count($this->getSelectors()) > 1 && count($this->getColumns()) > 1)
        {
            $select = new SQLQuerySelector($this, $holder);
            return $select->getQueryResult();
        }

        $manager = Utils\ConstraintManagerBuilder::factory($this, $holder, $this->getConstraint());
        if ($manager != null)
            $manager->addConstraint();

        $qs = $holder->getQuerySelect();
        $qs->set_constraint($holder->getDefaultConstraintGroup());

        /* Ugly hack to satisfy JCR Query.
         * We use SQL so offset without limit is RDBM provider specific.
         * In SQLite you can set negative limit which is invalid in MySQL for example. */
        if ($this->offset > 0 && $this->limit == 0) {
            $this->setLimit(9999);
        }

        if ($this->offset > 0 && $this->limit > 0) {
            $this->getQuerySelectHolder()->getQuerySelect()->set_limit($this->limit);
        }
       
        //\midgard_connection::get_instance()->set_loglevel("debug");
        //\midgard_error::debug("EXECUTE QUERY : " . $this->statement . "");
        $qs->execute();
        //\midgard_connection::get_instance()->set_loglevel("warn");
        return new QueryResult($this, $qs, $this->session);
    }

    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        //$this->getQuerySelectHolder()->getQuerySelect()->set_limit($limit);
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
        $this->getQuerySelectHolder()->getQuerySelect()->set_offset($offset);
    }
  
    public function getStatement()
    {
        if ($this->statement == null) {
            $converter = new \PHPCR\Util\QOM\QomToSql2QueryConverter(new \PHPCR\Util\QOM\Sql2Generator());
            $this->statement = $converter->convert($this);
        }
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

    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }

    public function storeAsNode($absPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

}
