<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

interface AbstractResourceActionInterface
{
    public function __invoke(Request $request): Response;
}