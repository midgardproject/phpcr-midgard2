<?php
namespace Midgard\PHPCR\Transaction;

use PHPCR\Transaction\UserTransactionInterface;
use midgard_transaction;

class Transaction implements UserTransactionInterface
{
    private $midgardTransaction = null;
    private $timeout = 0;
    private $inTransaction = false;
    private static $instance = null;

    private function __construct()
    {
        $this->midgardTransaction = new midgard_transaction();
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Transaction();
        }
        return self::$instance;
    }

    public function begin()
    {
        if ($this->inTransaction) {
            return;
        }
        $this->midgardTransaction->begin();
        $this->inTransaction = true;
    }

    public function commit()
    { 
        $this->midgardTransaction->commit();
        $this->inTransaction = false;
    }

    public function inTransaction()
    {
        return $this->inTransaction;
    }

    public function rollback()
    {
        $this->midgardTransaction->rollback();
        $this->inTransaction = false;
    }

    public function setTransactionTimeout($seconds = 0)
    {
        $this->timeout = 0;
    }
}
