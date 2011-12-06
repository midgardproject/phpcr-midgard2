<?php
namespace Midgard\PHPCR\Query\Utils;
use Midgard\PHPCR\Query\SQLQuery;

class ConstraintManagerComparison extends ConstraintManager
{
    public function addConstraint()
    {
        $propertyStorage = $this->holder->getPropertyStorage();
        $this->holder->getQuerySelect()->add_join(
            'INNER',
            new \midgard_query_property('id'),
            new \midgard_query_property('parent', $propertyStorage)
        );
        $constraint = new \midgard_query_constraint(
            new \midgard_query_property("title", $propertyStorage),
            "=",
            new \midgard_query_value ($this->constraintIface->getOperand1()->getPropertyName())
        );
        $this->holder->getDefaultConstraintGroup()->add_constraint($constraint);

        $constraint = new \midgard_query_constraint(
            new \midgard_query_property("value", $propertyStorage),
            "=",
            new \midgard_query_value ($this->removeQuotes($this->constraintIface->getOperand2()->getLiteralValue()))
        );
        $this->holder->getDefaultConstraintGroup()->add_constraint($constraint);
    }
}
