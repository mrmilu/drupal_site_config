<?php

namespace Drupal\site_config_rest\Plugin\rest\resource;

/**
 * Represents SiteConfig records as resources.
 *
 * @RestResource (
 *   id = "site_config_list",
 *   label = @Translation("Site Config List"),
 *   uri_paths = {
 *     "canonical" = "/api/site-config",
 *   }
 * )
 *
 */
class SiteConfigListResource extends SiteConfigResourceBase {

  protected function getData(?string $id = NULL): array {
    return $this->siteConfigService->getSiteConfig();
  }

}
