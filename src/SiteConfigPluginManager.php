<?php

namespace Drupal\site_config;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * SiteConfig plugin manager.
 */
class SiteConfigPluginManager extends DefaultPluginManager {

  /**
   * Constructs SiteConfigPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    protected CacheBackendInterface $cache_backend,
    protected ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/SiteConfig',
      $namespaces,
      $module_handler,
      'Drupal\site_config\SiteConfigInterface',
      'Drupal\site_config\Attribute\SiteConfig',
      'Drupal\site_config\Annotation\SiteConfig'
    );
    $this->alterInfo('site_config_info');
    $this->setCacheBackend($cache_backend, 'site_config_plugins');
  }

}
