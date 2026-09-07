<?php

declare(strict_types=1);

namespace Lochmueller\Seal;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * The Loupe adapter builds its storage directory from the DSN host and path
 * ("loupe://var/loupe" => "var/loupe") and hands it to Loupe unchanged, which
 * resolves a relative directory against the current working directory. That
 * directory differs between CLI (project path) and frontend requests (public
 * path), so indexing and searching would silently work on two different
 * databases and the search stays empty. Relative directories are therefore made
 * absolute against the project path.
 *
 * An empty directory ("loupe://") makes Loupe store everything in memory, which
 * is dropped at the end of every request. It defaults to the documented
 * "var/loupe" location instead.
 */
class DsnNormalizer
{
    public const LOUPE_SCHEME = 'loupe';
    public const LOUPE_DEFAULT_DIRECTORY = 'var/loupe';

    public function normalize(string $dsn): string
    {
        $prefix = self::LOUPE_SCHEME . '://';
        if (!str_starts_with($dsn, $prefix)) {
            return $dsn;
        }

        // Everything behind the scheme is the directory, which is what
        // CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory::createHelper builds out of host and path.
        // parse_url is not usable here, as it fails for "loupe://" and "loupe:///absolute/path" since PHP 8.5.
        $directory = substr($dsn, \strlen($prefix));
        $directoryLength = strcspn($directory, '?#');
        $suffix = substr($directory, $directoryLength);
        $directory = substr($directory, 0, $directoryLength);

        if ($directory === '') {
            $directory = self::LOUPE_DEFAULT_DIRECTORY;
        }

        if (!PathUtility::isAbsolutePath($directory)) {
            $directory = rtrim(Environment::getProjectPath(), '/') . '/' . $directory;
        }

        return $prefix . $directory . $suffix;
    }
}
