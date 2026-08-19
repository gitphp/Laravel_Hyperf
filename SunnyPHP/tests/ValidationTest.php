<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Validation\Factory;
use SunnyPHP\Validation\ValidationException;

final class ValidationTest extends TestCase
{
    public function testPassesAndReturnsValidatedData(): void
    {
        $validated = (new Factory())->validate(
            [
                'email' => 'ada@example.com',
                'password' => 'secret12',
                'password_confirmation' => 'secret12',
                'age' => 30,
            ],
            [
                'email' => 'required|email',
                'password' => 'required|string|min:6|confirmed',
                'age' => 'required|integer|min:18',
            ],
        );

        $this->assertSame('ada@example.com', $validated['email']);
        $this->assertSame(30, $validated['age']);
    }

    public function testFailsWithErrors(): void
    {
        try {
            (new Factory())->validate(
                ['email' => 'not-an-email', 'age' => 3],
                ['email' => 'required|email', 'age' => 'integer|min:18', 'name' => 'required'],
            );
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status);
            $this->assertArrayHasKey('email', $e->errors);
            $this->assertArrayHasKey('age', $e->errors);
            $this->assertArrayHasKey('name', $e->errors);
        }
    }
}
