<?php declare(strict_types = 1);

namespace rask\Libload\Tests;

use PHPUnit\Framework\TestCase;
use rask\Libload\Loader;

/**
 * Class LoaderTest
 */
class LoaderTest extends TestCase
{
    public function test_it_can_be_instantiated() : void
    {
        $loader = new Loader();

        $this->assertInstanceOf(Loader::class, $loader);
    }
}
