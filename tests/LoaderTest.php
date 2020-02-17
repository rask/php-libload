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

    public function test_it_loads_libraries_with_directory_search() : void
    {
        $loader = new Loader();

        $ffi = $loader->fromDirectory(__DIR__ . '/lib/dylib/some')->load(__DIR__ . '/lib/testlib_1.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $this->assertSame(42, $ffi->foo(), 'Wrong lib loaded with wrong implementation');
    }

    public function test_it_loads_libraries_with_directory_search_but_fails_if_not_found() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/not found inside directory/');

        $ffi = $loader->fromDirectory(__DIR__ . '/lib/dylib/some')->load(__DIR__ . '/lib/testlib_0.h');
    }

    public function test_it_dir_search_fails_with_invalid_directory() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/No directory found/');

        $ffi = $loader->fromDirectory('/i/hope/this/one/does/not/exist/either')->load(__DIR__ . '/lib/testlib_0.h');
    }

    public function test_it_dir_search_fails_with_pathlike_ffilib() : void
    {
        $loader = new Loader();

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/Cannot use relative or absolute/');

        $ffi = $loader->fromDirectory(__DIR__ . '/lib/dylib/some')->load(__DIR__ . '/lib/testlib_8.h');
    }

    public function test_dir_search_fails_without_custom_path_being_set() : void
    {
        $loader_refl = new \ReflectionClass(Loader::class);

        $mode_prop = $loader_refl->getProperty('mode');
        $path_prop = $loader_refl->getProperty('load_root');
        $mode_prop->setAccessible(true);
        $path_prop->setAccessible(true);

        $inst = $loader_refl->newInstance();
        $mode_prop->setValue($inst, 8);
        $path_prop->setValue($inst, null);

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessageMatches('/Cannot load without/');

        $inst->load(__DIR__ . '/lib/testlib_1.h');
    }

    public function test_it_can_be_reset() : void
    {
        $loader = new Loader();

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $loader->reset();

        $this->expectException(\FFI\Exception::class);

        $ffi = $loader->load(__DIR__ . '/lib/testlib_3.h');
    }

    public function test_it_can_use_auto_reset() : void
    {
        $loader = new Loader();
        $loader->enableAutoReset();

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $this->expectException(\FFI\Exception::class);

        $ffi = $loader->load(__DIR__ . '/lib/testlib_3.h');
    }

    public function test_it_can_disable_auto_reset() : void
    {
        $loader = new Loader();
        $loader->enableAutoReset();

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $loader->disableAutoReset();

        $ffi = $loader->relativeTo(__DIR__ . '/lib')->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);

        $ffi = $loader->load(__DIR__ . '/lib/testlib_3.h');

        $this->assertInstanceOf(\FFI::class, $ffi);
    }

    public function test_it_tells_if_it_is_auto_resetting() : void
    {
        $loader = new Loader();

        $this->assertFalse($loader->isAutoResetting());

        $loader->enableAutoReset();

        $this->assertTrue($loader->isAutoResetting());
    }

    public function test_autoresetting_fires_in_case_of_load_errors_as_well() : void
    {
        $loader_refl = new \ReflectionClass(Loader::class);

        $mode_prop = $loader_refl->getProperty('mode');
        $path_prop = $loader_refl->getProperty('load_root');
        $mode_prop->setAccessible(true);
        $path_prop->setAccessible(true);

        $inst1 = $loader_refl->newInstance();
        $inst1->enableAutoReset();
        $mode_prop->setValue($inst1, 4);
        $path_prop->setValue($inst1, null);

        $inst2 = $loader_refl->newInstance();
        $inst2->enableAutoReset();
        $mode_prop->setValue($inst2, 8);
        $path_prop->setValue($inst2, null);

        $inst3 = $loader_refl->newInstance();
        $inst3->enableAutoReset();
        $mode_prop->setValue($inst3, 1337);

        $inst4 = $loader_refl->newInstance();
        $inst4->disableAutoReset();
        $mode_prop->setValue($inst4, 1337);

        try {
            $_ = $inst1->load(__DIR__ . '/lib/testlib_7.h');
        } catch (LoaderException $e) {
            $this->assertStringContainsString('Cannot load without', $e->getMessage());
        }

        $this->assertTrue($inst1->isAutoResetting());

        $ffi1 = $inst1->load(__DIR__ . '/lib/testlib_7.h');

        $this->assertInstanceOf(\FFI::class, $ffi1);

        try {
            $_ = $inst2->load(__DIR__ . '/lib/testlib_7.h');
        } catch (LoaderException $e) {
            $this->assertStringContainsString('Cannot load without', $e->getMessage());
        }

        $this->assertTrue($inst2->isAutoResetting());

        $ffi2 = $inst2->load(__DIR__ . '/lib/testlib_7.h');

        $this->assertInstanceOf(\FFI::class, $ffi2);

        try {
            $_ = $inst3->load(__DIR__ . '/lib/testlib_7.h');
        } catch (LoaderException $e) {
            $this->assertStringContainsString('invalid mode', $e->getMessage());
        }

        $this->assertTrue($inst3->isAutoResetting());

        $ffi3 = $inst3->load(__DIR__ . '/lib/testlib_7.h');

        $this->assertInstanceOf(\FFI::class, $ffi3);

        try {
            $_ = $inst4->load(__DIR__ . '/lib/testlib_7.h');
        } catch (LoaderException $e) {
            $this->assertStringContainsString('invalid mode', $e->getMessage());
        }

        try {
            $_ = $inst4->load(__DIR__ . '/lib/testlib_7.h');
        } catch (LoaderException $e) {
            // autoreset was disabled for this one, so we should meet the same error
            $this->assertStringContainsString('invalid mode', $e->getMessage());
        }
    }
}
