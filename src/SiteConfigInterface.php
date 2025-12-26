<?php

namespace Drupal\site_config;

/**
 * Interface for site_config plugins.
 */
interface SiteConfigInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Returns the form element.
   *
   * @return array
   *   Form element.
   */
  public function getFormElement(): array;

  /**
   * Returns the field stored value.
   *
   * @return mixed
   *   Value.
   */
  public function getValue(string $field): mixed;

  /**
   * Save the config value.
   */
  public function setValue(string $field, $value): void;

  /**
   * Returns all storeds value.
   *
   * @return mixed
   *   Value.
   */
  public function getValues(): mixed;

  /**
   * Add '#' to all keys of $data to make it a form element.
   *
   * @param array $array
   *   The array to transform.
   *
   * @return mixed
   *   The transformed array.
   */
  public function getArrayFormElement(array $array): mixed;

  /**
   * Get select options for a field.
   *
   * @param string $fieldName
   *   The field name.
   *
   * @return array
   *   The options array.
   */
  public function getOptions(string $fieldName): array;

}
