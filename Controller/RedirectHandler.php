<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

final class RedirectHandler implements RedirectHandlerInterface
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param RequestConfiguration $configuration
     */
    public function redirectToResource(SyliusRequestConfiguration $configuration, ResourceInterface $resource): Response
    {
        $redirect = $configuration->getParameters()->get('redirect');

        if(isset($redirect['url'])) {
            return $this->redirect($configuration, $redirect['url']);
        } else {
            try {
                return $this->redirectToRoute(
                    $configuration,
                    (string) $configuration->getRedirectRoute(ResourceActions::SHOW),
                    $configuration->getRedirectParameters($resource)
                );
            } catch (RouteNotFoundException $exception) {
                return $this->redirectToRoute(
                    $configuration,
                    (string) $configuration->getRedirectRoute(ResourceActions::INDEX),
                    $configuration->getRedirectParameters($resource)
                );
            }
        }
    }

    /**
     * @param RequestConfiguration $configuration
     */
    public function redirectToIndex(SyliusRequestConfiguration $configuration, ?ResourceInterface $resource = null): Response
    {
        return $this->redirectToRoute(
            $configuration,
            (string) $configuration->getRedirectRoute('index'),
            $configuration->getRedirectParameters($resource)
        );
    }

    /**
     * @param RequestConfiguration $configuration
     */
    public function redirectToRoute(SyliusRequestConfiguration $configuration, string $route, array $parameters = []): Response
    {
        if ('referer' === $route) {
            return $this->redirectToReferer($configuration);
        }

        return $this->redirect($configuration, $this->router->generate($route, $parameters));
    }

    /**
     * @param RequestConfiguration $configuration
     */
    public function redirect(SyliusRequestConfiguration $configuration, string $url, int $status = 302): Response
    {
        if ($configuration->isHeaderRedirection()) {
            return new Response('', 200, [
                'X-OWL-LOCATION' => $url . $configuration->getRedirectHash(),
            ]);
        }

        return new RedirectResponse($url . $configuration->getRedirectHash(), $status);
    }

    /**
     * @param RequestConfiguration $configuration
     */
    public function redirectToReferer(SyliusRequestConfiguration $configuration): Response
    {
        return $this->redirect($configuration, (string) $configuration->getRedirectReferer());
    }

    public function getRedirectHeaders(SyliusRequestConfiguration $configuration, ?ResourceInterface $resource): array
    {
        $url = '';
        $redirect = $configuration->getParameters()->get('redirect');

        if(isset($redirect['url'])) {
            $url = $redirect['url'];
        } else {
            $parameters = $resource ? $configuration->getRedirectParameters($resource) : [];

            try {
                $url = $this->router->generate(
                    (string) $configuration->getRedirectRoute(ResourceActions::SHOW),
                    $parameters
                );
            } catch (RouteNotFoundException $exception) {
                $url = $this->router->generate(
                    (string) $configuration->getRedirectRoute(ResourceActions::INDEX),
                    $parameters
                );
            }
        }

        return [
            'X-OWL-LOCATION' => $url . $configuration->getRedirectHash(),
        ];
    }
}
