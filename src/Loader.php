<?php declare(strict_types = 1);

namespace rask\Libload;

/**
 * Class Loader
 */
final class Loader
{
    /**
     * Default mode, as in the dlopen(3) logic.
     */
    protected const MODE_DEFAULT = 1;

    /**
     * Mode for loading libraries relative to the header file where their path is defined.
     */
    protected const MODE_REL_HEADER_FILE = 2;

    /**
     * Mode for loading libraries relative to a set custom path.
     */
    protected const MODE_REL_CUSTOM_PATH = 4;

    /**
     * Mode for loading libraries using recursive directory search.
     */
    protected const MODE_DIR_SEARCH = 8;

    /**
     * Loading mode.
     */
    protected int $mode = self::MODE_DEFAULT;

    /**
     * When loading relative to a custom path, this is the root where the file is looked from.
     */
    protected ?string $load_root = null;

    /**
     * Should the instance automatically reset after each call to self::load()?
     */
    protected bool $auto_reset = false;

    /**
     * Load FFI instance as defined in bindings.
     *
     * @throws LoaderException If loading fails.
     */
    public function load(string $header_path) : \FFI
    {
        $header = new Header($header_path);

        if ($header->getFfiLib() === null) {
            throw new LoaderException('No FFI_LIB defined in header `' . $header_path . '`');
        }

        /** @var ?\FFI $ffi */
        $ffi = null;

        switch ($this->mode) {
            case self::MODE_DIR_SEARCH:
                if ($this->load_root === null) {
                    $this->maybeAutoReset();

                    throw new LoaderException('Cannot load without directory search root set');
                }

                $ffi = $this->loadBySearchingDirectory($header, $this->load_root);

                break;
            case self::MODE_REL_CUSTOM_PATH:
                if ($this->load_root === null) {
                    $this->maybeAutoReset();

                    throw new LoaderException('Cannot load without relative load path set');
                }

                $ffi = $this->loadRelativeToPath($header, $this->load_root);

                break;
            case self::MODE_REL_HEADER_FILE:
                $ffi = $this->loadRelativeToHeaderFile($header);

                break;
            case self::MODE_DEFAULT:
                $ffi = \FFI::load($header_path);

                break;
            default:
                $this->maybeAutoReset();

                throw new LoaderException('Cannot load library with invalid mode');
        }

        $this->maybeAutoReset();

        return $ffi;
    }

    /**
     * Do automatic reset if applicable.
     */
    protected function maybeAutoReset() : void
    {
        if ($this->auto_reset === false) {
            return;
        }

        $this->reset();
    }

    /**
     * Reset the loader configuration to defaults.
     */
    public function reset() : self
    {
        $this->mode = self::MODE_DEFAULT;
        $this->load_root = null;

        return $this;
    }

    /**
     * Enable resetting automatically after calls to self::load().
     */
    public function enableAutoReset() : self
    {
        $this->auto_reset = true;

        return $this;
    }

    /**
     * Disable resetting automatically after calls to self::load().
     */
    public function disableAutoReset() : self
    {
        $this->auto_reset = false;

        return $this;
    }

    /**
     * Is this instance set to auto reset?
     */
    public function isAutoResetting() : bool
    {
        return $this->auto_reset;
    }

    /**
     * Set load mode as relative to header file where FFI_LIB is defined.
     */
    public function relativeToHeader() : self
    {
        $this->mode = self::MODE_REL_HEADER_FILE;

        return $this;
    }

    /**
     * Set load mode as relative to a custom user supplied path.
     *
     * @throws LoaderException If path is invalid.
     */
    public function relativeTo(string $path) : self
    {
        if (\is_file($path) === true) {
            $path = \dirname($path);
        }

        if (!\is_dir($path)) {
            throw new LoaderException('Cannot load relative to path, directory does not exist');
        }

        $this->mode = self::MODE_REL_CUSTOM_PATH;
        $this->load_root = $path;

        return $this;
    }

    /**
     * Set load mode to search inside a directory tree.
     *
     * @throws LoaderException If path is invalid.
     */
    public function fromDirectory(string $directory) : self
    {
        if (\is_dir($directory) === false) {
            throw new LoaderException('No directory found at `' . $directory . '`');
        }

        $this->mode = self::MODE_DIR_SEARCH;
        $this->load_root = $directory;

        return $this;
    }

    /**
     * Do actual loading with header and library file path.
     *
     * @throws LoaderException If library is missing or unreadable.
     * @throws \FFI\Exception In case FFI loading fails.
     */
    protected function doLoad(Header $header, string $lib_path) : \FFI
    {
        if (!\is_readable($lib_path)) {
            throw new LoaderException('Cannot load FFI instance, invalid library path');
        }

        return \FFI::cdef($header->getContents(), $lib_path);
    }

    /**
     * Load library with path as relative to header file.
     *
     * @throws LoaderException If loading fails.
     */
    protected function loadRelativeToHeaderFile(Header $header) : \FFI
    {
        $lib_path = $header->getFfiLib();

        \assert($lib_path !== null);

        if (\strpos($lib_path, '/') === 0) {
            throw new LoaderException('Cannot load absolute path FFI_LIB when using relative mode');
        }

        $root_path = \dirname($header->getPath());

        $actual_path = $root_path . '/' . $lib_path;

        return $this->doLoad($header, $actual_path);
    }

    /**
     * Load library relative to a user supplied path.
     *
     * @throws LoaderException If loading fails.
     */
    protected function loadRelativeToPath(Header $header, string $relative_to) : \FFI
    {
        $lib_path = $header->getFfiLib();

        \assert($lib_path !== null);

        if (\strpos($lib_path, '/') === 0) {
            throw new LoaderException('Cannot load absolute path FFI_LIB when using relative mode');
        }

        $actual_path = $relative_to . '/' . $lib_path;

        return $this->doLoad($header, $actual_path);
    }

    /**
     * Load library relative to a user supplied path.
     *
     * @throws LoaderException If loading fails.
     */
    protected function loadBySearchingDirectory(Header $header, string $directory) : \FFI
    {
        $lib_path = $header->getFfiLib();

        \assert($lib_path !== null);

        if (\strpos($lib_path, DIRECTORY_SEPARATOR) !== false) {
            throw new LoaderException(
                'Cannot use relative or absolute path for FFI_LIB when loading using directory search'
            );
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
        ));

        /** @var ?string $found_lib_path */
        $found_lib_path = null;

        /** @var \SplFileInfo $path */
        foreach ($iterator as $path) {
            if ($path->isDir()) {
                continue;
            }

            $basename = $path->getBasename();

            if ($basename === $lib_path) {
                $found_lib_path = $path->getPathname();

                break;
            }
        }

        if ($found_lib_path === null) {
            throw new LoaderException(\sprintf('Library `%s` not found inside directory `%s`', $lib_path, $directory));
        }

        return $this->doLoad($header, $found_lib_path);
    }
}
