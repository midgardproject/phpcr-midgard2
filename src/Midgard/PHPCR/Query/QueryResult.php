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
    protected $ordered = false;
    protected $nodes = null;

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
        foreach ($this->getSelectorNames() as $name)
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

    protected function orderResult()
    {
        if ($this->ordered == true) {
            return;
        }

        $orderings = $this->query->getOrderings();
        if (empty($orderings)) {
            $this->ordered = true;
            return;
        }

        $properties = array();

        foreach ($orderings as $ordering)
        {
            if (!($ordering->getOperand() instanceOf \Midgard\PHPCR\Query\QOM\PropertyValue)) {
                throw new \PHPCR\RepositoryException(get_class($ordering->getOperand()) ." operand not supported");
            }
            $properties[$ordering->getOperand()->getPropertyName()] = $ordering->getOrder();
        }

        $tmpResult = $this->nodes;
        /* Foreach orderings' property and foreach Node, associate property -> node */
        foreach ($properties as $property => $order)
        {
            foreach ($tmpResult as $path => $n) 
            {
                try {
                    $v = $n->getPropertyValue($property);
                } catch (\PHPCR\PathNotFoundException $e) {
                    $v = null;
                }
                $tmpOrder[$n->getPath()] = $v;
            }

            /* Sort by given property and order type */
            if ($order == 'jcr.order.ascending') {
                asort($tmpOrder, SORT_STRING);
            } else {
                arsort($tmpOrder, SORT_STRING);
            }

            foreach ($tmpOrder as $path => $pv) {
                $tmp[$path] = $tmpResult[$path];
            } 
            $tmpResult = $tmp;
        }

        $this->nodes = new \ArrayIterator($tmpResult);
        $this->ordered = true;
    }

    public function getNodes($prefetch = false)
    {
        if ($this->nodes != null) {
            return $this->nodes;
        }

        $objects = $this->qs->list_objects();
        $ret = array();
        foreach ($objects as $midgardNode)
        {
            $node = $this->session->getNodeRegistry()->getByMidgardNode($midgardNode);
            $ret[$node->getPath()] = $node;
        }
        $this->nodes = new \ArrayIterator($ret);
        $this->orderResult();
        return $this->nodes;
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
        $names = array();
        foreach ($this->query->getSelectors() as $selector) 
        {
            $names[] = $selector->getNodeTypeName();
        }
        return $names;
    }

    public function getIterator()
    {
        return $this->getRows();
    }
}

?>
