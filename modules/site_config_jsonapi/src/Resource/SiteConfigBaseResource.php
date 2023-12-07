<?php

namespace Drupal\site_config_jsonapi\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_resources\Resource\ResourceBase;
use Drupal\site_config\Service\SiteConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Processes a request for the authenticated user's information.
 *
 * @internal
 */
abstract class SiteConfigBaseResource extends ResourceBase implements ContainerInjectionInterface {

  /**
   * @var SiteConfigService
   */
  protected SiteConfigService $siteConfigService;

  /**
   * Constructs a new EntityResourceBase object.
   *
   * @param \Drupal\site_config\Service\SiteConfigService $siteConfigService
   */
  public function __construct(SiteConfigService $siteConfigService) {
    $this->siteConfigService = $siteConfigService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site_config.service'),
    );
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The route resource types.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public abstract function process(Request $request, array $resource_types): ResourceResponse;

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    $resource_type = new ResourceType('site_config', 'site_config', NULL);
    return [$resource_type];
  }

  /**
   * Format values.
   *
   * @param array $values
   *
   * @return array
   */
  protected function formatValues(array $values): array {
    foreach ($values as &$value) {
      if ($value instanceof EntityInterface) {
        try {
          $value = [
            'type' => implode('--', [
              $value->getEntityTypeId(),
              $value->bundle(),
            ]),
            'id' => $value->uuid(),
            'drupal_internal__id' => $value->id(),
            'url' => $value->toUrl()->toString(TRUE)->getGeneratedUrl(),
          ];
        }
        catch (EntityMalformedException $e) {
          \Drupal::logger('site_config')->error($e->getMessage());
          return [];
        }
      }
    }
    return $values;
  }

}

