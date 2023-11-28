<?php

namespace Drupal\site_config\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines site_config annotation object.
 *
 * @Annotation
 */
class SiteConfig extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An array of fields.
   *
   * @var array
   */
  public $fields = [];

  /**
   * Storage type: could be 'config' or 'status'.
   *
   * @var string
   */
  public $storage = 'status';

  /**
   * Translation flag.
   *
   * @var bool
   */
  public $translatable = FALSE;

}
