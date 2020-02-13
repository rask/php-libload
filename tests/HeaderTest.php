<?php declare(strict_types = 1);

namespace rask\Libload\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use rask\Libload\Parsing\ParseException;
use rask\Libload\Header;

/**
 * Class HeaderTest
 */
class HeaderTest extends TestCase
{
    public function test_it_parses_ffi_lib_defines() : void
    {
        $header = new Header(__DIR__ . '/lib/testlib_1.h');

        $this->assertSame('testlib1.so', $header->getFfiLib());

        $header = new Header(__DIR__ . '/lib/bindings/testlib_4.h');

        $this->assertSame('../dylib/testlib1.so', $header->getFfiLib());
    }

    public function test_it_fails_for_missing_file() : void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessageMatches('/not a file/');

        $header = new Header('/not/a/file');
    }

    public function test_it_fails_for_unreadable_file() : void
    {
        $root = vfsStream::setup('exampleDir');

        $root->addChild(new vfsStreamFile('foo.h', 0000));

        $this->expectException(ParseException::class);
        $this->expectExceptionMessageMatches('/file not readable/');

        $header = new Header($root->getChild('foo.h')->url());
    }

    public function test_no_ffi_lib() : void
    {
        $root = vfsStream::setup('exampleDir');

        $root->addChild(new vfsStreamFile('foo.h'));

        $header = new Header($root->getChild('foo.h')->url());

        $this->assertNull($header->getFfiLib());
    }

    public function test_it_can_provide_contents() : void
    {
        $header = new Header(__DIR__ . '/lib/testlib_1.h');

        $expected = <<<'HEADER'
            #define FFI_LIB "testlib1.so"

            // This header has a path that is relative to LD_LIBRARY_PATH or /lib or /usr/lib

            int foo();

            HEADER;

        $this->assertSame($expected, $header->getContents());
    }

    public function test_it_can_provide_file_path() : void
    {
        $header = new Header(__DIR__ . '/lib/testlib_1.h');

        $this->assertSame(__DIR__ . '/lib/testlib_1.h', $header->getPath());
    }
}
