# Site Config

Site Config is a Drupal module that provides a flexible way to manage global
site configurations using a plugin-based system. It allows developers to define
custom configuration sets that can be easily edited via a unified settings form
and exposed through REST or JSON:API.

## Features

- **Plugin-based architecture**: Define configuration sets as plugins.
- **Unified Settings Form**: All configurations are manageable from a single
  administrative interface.
- **Multilingual Support**: Supports translatable configurations.
- **Flexible Storage**: Choose between Drupal's State API or Configuration API
  for storage.
- **Entity Integration**: Built-in support for entity autocompletes.
- **API Ready**: Submodules for REST and JSON:API integration.

## Requirements

- Drupal 9, 10, or 11.
- `jsonapi_resources` module (only if using `site_config_jsonapi`).
- `multivalue_form_element` module (optional, for multi-value field support).

## Installation

1. Install the module as you would any other Drupal module.
2. Enable the main `site_config` module.
3. (Optional) Enable `site_config_rest` or `site_config_jsonapi` if you need to
   expose your configurations via API.

## Configuration

The main settings form is located at `/admin/site-config`. Here you will find
all the configuration elements defined by the active Site Config plugins.

## Creating a Site Config Plugin

To create a new configuration set, you need to define a plugin in your custom
module.

### 1. Define the Plugin using PHP Attributes (Recommended)

Create a file in `src/Plugin/SiteConfig/YourPluginName.php`:

```php
<?php

namespace Drupal\your_module\Plugin\SiteConfig;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\site_config\Attribute\SiteConfig;
use Drupal\site_config\SiteConfigPluginBase;

#[SiteConfig(
  id: "custom_settings_attribute",
  label: new TranslatableMarkup("Custom Settings attribute"),
  fields: [
    "welcome_message" => [
      "type" => "textfield",
      "title" => "Welcome Message",
      "description" => "A message shown on the home page.",
    ],
    "featured_node" => [
      "type" => "entity_autocomplete",
      "target_type" => "node",
      "title" => "Featured Content",
    ],
  ],
  storage: "config",
  translatable: true,
)]
class YourPluginName extends SiteConfigPluginBase {
}
```

### 2. Define the Plugin using Annotations (Legacy)

Although Attributes are recommended for Drupal 10.2+, Annotations are still supported:

```php
<?php

namespace Drupal\your_module\Plugin\SiteConfig;

use Drupal\site_config\SiteConfigPluginBase;

/**
 * Provides a site config plugin.
 *
 * @SiteConfig(
 *   id = "custom_settings",
 *   label = @Translation("Custom Settings"),
 *   storage = "config",
 *   translatable = true,
 *   fields = {
 *     "welcome_message" = {
 *       "type" = "textfield",
 *       "title" = "Welcome Message",
 *       "description" = "A message shown on the home page."
 *     },
 *     "featured_node" = {
 *       "type" = "entity_autocomplete",
 *       "target_type" = "node",
 *       "title" = "Featured Content"
 *     }
 *   }
 * )
 */
class YourPluginName extends SiteConfigPluginBase {
}
```

### 3. Plugin Properties

- `id`: Unique identifier for the plugin.
- `label`: Human-readable label for the configuration group.
- `storage`: Either `config` (Configuration API) or `state` (State API).
- `translatable`: Boolean indicating if the values should be stored per language.
- `fields`: An array of form elements. Most standard Drupal Form API properties
  are supported.

## Submodules

### Site Config REST

Exposes the configurations via Drupal's RESTful Web Services.
- **Resources**:
  - `site_config_list`: List all available configuration sets.
  - `site_config_item`: Get values for a specific configuration set.

### Site Config JSON:API

Exposes the configurations as JSON:API resources.
- **Endpoints**:
  - ` / jsonapi / site - config`: List all configuration sets.
  - ` / jsonapi / site - config / item / {id}`: Get values for a specific
    config ID.

## License

This module is licensed under the GPL-2.0-or-later license.
