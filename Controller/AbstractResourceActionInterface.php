<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface AbstractResourceActionInterface
{
    public function __invoke(Request $request): Response;
}
