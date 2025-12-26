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
class SiteConfigItemResource extends SiteConfigBaseResource {

  /**
   * {@inheritdoc}
   */
  public function process(Request $request, array $resource_types): ResourceResponse {
    $resource_type = reset($resource_types);
    $links = new LinkCollection([]);
    $data = [];

    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheTags(['site:config'])->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);

    $id = $request->attributes->get('id');
    $config = $this->siteConfigService->getSiteConfigById($id);
    $instance = $this->siteConfigManager->getDefinitions();
    $fields = $instance[$id]['fields'] ?? [];

    foreach ($fields as $field => $field_data) {
      if (isset($config[$field])) {
        // Set value and field_type.
        $values[$field] = [
          'value' => $config[$field],
          'field_type' => $field_data['type'],
        ];

        // If  is 'multivalue' field type, add the subfields.
        if ($field_data['type'] == 'multivalue') {
          $values[$field]['fields'] = $field_data['fields'];
        }
      }
    }

    if (!empty($values)) {
      $data[] = new ResourceObject(
        $cacheable_metadata,
        $resource_type,
        $id,
        NULL,
        $this->formatValues($values),
        $links
      );
    }

    $top_level_data = new ResourceObjectData($data, 1);
    return $this->createJsonapiResponse($top_level_data, $request);
  }

}
