<?php

namespace Midgard2CR\Query;

class Row implements \Iterator, \PHPCR\Query\RowInterface
{
    protected $score = null;
    protected $path = null;
    protected $queryResult = null;
    protected $node = null;
    protected $position = 0;
    protected $values = null; 
    protected $indexes = null;

    public function __construct(\Midgard2CR\Query\QueryResult $qr, $path, $score, \Midgard2CR\Node $node)
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
        return $this->score;
    }

    public function getValue($columnName)
    {
        if (strpos($columnName, '.'))
        {
            $parts = explode('.', $columnName);
            $columnName = $parts[1];
        }
        else 
        {
            if (strpos($columnName, 'path'))
            {
                return $this->getPath();
            }
            else if (strpos($columnName, 'score'))
            {
                return $this->getScore();
            }
        }
        return $this->node->getPropertyValue($columnName);
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
