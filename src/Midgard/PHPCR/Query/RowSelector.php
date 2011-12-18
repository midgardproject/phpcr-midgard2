<?php

namespace Midgard\PHPCR\Query;

class RowSelector extends Row
{
    protected $results = null;
    protected $session = null;

    public function __construct(\Midgard\PHPCR\Query\QueryResultSelector $qr, $score, array $results)
    {
        $this->queryResult = $qr;
        $this->score = $score;
        $this->results = $results;
    }

    private function getSession()
    {
        if ($this->session != null) {
            return $this->session;
        }

        $this->session = $this->queryResult->getQuery()->getSession();
        return $this->session;
    }

    public function getNode($selectorName = null)
    {
        if (!isset($this->results[$selectorName])) {
            throw new \PHPCR\RepositoryException ("{$selectorName} not set for this row");
        }

        return $this->getSession()->getNodeRegistry()->getByMidgardGuid($this->results[$selectorName]['midgardNodeProperty']->guid);
    }

    public function getPath($selectorName = null)
    {
        return $this->getNode($selectorName)->getPath();
    }

    public function getScore($selectorName = null)
    {
        /* FIXME */
        return (float) $this->score;
    }

    public function getValue($columnName)
    {
        $parts = explode('.', $columnName);
        $selectorName = $parts[0];
        $columnName = $parts[1];

        if (!isset($this->results[$selectorName])
            || !isset($this->results[$selectorName][$columnName])) {
            throw new \PHPCR\ItemNotFoundException("Column {$columnName} not found");
        }

        return $this->results[$selectorName][$columnName]['midgardNodeProperty']->value;
    }

    private function populateValues()
    {
        if ($this->values != null) {
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
}

?>
