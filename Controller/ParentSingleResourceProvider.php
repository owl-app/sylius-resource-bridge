<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Resource\Metadata\Registry;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Sylius\Component\Registry\ServiceRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use LogicException;
use Owl\Bridge\SyliusResource\Doctrine\Orm\ItemProviderInterface;

final class ParentSingleResourceProvider implements ParentSingleResourceProviderInterface
{
    public function __construct(
        private Registry $resourceRegistry,
        private ServiceRegistry $resourceRepositoryRegistry,
        private ItemProviderInterface $itemProvider
    ) {

    }

    /**
     * @return \Sylius\Component\Resource\Model\ResourceInterface[]
     *
     * @psalm-return array<string, \Sylius\Component\Resource\Model\ResourceInterface>
     */
    public function get(RequestConfiguration $requestConfiguration): array
    {
        $resources = $requestConfiguration->getParents();
        $resourceParents = [];

        if($resources) {
            foreach($resources as $parentParams) {
                $metadata = $this->getMetadata($parentParams);
                $method = $this->getRepositoryParam($parentParams, 'method');
                $arguments = $this->getRepositoryParam($parentParams, 'arguments');
                $repository = $this->resourceRepositoryRegistry->get(
                    sprintf('%s.%s', $metadata->getApplicationName(), $metadata->getName())
                );

                $repositoryOptions = [
                    'method' => $method,
                    'arguments' => $arguments
                ];

                $resourceParent = $this->itemProvider->get($repository, null, $repositoryOptions);

                if(!$resourceParent) {
                    throw new NotFoundHttpException(sprintf('The "%s" has not been found', $metadata->getHumanizedName()));
                }

                $resourceParents[$metadata->getName()] = $resourceParent;
            }
        }
        
        return $resourceParents;
    }

    private function getMetadata(array $resourceParent): MetadataInterface
    {
        if (!isset($resourceParent['resource'])) {
            throw new LogicException('Not set resource to parent resource');
        }

        return $this->resourceRegistry->get($resourceParent['resource']);
    }

    private function getRepositoryParam(array $resourceParent, string $param)
    {
        if (!isset($resourceParent['repository'][$param])) {
            throw new LogicException(sprintf('Not set %s repository to parent resource', $param));
        }

        return $resourceParent['repository'][$param];
    }
}
