# rask/libload

A small library to help in loading FFI libraries in a straight forward manner.

## Rationale

The vanilla methods of loading dynamic libraries via PHP FFI are simple and understandable:

-   `\FFI::cdef()` allows you to take in a library, and write the library header definition on the fly.
-   `\FFI::load()` allows you to take in a library header, inside which resides `FFI_LIB` that points to a dynamic library to load.

This is all fine and well, apart from one thing:

You're locked tight into the logic of `dlopen(3)`, which is a C function to load dynamic libraries. In essence this means the following:

1.   You cannot use relative paths for `FFI_LIB`, as those operate on current working directory, which can be whatever
2.   You cannot use absolute paths, as all software is runnable anywhere on any system in any path
3.   You cannot rely on `LD_LIBRARY_PATH` as it cannot be altered at runtime, and you would require users of your code to set it up properly
4.   You cannot use `/lib` or `/usr/lib`, as that would be senseless pollution on the user's system, not to speak of requiring admin privileges

In smaller projects with limited audiences these limitations might not matter. Or maybe the library you intend to use is a well-known and often preinstalled one (e.g. `libc`). But once you want to distribute public code that relies on a custom built FFI library, you're in trouble.

So, the only real reason this library exists is to bypass these limitations, and allow you to load a dynamic library in a few different ways.

## Features

-   Load libraries the `dlopen(3)` way
-   Load libraries according to certain ways of `dlopen(3)` (e.g. allow `LD_LIBRARY_PATH` but disallow others)
-   Disable loading libraries using the `dlopen(3)` logic
-   Load libraries from specific paths, e.g. relative to the current PHP file or similar.

## Example

```php
<?php

use rask\Libload\Loader;

$loader = new Loader();

$loader->disableDlopenLogic();
$loader->addLibraryPath('/var/app/path/to/libs');

try {
    $ffi = $loader->load(__DIR__ . '/libs/my_lib.h');
} catch (\rask\Libload\LibloadException $e) {
    // log it or something
}

assert($ffi instanceof \FFI);
```

Where `my_lib.h` contains

```h
#define FFI_LIB "my_lib.so"

... definitions here ...
```

The example above instantiates a new `Loader`, and on that loader we disable the default logic of PHP and `dlopen(3)`. Then we pass in a library path (directory) where the loader should look for dynamic libraries in. Our header file `my_lib.h` contains an `FFI_LIB` definition that is not of the path type, and it will be used as a relative path once we disable the `dlopen(3)` default logic.

So, if a dynamic library exists in `/var/app/path/to/libs/my_lib.so` it should get loaded and return a regular PHP `\FFI` instance for us.

## How it works

In a nutshell:

The loader reads your header file, parses the `FFI_LIB` definitions, and then just uses `\FFI::cdef()` with the header file and a proper path to the library you actually want to load.

A little hacky, yes.

## Installation

    $ composer require rask/libload

## Usage

> to be written

## Todo

-   Make this package useless, by pestering PHP core devs to add this functionality to the PHP core FFI implementation

## Notes

Currently FFI library loading in opcache preloading contexts is limited to a `php.ini` setting called `ffi.preload`, meaning all bindings you want to have available during preloading must be defined there. This package does not change that, and currently this package targets CLI projects mainly.

## Contributing

Development requirements apart from a PHP CLI installation that supports FFI, you need to have `gcc` and `make` available, and being able to compile on a system that produces Linux shared object binaries (`*.so`). This means you probably cannot run tests on Windows or Mac right now.

We need `gcc` and `make` so we can build a shared library for testing purposes when running PHPUnit.

Before sending code as a pull request:

-   Try to add tests for your changes (`composer test`), or ask for help from the maintainers with it
-   Run linting and sttatic analysis against your code before commiting (`composer lint` and `composer stan`)

If you have problems using the package, you can raise issues. Documentation and cleanup contributions very welcome. Feature requests are OK as long as they're not too far from the core purpose of this package: making loading FFI libraries a little simpler/easier/saner/etc.

If you have any questions about how to contribute, you can create an issue and ask for pointers.

## License

MIT License, see [LICENSE.md](./LICENSE.md).
