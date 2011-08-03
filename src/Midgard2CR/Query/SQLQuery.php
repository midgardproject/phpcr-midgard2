<?php
namespace Midgard2CR\Query;

class SQLQuery implements \PHPCR\Query\QueryInterface
{
    protected $session = null;
    protected $qs = null;
    protected $statement = null;

    protected $storageType = null;
    
    public function __construct (\Midgard2CR\Session $session, $statement)
    {
        $this->session = $session;
        $this->statement = $statement;
        $this->QBFromStatement();
    }

    private function QBFromStatement()
    {
        $scanner = new \PHPCR\Util\QOM\Sql2Scanner($this->statement);
        do {
            $token = $scanner->fetchNextToken(); 
            if ($token == 'FROM')
            {
                $type = $scanner->fetchNextToken();
            }
        } while ($token != '');

        try 
        {
            $this->storageType = \MidgardNodeMapper::getMidgardName(str_replace(array('[', ']'), '', $type));
        }
        catch (\midgard_error_exception $e)
        {
            throw new \PHPCR\RepositoryException("Invalid type '{$this->storageType}'. " . $e->getMessage());
        }

        $storage = new \midgard_query_storage('midgard_node');
        $this->qs = new \midgard_query_select($storage);
        if ($this->storageType != null)
        {
            $this->qs->set_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property('typename'),
                    '=',
                    new \midgard_query_value($this->storageType)
                )
            );
        }
    } 

    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function execute()
    {
        $this->qs->execute();
        return new QueryResult($this->qs, $this->session);
    }

    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function setLimit($limit)
    {
        $this->qs->set_limit($limit);
    }
   
    public function setOffset($offset)
    {
        $this->qs->set_offfset($offset);
    }
  
    public function getStatement()
    {
        return $this->statement;
    }
     
    public function getLanguage()
    {
        return \PHPCR\Query\QueryInterface::JCR_SQL2; 
    }
     
    public function getStoredQueryPath()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }
  
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

}
