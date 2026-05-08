<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Adapters;

use Concept\Core\Components\Validator\Contracts\RuleInterface;
use Rakit\Validation\MissingRequiredParameterException;
use Rakit\Validation\Rule as RakitRule;

class RakitRuleAdapter extends RakitRule
{
    public function __construct(protected readonly RuleInterface $customRule)
    {
        $this->setMessage($this->customRule->getMessage());
        $this->fillableParams = $this->customRule->getFillable();
    }

    /**
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->customRule->getRequired());
        $this->customRule->setParameters($this->params);

        return $this->customRule->passes($value);
    }
}