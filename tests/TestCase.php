<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las vistas cargan los assets con @vite; en pruebas no hace falta
        // tener el frontend compilado (public/build/manifest.json).
        $this->withoutVite();
    }
}
