<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\Parameters;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

final class RequestConfigurationFactory implements RequestConfigurationFactoryInterface
{
    private RequestConfigurationFactoryInterface $decorated;

    /** @var RouterInterface */
    private $router;

    /**
     * @var string
     *
     * @psalm-var class-string<RequestConfiguration>
     */
    private $configurationClass;

    /**
     * @psalm-param class-string<RequestConfiguration> $configurationClass
     */
    public function __construct($decorated, RouterInterface $router, string $configurationClass)
    {
        $this->decorated = $decorated;
        $this->router = $router;
        $this->configurationClass = $configurationClass;
    }

    public function create(MetadataInterface $metadata, Request $request): RequestConfiguration
    {
        $decoratedConfiguration = $this->decorated->create($metadata, $request);

        /** @psalm-suppress UnsafeInstantiation */
        return new $this->configurationClass(
            $decoratedConfiguration->getMetadata(),
            $request,
            $this->getParameters($decoratedConfiguration, $request),
        );
    }

    private function getParameters(SyliusRequestConfiguration $configuration, Request $request): Parameters
    {
        $action = $request->request->get('save_action', '');
        $url = '';
        $parameters = $configuration->getParameters();
        $vars = $configuration->getVars();
        $route = ['header' => 'xhr'];
        $referer = $this->getRefereUrl($request, $configuration, $vars, $action);

        if (!empty($action)) {
            if ($action === 'referer') {
                $route = array_merge($route, $referer);
            } else {
                if (isset($vars['redirect'][$action])) {
                    $redirectAction = $vars['redirect'][$action];
                    $route['url'] = $this->router->generate($redirectAction['route'], $redirectAction['parameters'] ?? []);
                    $route['hash'] = $redirectAction['hash'] ?? '';
                } else {
                    $route['route'] = $configuration->getRouteName($action);
                }
            }

            $parameters->set('redirect', $route);
        }

        try {
            if (isset($referer['url'])) {
                $url = $referer['url'];
            } else {
                $url = $this->router->generate($referer['route'] ?? null, $referer['parameters'] ?? null);
            }
        } catch (RouteNotFoundException $e) {
            $url = $request->getRequestUri();
        }

        $parameters->set('vars', array_merge($vars, [
            'referer_url' => $url,
        ]));

        return $parameters;
    }

    /**
     * @return (array|mixed|string|null)[]
     *
     * @psalm-return array{url?: mixed, route?: mixed|null|string, parameters?: array<never, never>}
     */
    private function getRefereUrl(Request $request, SyliusRequestConfiguration $configuration, array $vars, string $action): array
    {
        $route = [];
        $session = $request->getSession();
        $previousPath = $session->get('_previous_path');

        if (empty($previousPath)) {
            $route['route'] = $vars['redirect']['optional_referer'] ?? $configuration->getRedirectRoute(ResourceActions::INDEX);
            $route['parameters'] = [];
        } else {
            $route['url'] = $session->get('_previous_path');
        }

        return $route;
    }
}
