<?php

namespace Drupal\theme_explorer\Controller;

class ThemeExplorerController {
  public function index() {
    return array(
      '#name' => 'Theme Explorer',
      '#theme' => 'theme_explorer',
      '#attached' => [
        'library' => ['theme_explorer/theme_explorer']
      ]
    );
  }
}