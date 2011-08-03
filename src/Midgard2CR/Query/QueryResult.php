<?php
namespace Midgard2CR\Query;

class QueryResult implements \IteratorAggregate, \PHPCR\Query\QueryResultInterface
{
    protected $qs;
    protected $session;
    protected $selectors;

    public function __construct(array $selectors, \midgard_query_select $qs, \Midgard2CR\Session $session)
    {
        $this->qs = $qs;
        $this->session = $session;
        $this->selectors = $selectors;
    }

    public function getColumnNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getNodes($prefetch = false)
    {
        $objects = $this->qs->list_objects();
        $ret = array();
        foreach ($objects as $midgardNode)
        {
            $node = new \Midgard2CR\Node($midgardNode, null, $this->session);
            $ret[$node->getPath()] = $node;
        }

        return new \ArrayIterator($ret);
    }

    public function getRows()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getSelectorNames()
    {
        return $this->selectors;
    }

    public function getIterator()
    {
        return $this->getNodes();
    }
}

?>
