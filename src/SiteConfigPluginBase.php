<?php

namespace Drupal\site_config;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for site_config plugins.
 */
abstract class SiteConfigPluginBase extends PluginBase implements SiteConfigInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\ElementInfoManager
   */
  protected ElementInfoManagerInterface $formElementManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * @var \Drupal\Core\State\State
   */
  protected State $state;


  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $languageManager, $formElementManager, $state, $configFactory) {
    if (!in_array($plugin_definition['storage'], ['status', 'config'])) {
      \Drupal::logger('site_config')
        ->error('The "storage" value must be one of the followings: status, config.');
      return;
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $languageManager;
    $this->formElementManager = $formElementManager;
    $this->state = $state;
    $this->configFactory = $configFactory;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('state'),
      $container->get('config.factory'),
    );
  }

  /**
   * @return bool
   */
  private function isTranslatable(): bool {
    return $this->pluginDefinition['translatable'];
  }

  /**
   * Get the config key.
   *
   * @return string
   */
  private function getConfigKey() {
    if ($this->isTranslatable()) {
      $langCode = $this->languageManager->getCurrentLanguage()->getId();
      return "site_config.{$this->pluginDefinition['id']}.$langCode";
    }
    else {
      return "site_config.{$this->pluginDefinition['id']}";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElement(): array {
    if (empty($this->pluginDefinition['fields'])) {
      return [];
    }

    $fieldset = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->label(),
    ];

    foreach ($this->pluginDefinition['fields'] as $fieldName => $fieldData) {
      $fieldset[$fieldName] = [
        '#title' => $fieldData['label'] ?? '',
        '#type' => (isset($fieldData['type']) && $this->formElementManager->hasDefinition($fieldData['type'])) ? $fieldData['type'] : 'textfield',
        '#required' => $fieldData['options'] ?? FALSE,
        '#default_value' => $this->getValue($fieldName),
      ];

      if ($fieldset[$fieldName]['#type'] == 'select') {
        $fieldset[$fieldName]['#empty_option'] = t('- Select -');
        $fieldset[$fieldName]['#options'] = $fieldData['options'] ?? $this->getOptions($fieldName);
      }
    }

    return $fieldset;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($field): mixed {
    $value = '';
    $key = $this->getConfigKey();
    switch ($this->pluginDefinition['storage']) {
      case 'status':
        $value = $this->state->get("{$key}.{$field}");
        break;
      case 'config':
        $value = $this->configFactory->get($key)->get($field);
        break;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($field, $value): void {
    $key = $this->getConfigKey();

    switch ($this->pluginDefinition['storage']) {
      case 'status':
        $this->state->set("{$key}.{$field}", $value);
        break;
      case 'config':
        $this->configFactory->getEditable($key)->set($field, $value)->save();
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValues(): mixed {
    $values = [];
    foreach ($this->pluginDefinition['fields'] as $fieldName => $fieldLabel) {
      $values[$fieldName] = $this->getValue($fieldName);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions($fieldName): array {
    return [];
  }

}
