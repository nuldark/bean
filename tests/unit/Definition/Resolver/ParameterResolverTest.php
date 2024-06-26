<?php

/*
 * This file is part of the nuldark/bean.
 *
 * Copyright (C) 2024 Dominik Szamburski
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Bean\Tests\Unit\Definition\Resolver;

use Bean\Container;
use Bean\ContainerInterface;
use Bean\Definition\Exception\DependencyException;
use Bean\Definition\Resolver\ParameterResolver;
use Bean\Definition\Resolver\ParameterResolverInterface;
use Bean\Tests\Unit\Fixtures\ClassWithDefaultValue;
use Bean\Tests\Unit\Fixtures\ClassWithDependency;
use Bean\Tests\Unit\Fixtures\ExtendedSampleClass;
use Bean\Tests\Unit\Fixtures\SampleClass;
use Bean\Tests\Unit\Fixtures\TypedClass;
use Bean\Tests\Unit\Fixtures\UntypedClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ParameterResolver::class)]
class ParameterResolverTest extends TestCase
{
    private MockObject|ContainerInterface $container;
    private ParameterResolverInterface $resolver;

    public function setUp(): void
    {
        $this->container = $this->createMock(
            Container::class
        );

        $this->resolver = new ParameterResolver($this->container);
    }

    public function testReturnsEmptyArrayIfReflectionMethodIsNull(): void
    {
        self::assertSame([], $this->resolver->resolveParameters());
    }

    public function testThrowsExceptionIfParameterIsNotDefinedOrGuessable(): void
    {
        self::expectException(DependencyException::class);
        self::expectExceptionMessage('Parameter `$variable` has no value defined or guessable.');

        $reflection = new ReflectionClass(ClassWithDependency::class);

        $this->resolver->resolveParameters($reflection->getConstructor());
    }

    public function testGetParameterDefaultValue(): void
    {
        $reflection = new ReflectionClass(ClassWithDefaultValue::class);
        $parameters = $this->resolver->resolveParameters($reflection->getConstructor());

        self::assertSame(
            'default',
            $parameters[0]
        );
    }

    public function testGetParameterFromPassedParameters(): void
    {
        $reflection = new ReflectionClass(ExtendedSampleClass::class);
        $parameters = $this->resolver->resolveParameters(
            $reflection->getConstructor(),
            ['name' => 'john']
        );

        self::assertNotEmpty($parameters);
        self::assertCount(1, $parameters);
        self::assertSame('john', $parameters[0]);
    }

    public function testGetParameterFromContainer(): void
    {
        $this->container->expects(self::once())
            ->method('make')
            ->with(SampleClass::class, [])
            ->willReturn(new SampleClass());

        $reflection = new ReflectionClass(TypedClass::class);
        $parameters = $this->resolver->resolveParameters($reflection->getConstructor());

        self::assertCount(1, $parameters);
        self::assertInstanceOf(SampleClass::class, $parameters[0]);
    }
}
