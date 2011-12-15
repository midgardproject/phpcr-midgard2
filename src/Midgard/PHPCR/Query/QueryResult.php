<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\SQLQuery;

class QueryResult implements \IteratorAggregate, \PHPCR\Query\QueryResultInterface
{
    protected $qs;
    protected $session;
    protected $query;
    protected $rows = null;

    public function __construct(SQLQuery $query, \midgard_query_select $qs, \Midgard\PHPCR\Session $session)
    {
        $this->qs = $qs;
        $this->session = $session;
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getColumnNames()
    {
        $ret = array();
        foreach ($this->query->getSelectors() as $name)
        {
            $midgardType = NodeMapper::getMidgardName($name);
            $o = new $midgardType;
            /* PropertyDefinition requires valid node instance which is not available at this moment.
             * So get reflector directly and save resources */
            $mrp = new \midgard_reflection_property($midgardType);
            foreach ($o as $k => $v)
            {
                if (strpos($k, '-') !== false)
                {
                    /* Add column, only if property is not multiple.
                     * Can not find this either in the API or in spec, but at least 
                     * jackrabbit implementation mentions multiple property as invalid in query result. */
                    $mvp = $mrp->get_user_value($k, 'isMultiple');
                    if ($mvp != 'true')
                        $ret[] = $name . "." . NodeMapper::getPHPCRProperty($k);
                }
            }
        }

        return $ret;
    }

    public function getNodes($prefetch = false)
    {
        $objects = $this->qs->list_objects();
        $ret = array();
        foreach ($objects as $midgardNode)
        {
            $node = $this->session->getNodeRegistry()->getByMidgardNode($midgardNode);
            $ret[$node->getPath()] = $node;
        }
        return new \ArrayIterator($ret);
    }

    public function getRows()
    {
        if($this->rows == null)
        {
            $this->rows = array();
            $i = 0;
            foreach ($this->getNodes() as $path => $node)
            {
                $this->rows[] = new Row($this, $path, ++$i, $node);
            }
        }
        return new \ArrayIterator($this->rows);
    }

    public function getSelectorNames()
    {
        return $this->query->getSelectors();
    }

    public function getIterator()
    {
        return $this->getRows();
    }
}

?>
