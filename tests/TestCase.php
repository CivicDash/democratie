<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Désactive le CSRF pour éviter les 419 sur les tests Feature.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
