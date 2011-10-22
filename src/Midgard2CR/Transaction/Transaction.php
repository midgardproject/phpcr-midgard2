<?php

namespace Midgard\PHPCR\Transaction;

class Transaction implements \PHPCR\Transaction\UserTransactionInterface
{
    private $midgardTransaction = null;
    private $timeout = 0;

    public function __construct()
    {
        $this->midgardTransaction = new \midgard_transaction();
    }

    public function begin()
    {
        $this->midgardTransaction->begin();
    }

    public function commit()
    {
        $this->midgardTransaction->commit();
    }

    public function inTransaction()
    {
        /* TODO */
    }

    public function rollback()
    {
        $this->midgardTransaction->rollback();
    }

    public function setTransactionTimeout($seconds = 0)
    {
        $this->timeout = 0;
    }
}
?>
