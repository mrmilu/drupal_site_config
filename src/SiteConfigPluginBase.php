<?php

namespace Drupal\site_config;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
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
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;


  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $languageManager, $formElementManager, $state, $configFactory, $entityTypeManager) {
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
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.manager'),
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
        '#required' => $fieldData['required'] ?? FALSE,
        '#default_value' => $this->getInternalValue($fieldName),
      ];

      if ($fieldset[$fieldName]['#type'] == 'select') {
        $fieldset[$fieldName]['#empty_option'] = t('- Select -');
        $fieldset[$fieldName]['#options'] = $fieldData['options'] ?? $this->getOptions($fieldName);
      }
      elseif ($fieldset[$fieldName]['#type'] == 'entity_autocomplete') {
        $fieldset[$fieldName]['#target_type'] = $fieldData['target_type'] ?? 'node';
        $fieldset[$fieldName]['#selection_settings'] = $fieldData['selection_settings'] ?? [];
      }
    }

    return $fieldset;
  }

  /**
   * Get the stored value for a given field.
   *
   * @param $field
   *
   * @return mixed
   */
  protected function getInternalValue($field): mixed {
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

    if (!empty($value) && $this->pluginDefinition['fields'][$field]['type'] == 'entity_autocomplete') {
      $langCode = $this->languageManager->getCurrentLanguage()->getId();
      try {
        $storage = $this->entityTypeManager->getStorage($this->pluginDefinition['fields'][$field]['target_type'] ?? 'node');
        /** @var ContentEntityBase $value */
        $value = $storage->load($value);
        if ($value->hasTranslation($langCode)) {
          $value = $value->getTranslation($langCode);
        }
      }
      catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
        \Drupal::logger('site_config')->error($e->getMessage());
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($field): mixed {
    $value = $this->getInternalValue($field);

    if ($value instanceof EntityInterface) {
      $value = [
        'type' => implode('--', [$value->getEntityTypeId(), $value->bundle()]),
        'id' => $value->uuid(),
        'drupal_internal__nid' => $value->id(),
        'url' => $value->toUrl()->toString(TRUE)->getGeneratedUrl(),
      ];
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
  function getOptions($fieldName): array {
    return [];
  }

}
