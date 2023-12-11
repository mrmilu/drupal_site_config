<?php

namespace Drupal\site_config\Service;

use Drupal\Component\Plugin\Exception\PluginException;
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
                \Drupal::logger('site_config')->error($e->getMessage());
            }
        }

        return $data;
    }

    /**
     * Get config value by ID.
     *
     * @param string|null $id
     *
     * @return array
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
            \Drupal::logger('site_config')->error($e->getMessage());
            return [];
        }
    }

    /**
     * Get value.
     *
     * @param $siteKey
     * @param $field
     *
     * @return array|mixed
     */
    public function getValue($siteKey, $field) {
        if (empty($siteKey) || !$this->siteConfigManager->hasDefinition($siteKey)) {
            return [];
        }
        try {
            /** @var \Drupal\site_config\SiteConfigInterface $plugin */
            $plugin = $this->siteConfigManager->createInstance($siteKey);
            return $plugin->getValue($field);
        }
        catch (PluginException $e) {
            \Drupal::logger('site_config')->error($e->getMessage());
            return [];
        }
    }

    /**
     * Set value.
     *
     * @param $siteKey
     * @param $field
     * @param $value
     *
     * @return void
     */
    public function setValue($siteKey, $field, $value) {
        if (empty($siteKey) || !$this->siteConfigManager->hasDefinition($siteKey)) {
            return;
        }
        try {
            /** @var \Drupal\site_config\SiteConfigInterface $plugin */
            $plugin = $this->siteConfigManager->createInstance($siteKey);
            $plugin->setValue($field, $value);
        }
        catch (PluginException $e) {
            \Drupal::logger('site_config')->error($e->getMessage());
        }
    }

}
