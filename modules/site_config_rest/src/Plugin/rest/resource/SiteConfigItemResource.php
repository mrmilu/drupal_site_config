<?php

namespace Drupal\site_config_rest\Plugin\rest\resource;

/**
 * Represents SiteConfig records as resources.
 *
 * @RestResource (
 *   id = "site_config_item",
 *   label = @Translation("Site Config Item"),
 *   uri_paths = {
 *     "canonical" = "/api/site-config/{id}",
 *   }
 * )
 *
 */
class SiteConfigItemResource extends SiteConfigResourceBase {

  protected function getData(?string $id = NULL): array {
    return $this->siteConfigService->getSiteConfigById($id);
  }

}
