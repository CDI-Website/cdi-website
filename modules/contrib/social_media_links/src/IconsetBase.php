<?php
/**
 * @file
 * Provides Drupal\social_media_links\IconsetBase.
 */

namespace Drupal\social_media_links;

use Drupal\Core\Plugin\PluginBase;

abstract class IconsetBase extends PluginBase implements IconsetInterface {

  protected $path = '';

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $iconsetFinderService = \Drupal::service('social_media_links.finder');
    $this->path = $iconsetFinderService->getPath($plugin_id);
  }

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getPublisher() {
    return $this->pluginDefinition['publisher'];
  }

  public function getPublisherUrl() {
    return $this->pluginDefinition['publisherUrl'];
  }

  public function getDownloadUrl() {
    return $this->pluginDefinition['downloadUrl'];
  }

  public function getPath() {
    return $this->path;
  }

  public function getLibrary() {
    return NULL;
  }

  public function getIconElement($platform, $style) {
    $iconName = $platform->getIconName();
    $path = $this->getIconPath($iconName, $style);

    $icon = array(
      '#theme' => 'image',
      '#uri' => $path,
    );

    return $icon;
  }

  public static function explodeStyle($style, $key = FALSE) {
    $exploded = explode(':', $style);

    if ($key) {
      return $exploded[$key];
    }

    return array(
      'iconset' => isset($exploded[0]) ? $exploded[0] : '',
      'style' => isset($exploded[1]) ? $exploded[1] : '',
    );
  }

}
