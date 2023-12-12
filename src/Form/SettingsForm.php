<?php

namespace Drupal\site_config\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_config\SiteConfigPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Site config settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\site_config\SiteConfigPluginManager
   */
  protected SiteConfigPluginManager $siteConfigManager;

  /**
   * @var array
   */
  protected array $pluginDefinitions;

  public function __construct(ConfigFactoryInterface $config_factory, $siteConfigManager) {
    parent::__construct($config_factory);
    $this->siteConfigManager = $siteConfigManager;
    $this->pluginDefinitions = $this->siteConfigManager->getDefinitions();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.site_config'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['site_config.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach ($this->pluginDefinitions as $pluginId => $pluginDefinition) {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($pluginId);
      $form[$pluginId] = $plugin->getFormElement();
    }

    $form['#tree'] = TRUE;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->pluginDefinitions as $pluginId => $pluginDefinition) {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($pluginId);
      foreach ($form_state->getValue($pluginId) as $key => $value) {
        $plugin->setValue($key, $value);
      }
    }

   \Drupal::service('cache_tags.invalidator')->invalidateTags(['site:config']);

    parent::submitForm($form, $form_state);
  }

}
