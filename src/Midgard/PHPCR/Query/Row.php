<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Node;

class Row implements \Iterator, \PHPCR\Query\RowInterface
{
    protected $score = null;
    protected $path = null;
    protected $queryResult = null;
    protected $node = null;
    protected $position = 0;
    protected $values = null; 
    protected $indexes = null;

    public function __construct(\Midgard\PHPCR\Query\QueryResult $qr, $path, $score, Node $node)
    {
        $this->queryResult = $qr;
        $this->path = $path;
        $this->score = $score;
        $this->node = $node;
    }

    public function getNode($selectorName = null)
    {
        return $this->node;
    }

    public function getPath($selectorName = null)
    {
        return $this->path;
    }

    public function getScore($selectorName = null)
    {
        return (float) $this->score;
    }

    public function getValue($columnName)
    {
        if (strpos($columnName, '.')) {
            $parts = explode('.', $columnName);
            $columnName = $parts[1];
        }
        
        $definedColumns = $this->queryResult->getQuery()->getColumns();
        foreach ($definedColumns as $column) {
            $tmp = $column->getPropertyName();
            if (strpos($tmp, "[") !== false) {
                $tmp = substr($tmp, 1, -1); /* Remove [] */ /* "Illegal offset type " */
            }
            if ($tmp == $columnName) {
                if (!$this->node->hasProperty($columnName)) {
                    return null;
                }
            }
        }

        try {
            $ret = $this->node->getPropertyValue($columnName);
            if ($ret instanceof Node) {
                return $ret->getIdentifier();
            }
            return $ret;
        } 
        catch (\PHPCR\PathNotFoundException $e)
        {
            throw new \PHPCR\ItemNotFoundException($e->getMessage());
        }
    }

    private function populateValues()
    {
        if ($this->values != null)
        {
            return;
        }

        $this->values = array();
        $this->indexes = array();
        $columns = $this->queryResult->getColumnNames();
        foreach ($columns as $name)
        {
            $this->values[$name] = $this->getValue($name);
            $this->indexes[$this->position] =& $name;
            $this->position++;
        }
    }

    public function getValues()
    {
        $this->populateValues();
        return $this->values;
    }

    /* Iterator implementation */

    public function rewind()
    {
        $this->populateValues(); 
        $this->position = 0;
    }

    public function current()
    { 
        return $this->values[$this->indexes[$this->position]];
    }

    public function key()
    {
        return $this->indexes[$this->position];
    }

    public function next()
    { 
        ++$this->position;
    }

    public function valid()
    { 
        return isset($this->indexes[$this->position]);
    }
}

?>
