<?php declare(strict_types = 1);

namespace rask\Libload\Tests;

use PHPUnit\Framework\TestCase;
use rask\Libload\Loader;
use rask\Libload\LoaderException;

/**
 * Class LoaderTest
 *
 * @covers \rask\Libload\Loader
 */
class LoaderTest extends TestCase
{
    /**
     * @runInSeparateProcess so we get ld_library_path env running
     */
    public function test_it_loads_with_defaults() : void
    {
        $loader = new Loader();

        $ffi = $loader->load(__DIR__ . '/lib/testlib_0.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $ffi2 = $loader->load(__DIR__ . '/lib/testlib_7.h');

        $this->assertInstanceOf(\FFI::class, $ffi2);
    }

    public function test_incorrect_mode_fails() : void
    {
        $loader_refl = new \ReflectionClass(Loader::class);

        $mode_prop = $loader_refl->getProperty('mode');
        $mode_prop->setAccessible(true);

        $inst = $loader_refl->newInstance();
        $mode_prop->setValue($inst, 1337);

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/invalid mode/');

        $inst->load(__DIR__ . '/lib/testlib_1.h');
    }

    public function test_it_fails_for_headers_with_no_ffi_lib_set() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);

        $ffi = $loader->relativeToHeader()->load(__DIR__ . '/lib/testlib_9.h');
    }

    public function test_it_loads_relative_to_bindings_file() : void
    {
        $loader = new Loader();

        $ffi = $loader->relativeToHeader()->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $ffi2 = $loader->relativeToHeader()->load(__DIR__ . '/lib/bindings/testlib_4.h');

        $this->assertInstanceOf(\FFI::class, $ffi2);

        $ffi3 = $loader->relativeToHeader()->load(__DIR__ . '/lib/testlib_8.h');

        $this->assertInstanceOf(\FFI::class, $ffi3);
    }

    public function test_it_does_not_load_absolute_when_relative_wanted() : void
    {
        $loader = new Loader();

        $this->expectExceptionMessageMatches('/absolute path/');

        $ffi = $loader->relativeToHeader()->load(__DIR__ . '/lib/testlib_2.h');
    }

    public function test_it_loads_relative_to_a_custom_path() : void
    {
        $loader = new Loader();

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);
    }

    public function test_it_loads_relative_to_custom_dir_even_with_file_supplied() : void
    {
        $loader = new Loader();

        $ffi = $loader->relativeTo(__DIR__ . '/lib/testlib_3.h')->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);
    }

    public function test_it_does_not_load_absolute_when_relative_wanted_custom_path() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/absolute path/');

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_2.h');
    }

    public function test_relative_custom_load_fails_without_custom_path_being_set() : void
    {
        $loader_refl = new \ReflectionClass(Loader::class);

        $mode_prop = $loader_refl->getProperty('mode');
        $path_prop = $loader_refl->getProperty('load_root');
        $mode_prop->setAccessible(true);
        $path_prop->setAccessible(true);

        $inst = $loader_refl->newInstance();
        $mode_prop->setValue($inst, 4);
        $path_prop->setValue($inst, null);

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/Cannot load without/');

        $inst->load(__DIR__ . '/lib/testlib_1.h');
    }

    public function test_it_fails_for_headers_with_no_ffi_lib_set_custom_path() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_9.h');
    }

    public function test_it_fails_when_custom_directory_is_missing() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/directory does not exist/');

        $ffi = $loader->relativeTo('/i/hope/this/does/not/exist')->load(__DIR__ . '/lib/testlib_2.h');
    }

    public function test_it_fails_if_library_is_not_readable() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/invalid library path/');

        $ffi = $loader->relativeToHeader()->load(__DIR__ . '/lib/testlib_10.h');
    }
}
