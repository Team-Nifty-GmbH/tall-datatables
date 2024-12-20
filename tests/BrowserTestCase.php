<?php

namespace TeamNiftyGmbH\DataTable\Tests;

use function Livewire\trigger;

class BrowserTestCase extends TestCase
{
    public static function tweakApplicationHook()
    {
        return function () {};
    }

    protected function setUp(): void
    {
        parent::setUp();

        trigger('browser.testCase.setUp', $this);
    }

    protected function tearDown(): void
    {
        trigger('browser.testCase.tearDown', $this);

        parent::tearDown();
    }
}
