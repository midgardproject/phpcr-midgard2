<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\SQLQuery;

class QueryResultSelector extends QueryResult
{
    protected $result = null;
    protected $rows = null;
    protected $columns = null;
    protected $nodes = null;
    protected $selectorNames = null;
    protected $resultProperties = null;

    public function __construct(\Midgard\PHPCR\Session $session, SQLQuery $query, array $result)
    {
        $this->session = $session;
        $this->query = $query;
        $this->result = $result;
    }

    public function getQuery()
    {
        return $this->query;
    }

    private function getResultProperties()
    {
        if ($this->resultProperties != null) {
            return $this->resultProperties;
        }
    
        foreach($this->result as $selector) 
        {
            foreach ($selector as $name => $v) 
            {
                foreach ($v as $k => $props)
                {
                    $this->resultProperties[] = $props;
                }
            }
        }
        return $this->resultProperties;
    }

    public function getColumnNames()
    {
        if ($this->columns != null) {
            return $this->columns;
        }

        foreach($this->getResultProperties() as $props) 
        {
            $columnName = $props['columnName'];
            if ($columnName == null) {
                $columnName = $props['propertyName'];
            }
            $this->columns[] = $columnName;
        }
        return $this->columns;
    }

    public function getNodes($prefetch = false)
    {
        if ($this->nodes != null) {
            $this->nodes;
        }

        foreach ($this->getResultProprties() as $props)
        {
            $ret[] = $this->session->getNodeRegistry()->getByMidgardGuid($prop['midgardNodeProperty']->guid);
        }

        $this->nodes = new \ArrayIterator($ret);
        return $this->nodes;
    }

    public function getRows()
    {
        throw new \PHPCR\RepositoryException("NotImplemented");
        if ($this->rows != null) {
            return $this->rows;
        }

        $i = 0;
        foreach ($this->result as $row)
        {
            $ret[] = new RowSelector($this, );   
        }

        $this->rows = new \ArrayIterator($ret);
        return $this->rows;
    }

    public function getSelectorNames()
    {
        if ($this->selectorNames != null) {
            return $this->selectorNames;
        }
        
        foreach ($this->result as $s)
        {
            foreach ($s as $name => $v)
            {
                $this->selectorNames[] = $name;
            }
        }
        return $this->selectorNames;
    }

    public function getIterator()
    {
        return $this->getRows();
    }
}

?>
