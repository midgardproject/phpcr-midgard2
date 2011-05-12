<?php
namespace Midgard2CR;

class NamespaceManager 
{
    protected $registery = null;

    public function __construct(\Midgard2CR\NamespaceRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Get NamespaceRegistry object associated with manager
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * Checks whethere given string is known prefix.
     */
    public function isPrefix($string)
    {
        $prefix = $this->registry->getPrefix($string);
        if ($prefix == false)
        {
            return false;
        }
        return true;
    }

    /**
     * From given string get known prefix.
     *
     * Return 'mgd' from:
     * 'mgd:Person'
     * 'http://www.midgard-project.org/repligard/1.4/Person'
     */ 
    public function getPrefix($string)
    {
        $tokens = $this->getPrefixTokens($string);
        if ($tokens[0] != null)
            return $tokens[0];
        return null;
    }

    /**
     * From given string get prefix tokens.
     *
     * Return array('mgd', 'Person') from:
     * 'mgd:Person'
     * 'http://www.midgard-project.org/repligard/1.4/Person'
     */ 
    public function getPrefixTokens($string)
    {
        $tokens = array (null, null);

        /* Registered prefix */
        if ($this->isPrefix($string))
        {
            $tokens[0] = $string;
            return $tokens;
        }
  
        /* Given string is uri */
        if (substr_count($string, '/') > 0) {
            $uri_tokens = $this->getUriTokens($string);
            if ($uri_tokens[0] != null) {
                $tokens[0] = $this->registry->getPrefix($uri_tokens[0]);
                $tokens[1] = $uri_tokens[1];
            }
            return $tokens;
        }

        /* Given string is prefix statement */
        if (substr_count($string, ':') > 0) 
        {
            $spltd = explode($string, ':');
            if ($this->isPrefix($spltd[0]) == true) {
                $tokens[0] = $spltd[0];
                $tokens[1] = $spltd[1];
            }
        }
        return $tokens;
    }

    /**
     * From given string get prefix with statement.
     *
     * Return 'mgd:Person' from
     * 'http://www.midgard-project.org/repligard/1.4/Person'
     */
    public function getPrefixWithStatement($string)
    {
        $tokens = $this->getPrefixTokens($string);
        if ($tokens[0] != null && $tokens[1] != null)
            return $tokens[0] . ":" . $tokens[1];
        return null;
    }

    /** 
     * Check whether given string is known uri
     */ 
    public function isUri($string)
    {
        $uri = $this->registry->getUri($string);
        if ($uri == false)
            return false;
        return true;
    }

    /** 
     * Get known uri from given string
     */ 
    public function getUri($string)
    {
        $tokens = $this->getUriTokens($string);
        if ($tokens[0] == null)
            return null;
        return $tokens[0];
    }

    /**
     * Get uri tokens
     */ 
    public function getUriTokens($string)
    {
        $tokens = array(null, null);

        /* Given string is uri */
        if ($this->isUri($string))
        {
            $tokens[0] = $string;
            return $tokens;
        }

        /* Assume, given string is '#' terminating */
        if (substr_count($string, '#', 1)) {
            $spltd = explode($string, '#');
            if ($spltd[0] != null)
                if ($this->isUri($spltd[0] . '#')) {
                    $tokens[0] = $spltd[0] . '#';
                    $tokens[1] = $spltd[1];
                }
            return $tokens;
        }
        
        /* prefix or unknown string, try to get prefix */
        if (substr_count($string, '/') == 0) {
            $prefix_tokens = $this->getPrefixTokens($string);
            if ($prefix_tokens[0] != null) {
                $tokens[0] = $this->getUri($prefix_tokens[0]);
                $tokens[1] = $prefix_tokens[1];
            }
            return $tokens;
        }
        
        /* check uri */
        $rs = strrch ($string, '/');
        $uri = substr($string, 0, (strlen($string) - strlen($rs)) + 1);
        if ($this->isUri($uri) == true) {
            $tokens[0] = $uri;
            $tokens[1] = substr($rs, 1, -1);
        }

        return $tokens;
    }

    /**
     * Get uri with statement
     */ 
    public function getUriWithStatement($string)
    {
        $tokens = $this->getUriTokens($string);
        if ($tokens[0] != null && $tokens[1] != null)
            return $tokens[0] . $tokens[1];
        return null;
    }
}

?>
