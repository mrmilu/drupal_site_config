<?php

namespace Drupal\site_config\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_config\SiteConfigPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Configure Site config settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The plugin definitions.
   *
   * @var array
   */
  protected array $pluginDefinitions;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\site_config\SiteConfigPluginManager $siteConfigManager
   *   The site config plugin manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected TypedConfigManagerInterface $typedConfigManager,
    protected SiteConfigPluginManager $siteConfigManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected BlockManagerInterface $blockManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->pluginDefinitions = $this->siteConfigManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('plugin.manager.site_config'),
      $container->get('cache_tags.invalidator'),
      $container->get('plugin.manager.block'),
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
    $plugin_block = $this->blockManager->createInstance('language_block:language_interface');
    if (!empty($plugin_block)) {
      $form['lang_switcher_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Choose language'),
      ];
      $form['lang_switcher_wrapper']['lang_switcher'] = $plugin_block->build();
    }

    foreach ($this->pluginDefinitions as $pluginId => $pluginDefinition) {
      /** @var \Drupal\site_config\SiteConfigInterface $plugin */
      $plugin = $this->siteConfigManager->createInstance($pluginId);
      $form[$pluginId] = $plugin->getFormElement();
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'] = 'site_config/site_config.form';

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

    $this->cacheTagsInvalidator->invalidateTags(['site:config']);

    parent::submitForm($form, $form_state);
  }

}
