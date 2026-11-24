<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tbo.client_id', '');
        config()->set('services.tbo.username', '');
        config()->set('services.tbo.password', '');
    }
}
