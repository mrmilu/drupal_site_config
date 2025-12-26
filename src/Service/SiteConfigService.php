<?php

namespace Drupal\site_config\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\site_config\SiteConfigPluginManager;

/**
 * Provides a service for site configuration.
 */
class SiteConfigService {

  /**
   * The site config plugin manager.
   *
   * @var \Drupal\site_config\SiteConfigPluginManager
   */
  protected SiteConfigPluginManager $siteConfigManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Constructs a new SiteConfigService object.
   *
   * @param \Drupal\site_config\SiteConfigPluginManager $siteConfigManager
   *   The site config plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(SiteConfigPluginManager $siteConfigManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->siteConfigManager = $siteConfigManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Get all config values.
   *
   * @return array
   *   The site configuration values.
   */
  public function getSiteConfig(): array {
    $data = [];

    foreach ($this->siteConfigManager->getDefinitions() as $pluginId => $pluginDefinition) {
      try {
        /** @var \Drupal\site_config\SiteConfigInterface $plugin */
        $plugin = $this->siteConfigManager->createInstance($pluginId);
        $data[$pluginId] = $plugin->getValues();
      }
      catch (PluginException $e) {
        $this->loggerFactory->get('site_config')->error('Site config error: @message', ['@message' => $e->getMessage()]);
      }
    }

    return $data;
  }

  /**
   * Get config value by ID.
   *
   * @param string|null $id
   *   The plugin ID.
   *
   * @return array
   *   The site configuration values for the given ID.
   */
  public function getSiteConfigById(?string $id): array {
    if (empty($id) || !$this->siteConfigManager->hasDefinition($id)) {
      return [];
    }

    try {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($id);
      return $plugin->getValues();
    }
    catch (PluginException $e) {
      $this->loggerFactory->get('site_config')->error('Site config error: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Get value.
   *
   * @param string $siteKey
   *   The site key.
   * @param string $field
   *   The field name.
   * @param mixed $defaultValue
   *   The default value.
   *
   * @return array|mixed
   *   The field value or the default value.
   */
  public function getValue(string $siteKey, string $field, $defaultValue = NULL) {
    if (empty($siteKey) || !$this->siteConfigManager->hasDefinition($siteKey)) {
      return $defaultValue;
    }
    try {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($siteKey);
      return $plugin->getValue($field);
    }
    catch (PluginException $e) {
      $this->loggerFactory->get('site_config')->error('Site config error: @message', ['@message' => $e->getMessage()]);
      return $defaultValue;
    }
  }

  /**
   * Set value.
   *
   * @param string $siteKey
   *   The site key.
   * @param string $field
   *   The field name.
   * @param mixed $value
   *   The value to set.
   *
   * @return void
   *   This method does not return a value.
   */
  public function setValue(string $siteKey, string $field, $value) {
    if (empty($siteKey) || !$this->siteConfigManager->hasDefinition($siteKey)) {
      return;
    }
    try {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($siteKey);
      $plugin->setValue($field, $value);
    }
    catch (PluginException $e) {
      $this->loggerFactory->get('site_config')->error('Site config error: @message', ['@message' => $e->getMessage()]);
    }
  }

}
