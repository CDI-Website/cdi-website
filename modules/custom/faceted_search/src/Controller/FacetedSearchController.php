<?php

namespace Drupal\faceted_search\Controller;

class FacetedSearchController {
  public function index() {
    return array(
      '#name' => 'Faceted Search',
      '#theme' => 'faceted_search',
      '#attached' => [
        'library' => ['faceted_search/faceted_search']
      ]
    );
  }
}