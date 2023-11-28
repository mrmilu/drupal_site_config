<?php

namespace Drupal\site_config\Plugin\rest\resource;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\site_config\Service\SiteConfigService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SiteConfig resource base.
 *
 */
abstract class SiteConfigResourceBase extends ResourceBase implements DependentPluginInterface {

  /**
   * @var SiteConfigService
   */
  protected SiteConfigService $siteConfigService;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, $siteConfigService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->siteConfigService = $siteConfigService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('site_config.service'),
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the record.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function get(?string $id = NULL) {
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheTags(['site:config']);

    $response = new ResourceResponse($this->getData($id));
    $response->addCacheableDependency($cacheable_metadata);

    return $response;
  }

  protected abstract function getData(?string $id = NULL): array;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [
      'module' => ['site_config'],
    ];
  }

}
