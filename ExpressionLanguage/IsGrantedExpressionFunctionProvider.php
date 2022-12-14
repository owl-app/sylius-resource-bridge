<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class IsGrantedExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'isGranted',
                /**
                 * @param mixed $result
                 */
                function ($result): string {
                    return sprintf('$this->authorizationChecker->isGranted(\'\', %1$s) ? %1$s : throw new AccessDeniedException())', $result);
                },
                /**
                 * @param mixed $arguments
                 * @param mixed $result
                 *
                 * @return mixed
                 */
                function ($arguments, $result) {
                    if (!$this->authorizationChecker->isGranted('', $result)) {
                        throw new AccessDeniedException();
                    }

                    return $result;
                }
            ),
        ];
    }
}
