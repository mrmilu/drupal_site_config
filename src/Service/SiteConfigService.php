<?php

namespace Drupal\site_config\Service;

use Drupal\site_config\SiteConfigPluginManager;

class SiteConfigService {

  /**
   * @var \Drupal\site_config\SiteConfigPluginManager
   */
  protected SiteConfigPluginManager $siteConfigManager;

  /**
   * @param \Drupal\site_config\SiteConfigPluginManager $siteConfigManager
   */
  public function __construct(SiteConfigPluginManager $siteConfigManager) {
    $this->siteConfigManager = $siteConfigManager;
  }

  /**
   * Get all config values.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSiteConfig(): array {
    $data = [];

    foreach ($this->siteConfigManager->getDefinitions() as $pluginId => $pluginDefinition) {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($pluginId);
      $data[$pluginId] = $plugin->getValues();
    }

    return $data;
  }

  /**
   * Get config value by ID.
   *
   * @param string|null $id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSiteConfigById(?string $id): array {
    if (empty($id) || !$this->siteConfigManager->hasDefinition($id)) {
      return [];
    }

    /** @var \Drupal\site_config\SiteConfigInterface $plugin */
    $plugin = $this->siteConfigManager->createInstance($id);
    return $plugin->getValues();
  }

}
