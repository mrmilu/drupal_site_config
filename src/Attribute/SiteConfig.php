<?php

namespace Drupal\site_config\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines site_config attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SiteConfig extends Plugin {

  /**
   * Constructs a SiteConfig attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\Annotation\Translation|string|null $label
   *   The human-readable name of the plugin.
   * @param \Drupal\Core\Annotation\Translation|string|null $description
   *   The description of the plugin.
   * @param array $fields
   *   An array of fields.
   * @param string $storage
   *   Storage type: could be 'config' or 'state'.
   * @param bool $translatable
   *   Translation flag.
   */
  public function __construct(
    public readonly string $id,
    public $label = NULL,
    public $description = NULL,
    public array $fields = [],
    public string $storage = 'state',
    public bool $translatable = FALSE,
  ) {}

}
