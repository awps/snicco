<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Snicco\Component\HttpRouting\Reflection;
use Traversable;

final class ReflectionTest extends TestCase
{

    /**
     * @test
     */
    public function test_isInterface_with_object(): void
    {
        $subject = new TestSubject();

        $this->assertTrue(Reflection::isInterface($subject, Countable::class));

        $this->assertFalse(Reflection::isInterface($subject, JsonSerializable::class));
    }

    /**
     * @test
     */
    public function test_is_interface_with_class_string(): void
    {
        $this->assertTrue(Reflection::isInterface(TestSubject::class, Countable::class));

        $this->assertFalse(Reflection::isInterface(TestSubject::class, JsonSerializable::class));
    }

    /**
     * @test
     */
    public function test_with_extended_interface(): void
    {
        $this->assertTrue(Reflection::isInterface(new TestTraversable(), Iterator::class));
        $this->assertFalse(Reflection::isInterface(new TestTraversable(), ArrayAccess::class));
        $this->assertTrue(Reflection::isInterface(new TestTraversable(), Traversable::class));

        $this->assertTrue(Reflection::isInterface(TestTraversable::class, Iterator::class));
        $this->assertFalse(Reflection::isInterface(TestTraversable::class, ArrayAccess::class));
        $this->assertTrue(Reflection::isInterface(TestTraversable::class, Traversable::class));
    }

    /**
     * @test
     */
    public function test_true_with_interface_string(): void
    {
        $this->assertTrue(Reflection::isInterface(ContainerInterface::class, ContainerInterface::class));
        $this->assertFalse(Reflection::isInterface(ContainerInterface::class, ArrayAccess::class));
    }

    /**
     * @test
     */
    public function test_false_for_missing_class(): void
    {
        $this->assertFalse(Reflection::isInterface('Foo', ArrayAccess::class));
    }

    /**
     * @test
     */
    public function test_exception_for_bad_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Interface [Foo] does not exist.');

        Reflection::isInterface(TestTraversable::class, 'Foo');
    }

    /**
     * @test
     */
    public function test_with_child_interface_as_string(): void
    {
        $this->assertTrue(Reflection::isInterface(Iterator::class, Traversable::class));
        $this->assertFalse(Reflection::isInterface(Traversable::class, Iterator::class));
    }

    /**
     * @test
     */
    public function test_firstParameterType_with_closure(): void
    {
        $this->assertSame(
            'string',
            Reflection::firstParameterType(function (string $foo): void {
                //
            })
        );
    }

    /**
     * @test
     */
    public function test_firstParameterType_with_class_string_uses_constructor(): void
    {
        $this->assertSame('string', Reflection::firstParameterType(ClassWithConstructor::class));
    }


}

class NoConstructor
{

}

class ClassWithConstructor
{

    public function __construct(string $foo)
    {
    }

    public function someMethod(string $foo, string $bar): void
    {
    }

}

class TestSubject implements Countable
{

    public function count(): int
    {
        return 0;
    }

}

class TestTraversable implements Iterator
{

    #[ReturnTypeWillChange]
    /**
     * @return void
     */
    public function current()
    {
    }

    public function next(): void
    {
    }

    #[ReturnTypeWillChange]
    /**
     * @return void
     */
    public function key()
    {
    }

    public function valid(): bool
    {
        return true;
    }

    public function rewind(): void
    {
        //
    }

}