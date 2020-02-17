# rask/libload

[![Infection MSI](https://badge.stryker-mutator.io/github.com/rask/php-libload/master)](https://infection.github.io)

A small library to help in loading FFI libraries in a straight-forward manner.

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
-   Load libraries relative to the header file
-   Load libraries relative to a custom path
-   Load libraries by searching for them inside a directory tree

## Example

```php
<?php

use rask\Libload\Loader;
use rask\Libload\LoaderException;
use rask\Libload\Parsing\ParseException;

$loader = new Loader();

try {
    $ffi = $loader->relativeTo('/path/to/libs')->load(__DIR__ . '/libs/my_lib.h');
} catch (LoaderException | ParseException $e) {
    // log it or something
}

assert($ffi instanceof \FFI);
```

Where `my_lib.h` contains

```h
#define FFI_LIB "my_lib.so"

... definitions here ...
```

The example above instantiates a new `Loader`, and then we instruct it to load a header file with the relative lookup mode.

So, if a dynamic library exists in `/path/to/libs/my_lib.so` it should get loaded and return a regular PHP `\FFI` instance for us.

## How it works

In a nutshell:

The loader reads your header file, parses the `FFI_LIB` definitions, and then just uses `\FFI::cdef()` with the header file and a proper path to the library you actually want to load.

A little hacky, yes.

## Installation

    $ composer require rask/libload

## Usage

### Using PHP default logic

To use the default `\FFI::load()` logic (aka `dlopen(3)` logic), you can use the loader as follows:

```php
<?php

// ... setup autoloads etc ...

$loader = new \rask\Libload\Loader();

$ffi = $loader->load('./path/to/header.h');

assert($ffi instanceof \FFI);
```

### Loading a library relative to header

If your header file defines an `FFI_LIB` that is located relative to the header file itself, you can use

```php
<?php

// ... setup autoloads etc ...

$loader = new \rask\Libload\Loader();

$ffi = $loader->relativeToHeader()->load('./path/to/header.h');

assert($ffi instanceof \FFI);
```

You header file might contain

```h
#define FFI_LIB "./subdir/lib.so"
```

So `$loader` would look for the library in `./path/to/header/subdir/lib.so`.

Using this method fails if the `FFI_LIB` definition contains an absolute path.

This method disables the default `dlopen(3)` logic.

### Loading a library relative to a custom path

If your library is located in a specific directory, you can load it using

```php
<?php

// ... setup autoloads etc ...

$loader = new \rask\Libload\Loader();

$ffi = $loader->relativeTo('/my/awesome/libs')->load('./path/to/header.h');

assert($ffi instanceof \FFI);
```

You header file might contain

```h
#define FFI_LIB "./subdir/lib.so"
```

So `$loader` would look for the library in `/my/awesome/libs/subdir/lib.so`.

Using this method fails if the `FFI_LIB` definition contains an absolute path.

This method disables the default `dlopen(3)` logic.

### Loading a library by searching inside a directory

If you have a directory tree with an arbritary structure but know the name of the library, you can search for it using

```php
<?php

// ... setup autoloads etc ...

$loader = new \rask\Libload\Loader();

$ffi = $loader->fromDirectory('/my/awesome/libs')->load('./path/to/header.h');

assert($ffi instanceof \FFI);
```

And if your directory tree is as follows

```txt
/
    my/
        awesome/
            libs/
                subdir1/
                    subdir2/
                        lib5.so
                    lib3.so
                    lib4.so
                lib1.so
                lib2.so
```

And if your header defines

```h
#define FFI_LIB "lib4.so"
```

Then `$loader` will find `/my/awesome/libs/subdir1/lib4.so`. The search will be slower in larger directories.

This method disables the default `dlopen(3)` logic.

### Multiple library loading and resetting the loader

If you want to use the same loader instance for all kinds of loading needs across your application, you may need to reset it depending on how you want to load various libraries.

You can use resetting as such:

```php
<?php

// ... setup autoloads etc ...

$loader = new \rask\Libload\Loader();

// Load using default logic
$ffi1 = $loader->load('mylib1.h');

// Load using relative to header
$ffi2 = $loader->reset()->relativeToHeader()->load('mylib2.h');

// Search a directory for the last one
$ffi3 = $loader->reset()->fromDirectory('/my/directory/path')->load('mylib3.h');
```

If you have loads of variance in loading types, you can configure the instance to reset after each load:

```php
<?php

$loader = new \rask\Libload\Loader();

$loader->enableAutoReset();

assert($loader->isAutoResetting() === true);

// Now the instance will reset after each call to `load()`

$loader->disableAutoReset();

assert($loader->isAutoResetting() === false);

// Returned to normal manual operation for resetting
```

If you don't use resetting, you can keep using the same loading method for multiple libraries:

```php
<?php

$loader = new \rask\Libload\Loader();

$loader = $loader->relativeTo('/my/libs');

$ffi1 = $loader->load('lib1.h');
$ffi2 = $loader->load('lib2.h');
$ffi3 = $loader->load('lib3.h');
```

## Todo

-   More ways to load libraries?
-   Make it work with headers that provide `FFI_SCOPE` (i.e. make it work with preloading)
-   Make this package useless, by pestering PHP core devs to add this functionality to the PHP core FFI implementation

## Notes

This package might be useless if you do not intend to write FFI code that is to be distributed as a package or something. Also this might be overkill if you can load your FFI instances using pure `\FFI::cdef()` instead.

Currently FFI library loading in opcache preloading contexts is confusingly documented, so this package waits for confirmations on how to actually preload FFI libraries in production before making commitments towards preloading-compatibility. Use this for CLI apps for now.

## Contributing

Development requirements apart from a PHP CLI installation that supports FFI, you need to have `gcc` and `make` available, and being able to compile on a system that produces Linux shared object binaries (`*.so`). This means you probably cannot run tests on Windows or Mac right now. Some tests require you to have `/lib/x86_64-linux-gnu/libc.so.6` available in your system.

We need `gcc` and `make` so we can build a shared library for testing purposes when running PHPUnit.

Before sending code as a pull request:

-   Try to add tests for your changes (`composer test`), or ask for help from the maintainers with it
-   Run linting and static analysis against your code before commiting (`composer lint` and `composer stan`)

If you have problems using the package, you can raise issues. Documentation and cleanup contributions very welcome. Feature requests are OK as long as they're not too far from the core purpose of this package: making loading FFI libraries a little simpler/easier/saner/etc.

If you have any questions about how to contribute, you can create an issue and ask for pointers.

## License

MIT License, see [LICENSE.md](./LICENSE.md).
