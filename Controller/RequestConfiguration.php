<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;

class RequestConfiguration extends SyliusRequestConfiguration
{
    public function isAjaxRequest(): bool
    {
        $vars = $this->getVars();

        if (isset($vars['form']['ajax'])) {
            return $vars['form']['ajax'];
        }

        return false;
    }

    public function getAjaxSerializationGroups(): array
    {
        $vars = $this->getVars();

        if (isset($vars['serialization_groups'])) {
            return $vars['serialization_groups'];
        }

        return ['AjaxShow'];
    }

    public function getAjaxValidationEventName(): string
    {
        $vars = $this->getVars();

        if (isset($vars['form']['ajax_validation_event'])) {
            return '.' . $vars['form']['ajax_validation_event'];
        }

        return '';
    }

    public function getRedirectRoute($name)
    {
        $redirect = $this->getParameters()->get('redirect');

        if (null === $redirect) {
            return $this->getRouteName($name);
        }

        if (is_array($redirect)) {
            if (!empty($redirect['referer'])) {
                return 'referer';
            }

            return $redirect['route'] ?? $this->getRouteName($name);
        }

        return $redirect;
    }

    /**
     * @return bool
     */
    public function isHeaderRedirection()
    {
        $redirect = $this->getParameters()->get('redirect');

        if (!$redirect) {
            $redirect = $this->getVars()['redirect'] ?? null;
        }

        if (!is_array($redirect) || !isset($redirect['header'])) {
            return false;
        }

        if ('xhr' === $redirect['header']) {
            return $this->getRequest()->isXmlHttpRequest();
        }

        return (bool) $redirect['header'];
    }

    public function getParents()
    {
        $parameters = $this->getParameters();

        if (!$parameters->has('parents')) {
            return null;
        }

        return $parameters->get('parents');
    }
}
