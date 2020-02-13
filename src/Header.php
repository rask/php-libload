<?php declare(strict_types = 1);

namespace rask\Libload;

use rask\Libload\Parsing\ParseException;

/**
 * Class Header
 *
 * @internal
 */
final class Header
{
    /**
     * Path to header file.
     */
    protected string $file_path;

    /**
     * Full header file contents.
     */
    protected string $header_contents;

    /**
     * Header constructor.
     */
    public function __construct(string $header_file_path)
    {
        if (\is_file($header_file_path) === false) {
            throw new ParseException('Cannot parse header file, not a file');
        }

        if (\is_readable($header_file_path) === false) {
            throw new ParseException('Cannot parse header file, file not readable');
        }

        $this->file_path = $header_file_path;

        $header_file = \file_get_contents($header_file_path);

        \assert($header_file !== false); // We don't use false-inducing args when getting file contents above.

        $this->header_contents = $header_file;
    }

    /**
     * Get the defined FFI_LIB path.
     */
    public function getFfiLib() : ?string
    {
        \preg_match('/^#define +FFI_LIB +[\'"](.+?)[\'"]\n/', $this->header_contents, $matched);

        return $matched[1] ?? null;
    }

    /**
     * Get full text contents of header.
     */
    public function getContents() : string
    {
        return $this->header_contents;
    }

    /**
     * Get the header file path.
     */
    public function getPath() : string
    {
        return $this->file_path;
    }
}
