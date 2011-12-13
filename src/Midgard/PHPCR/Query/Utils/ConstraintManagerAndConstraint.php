<?php
namespace Midgard\PHPCR\Query\Utils;

class ConstraintManagerAndConstraint extends ConstraintManager
{
    public function addConstraint()
    {
        $manager = ConstraintManagerBuilder::factory(
            $this->query, 
            $this->holder, 
            $this->constraintIface->getConstraint1());
        $manager->addConstraint();

        $manager = ConstraintManagerBuilder::factory(
            $this->query, 
            $this->holder, 
            $this->constraintIface->getConstraint2());
        $manager->addConstraint();
    }
}
