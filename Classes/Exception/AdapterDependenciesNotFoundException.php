<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Exception;

use Throwable;

class AdapterDependenciesNotFoundException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, ?string $package = null)
    {
        if ($package) {
            $message = 'You try to use EXT:seal with a adapter that is not exist in your instance. Please run `composer require ' . $package . '` to install the right adapter.';
        }
        parent::__construct($message, $code, $previous);
    }
}
