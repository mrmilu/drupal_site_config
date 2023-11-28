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
   * Get select options for a field.
   *
   * @param $fieldName
   *
   * @return array
   */
  function getOptions($fieldName): array;
}
