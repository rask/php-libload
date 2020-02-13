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
     * Loading mode.
     */
    protected int $mode = self::MODE_DEFAULT;

    /**
     * When loading relative to a custom path, this is the root where the file is looked from.
     */
    protected ?string $load_root = null;

    /**
     * Load FFI instance as defined in bindings.
     *
     * @throws LoaderException If loading fails.
     */
    public function load(string $header_path) : \FFI
    {
        $header = new Header($header_path);

        switch ($this->mode) {
            case self::MODE_REL_CUSTOM_PATH:
                if ($this->load_root === null) {
                    throw new LoaderException('Cannot load without relative load path set');
                }

                return $this->loadRelativeToPath($header, $this->load_root);
            case self::MODE_REL_HEADER_FILE:
                return $this->loadRelativeToHeaderFile($header);
            case self::MODE_DEFAULT:
                return \FFI::load($header_path);
            default:
                throw new LoaderException('Cannot load library with invalid mode');
        }
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

        if ($lib_path === null) {
            throw new LoaderException('No FFI_LIB defined in header');
        }

        if (\strpos($lib_path, '/') === 0) {
            throw new LoaderException('Cannot load absolute path using relative mode');
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

        if ($lib_path === null) {
            throw new LoaderException('No FFI_LIB defined in header');
        }

        if (\strpos($lib_path, '/') === 0) {
            throw new LoaderException('Cannot load absolute path using relative mode');
        }

        $actual_path = $relative_to . '/' . $lib_path;

        return $this->doLoad($header, $actual_path);
    }
}
