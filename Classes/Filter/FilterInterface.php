<?php

declare(strict_types=1);

namespace Lochmueller\Seal\Filter;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: 'seal.filter')]
interface FilterInterface
{
    public function getType(): string;


    public function getFilterConfiguration(array $filterItem, RequestInterface $request): array;


}
