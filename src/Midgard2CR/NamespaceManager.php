<?php
namespace Midgard2CR;

class NamespaceManager 
{
    protected $session = null;

    public function __construct(\Midgard2CR\Session $session)
    {
        $this->session = $session;    
        $this->registry = $this->builtins;
    }

    /**
     * Checks whethere given string is known prefix.
     */
    public function isPrefix($string)
    {

    }

    /**
     * From given string get known prefix.
     */ 
    public function getPrefix($string)
    {

    }

    /**
     * From given string get prefix tokens.
     */ 
    public function getPrefixTokens($string)
    {

    }

    /**
     * From given string get prefix with statement 
     * */
    public function getPrefixWithStatement($string)
    {

    }

    /** 
     * Check whether given string is known uri
     */ 
    public function isUri($string)
    {

    }

    /** 
     * Get known uri from given string
     */ 
    public function getUri($string)
    {

    }

    /**
     * Get uri tokens
     */ 
    public function getUriTokens($string)
    {

    }

    /**
     * Get uri with statement
     */ 
    public function getUriWithStatement($string)
    {

    }
}

?>
