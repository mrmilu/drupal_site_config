<?php

namespace Drupal\site_config_jsonapi\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageInterface;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents SiteConfig records as resources.
 *
 * @internal
 */
class SiteConfigListResource extends SiteConfigBaseResource {

  /**
   * {@inheritdoc}
   */
  public function process(Request $request, array $resource_types): ResourceResponse {
    $resource_type = reset($resource_types);
    $links = new LinkCollection([]);
    $data = [];

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheTags(['site:config'])->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);

    $config = $this->siteConfigService->getSiteConfig();
    $instance = $this->siteConfigManager->getDefinitions();

    foreach ($config as $key => $values) {
      $fields = $instance[$key]['fields'];
      foreach ($fields as $field => $field_data) {
        if (isset($values[$field])) {
          // Set value and field_type.
          $values[$field] = [
            'value' => $values[$field],
            'field_type' => $field_data['type'],
          ];

          // If  is 'multivalue' field type, add the subfields.
          if ($field_data['type'] == 'multivalue') {
            $values[$field]['fields'] = $field_data['fields'];
          }
        }
      }

      $data[] = new ResourceObject(
        $cacheable_metadata,
        $resource_type,
        $key,
        NULL,
        $this->formatValues($values),
        $links
      );
    }

    $top_level_data = new ResourceObjectData($data);
    return $this->createJsonapiResponse($top_level_data, $request);
  }

}

