<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use InvalidArgumentException;
use Webmozart\Assert\Assert;

use function array_shift;
use function is_subclass_of;

/**
 * @interal
 */
final class ConditionBlueprint
{

    private bool $negated = false;

    /**
     * @var class-string<AbstractRouteCondition>
     */
    private string $class;

    private array $args;

    /**
     * @param class-string<AbstractRouteCondition>|"!" $condition_class
     */
    public function __construct(string $condition_class, array $arguments = [])
    {
        if ($condition_class === AbstractRouteCondition::NEGATE) {
            /** @var string $condition_class */
            $condition_class = array_shift($arguments);
            Assert::stringNotEmpty($condition_class);
            $this->negated = true;
        }

        if (!is_subclass_of($condition_class, AbstractRouteCondition::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    "A condition has to be an instance of [%s].\nGot [%s].",
                    AbstractRouteCondition::class,
                    $condition_class
                )
            );
        }

        $this->class = $condition_class;
        $this->args = $arguments;
    }

    /**
     * @return class-string<AbstractRouteCondition>
     */
    public function class(): string
    {
        return $this->class;
    }

    public function passedArgs(): array
    {
        return $this->args;
    }

    public function isNegated(): bool
    {
        return $this->negated;
    }

}