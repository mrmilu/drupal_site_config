<?php

namespace Drupal\site_config_jsonapi\Resource;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi_resources\Resource\ResourceBase;
use Drupal\site_config\Service\SiteConfigService;
use Drupal\site_config\SiteConfigPluginManager;
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
   * The site config service.
   *
   * @var \Drupal\site_config\Service\SiteConfigService
   */
  protected SiteConfigService $siteConfigService;

  /**
   * The site config plugin manager.
   *
   * @var \Drupal\site_config\SiteConfigPluginManager
   */
  protected SiteConfigPluginManager $siteConfigManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Constructs a new EntityResourceBase object.
   *
   * @param \Drupal\site_config\Service\SiteConfigService $siteConfigService
   *   The site config service.
   * @param \Drupal\site_config\SiteConfigPluginManager $siteConfigManager
   *   The site config plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(SiteConfigService $siteConfigService, SiteConfigPluginManager $siteConfigManager, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->siteConfigService = $siteConfigService;
    $this->siteConfigManager = $siteConfigManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site_config.service'),
      $container->get('plugin.manager.site_config'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
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
  abstract public function process(Request $request, array $resource_types): ResourceResponse;

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    $resource_type = new ResourceType('site_config', 'item', NULL);
    return [$resource_type];
  }

  /**
   * Format values.
   *
   * @param array $values
   *   The values to format.
   *
   * @return array
   *   The formatted values.
   */
  protected function formatValues(array $values): array {
    foreach ($values as &$value) {
      switch ($value['field_type']) {
        case 'managed_file':
          foreach ($value['value'] as $numeric_key => $field_value) {
            $file = $this->entityTypeManager->getStorage('file')->load($field_value);
            if ($file instanceof EntityInterface) {
              $value['value'][$numeric_key] = $this->getFormatEntityValues($file);
            }
          }
          break;

        case 'entity_autocomplete':
          if ($value['value'] instanceof EntityInterface) {
            $value = $this->getFormatEntityValues($value['value']);
          }
          break;

        case 'multivalue':
          if (isset($value['fields'])) {
            foreach ($value['fields'] as $key => $field) {
              if (isset($field['type']) && $field['type'] == 'entity_autocomplete') {
                foreach ($value['value'] as $numeric_key => $field_value) {
                  if ($field_value[$key] instanceof EntityInterface) {
                    $value['value'][$numeric_key][$key] = $this->getFormatEntityValues($field_value[$key]);
                  }
                }
              }
              elseif ($field['type'] == 'managed_file') {
                foreach ($value['value'] as $numeric_key => $field_value) {
                  $file = $this->entityTypeManager->getStorage('file')->load(reset($field_value[$key]));
                  if ($file instanceof EntityInterface) {
                    $value['value'][$numeric_key][$key] = $this->getFormatEntityValues($file);
                  }
                }
              }
            }
          }
          break;
      }
    }

    // Remove field_type and value, and just return the value on each field.
    $values = array_map(function ($field) {
      return $field['value'] ?? $field;
    }, $values);

    return $values;
  }

  /**
   * Format values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to format.
   *
   * @return array
   *   The formatted entity values.
   */
  public function getFormatEntityValues(EntityInterface $entity): array {
    try {
      $entity_values = [
        'type' => implode('--', [
          $entity->getEntityTypeId(),
          $entity->bundle(),
        ]),
        'id' => $entity->uuid(),
        'drupal_internal__id' => $entity->id(),
        'url' => !$entity instanceof File ? $entity->toUrl()->toString(TRUE)->getGeneratedUrl() : $entity->getFileUri(),
      ];
    }
    catch (EntityMalformedException $e) {
      $this->loggerFactory->get('site_config')->error('Site config error: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $entity_values;
  }

}
