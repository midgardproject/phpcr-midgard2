<?php
namespace Midgard2CR\Query;

class QueryResult implements \IteratorAggregate, \PHPCR\Query\QueryResultInterface
{
    protected $qs;
    protected $session;

    public function __construct(\midgard_query_select $qs, \Midgard2CR\Session $session)
    {
        $this->qs = $qs;
        $this->session = $session;
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
            $ret[] = new \Midgard2CR\Node($midgardNode, null, $this->session);
        }

        return new \ArrayIterator($ret);
    }

    public function getRows()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getSelectorNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function getIterator()
    {
        return $this->getNodes();
    }
}

?>
