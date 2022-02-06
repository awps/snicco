<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

use function is_array;
use function serialize;
use function unserialize;

/**
 * @api
 * @todo revisit this. This should be configurable to allow for exception json enconding instead of
 *       serializing
 */
final class SerializedSessionData
{

    private string $as_string;
    private DateTimeImmutable $last_activity;

    private function __construct(string $data, DateTimeImmutable $last_activity)
    {
        $this->as_string = $data;
        $this->last_activity = $last_activity;
    }

    public static function fromSerializedString(string $string, int $last_activity_as_timestamp): SerializedSessionData
    {
        if (!self::isSerializedString($string)) {
            throw new InvalidArgumentException("$string is not a valid serialized string.");
        }

        return new self(
            $string,
            (new DateTimeImmutable())->setTimestamp($last_activity_as_timestamp)
        );
    }

    private static function isSerializedString(string $data): bool
    {
        return @unserialize($data) !== false;
    }

    public static function fromArray(array $data, int $last_activity_as_timestamp): SerializedSessionData
    {
        return new self(
            serialize($data),
            (new DateTimeImmutable())->setTimestamp($last_activity_as_timestamp)
        );
    }

    public function lastActivity(): DateTimeImmutable
    {
        return $this->last_activity;
    }

    public function __toString()
    {
        return $this->asString();
    }

    public function asString(): string
    {
        return $this->as_string;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function asArray(): array
    {
        $data = unserialize($this->as_string);
        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

}