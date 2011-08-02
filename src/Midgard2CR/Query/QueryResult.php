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
        $ret = $this->qs->list_objects();
        foreach ($ret as $object)
        {
            /* TODO */
        }

        return new \ArrayIterator(array());
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
