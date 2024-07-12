<?php

namespace Drupal\site_config;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
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
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $moduleHandler;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $languageManager, $formElementManager, $state, $configFactory, $entityTypeManager, $moduleHandler) {
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
    $this->moduleHandler = $moduleHandler;
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
      $container->get('module_handler'),
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
      // Add '#' to all keys of $data to make it a form element.
      $fieldset[$fieldName] = $this->getArrayFormElement($fieldData);
      // Check if $data['#label'] is set, if not, use $data['#title'], if not use 'Value'.
      $fieldset[$fieldName]['#title'] = $fieldset[$fieldName]['#label'] ?? $fieldset[$fieldName]['#title'] ?? t('Value');

      $fieldset[$fieldName]['#default_value'] = $this->getValue($fieldName);

      if ($fieldset[$fieldName]['#type'] == 'select') {
        $fieldset[$fieldName]['#empty_option'] = t('- Select -');
        $fieldset[$fieldName]['#options'] = $fieldData['options'] ?? $this->getOptions($fieldName);
      }
      elseif ($fieldset[$fieldName]['#type'] == 'entity_autocomplete') {
        $fieldset[$fieldName]['#target_type'] = $fieldData['target_type'] ?? 'node';
        $fieldset[$fieldName]['#selection_settings'] = $fieldData['selection_settings'] ?? [];
      }
      elseif ($fieldset[$fieldName]['#type'] == 'multivalue') {
        if (!$this->moduleHandler->moduleExists('multivalue_form_element')) {
          unset($fieldset[$fieldName]);
          continue;
        }
        $fields = $fieldData['fields'] ?? ['value' => []];
        foreach ($fields as $key => $data) {
          // Add '#' to all keys of $data to make it a form element.
          $data = $this->getArrayFormElement($data);
          $data['#type'] = $data['#type'] ?? 'textfield';

          // Check if $data['#label'] is set, if not, use $data['#title'], if not use 'Value'.
          $data['#title'] = $data['#label'] ?? $data['#title'] ?? t('Value');

          // Set field form element.
          $fieldset[$fieldName][$key] = $data;
        }
        if (empty($fieldset[$fieldName]['#default_value'])) {
          $fieldset[$fieldName]['#default_value'] = [];
        }
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
    $langCode = $this->languageManager->getCurrentLanguage()->getId();

    switch ($this->pluginDefinition['storage']) {
      case 'status':
        $value = $this->state->get("{$key}.{$field}");
        break;
      case 'config':
        $value = $this->configFactory->get($key)->get($field);
        break;
    }

    if (!empty($value)) {
      if ($this->pluginDefinition['fields'][$field]['type'] == 'entity_autocomplete') {
        try {
          $entity = $this->entityTypeManager->getStorage($this->pluginDefinition['fields'][$field]['target_type'] ?? 'node')->load($value);
          if ($entity instanceof ContentEntityBase) {
            $value = $entity;
            if ($entity->hasTranslation($langCode)) {
              $value = $value->getTranslation($langCode);
            }
          }
        }
        catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
          \Drupal::logger('site_config')->error($e->getMessage());
        }
      }

      if (is_array($value) && $this->pluginDefinition['fields'][$field]['type'] == 'multivalue') {
        $fields = $this->pluginDefinition['fields'][$field]['fields'];
        foreach ($value as $key => $value_array) {
          // Foreach $fields and check if its an autocomplete.
          foreach ($value_array as $fieldName => $value_config) {
            if (isset($fields[$fieldName]['type']) &&  $fields[$fieldName]['type'] == 'entity_autocomplete') {
              try {
                $entity = $this->entityTypeManager->getStorage($fields[$fieldName]['target_type'] ?? 'node')->load($value_array[$fieldName]) ;
                if ($entity instanceof ContentEntityBase) {
                  $value[$key][$fieldName] = $entity;
                  if ($entity->hasTranslation($langCode)) {
                    $value[$key][$fieldName] = $entity->getTranslation($langCode);
                  }
                }
              }
              catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
                \Drupal::logger('site_config')->error($e->getMessage());
              }
            }
          }
        }
      }
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
  public function getArrayFormElement(array $array): mixed {
    if (empty($array)) {
      return [];
    }

    return array_combine(array_map(function ($k) {
      return '#' . $k;
    }, array_keys($array)), $array);
  }

  /**
   * {@inheritdoc}
   */
  function getOptions($fieldName): array {
    return [];
  }

}
