<?php
/**
 * @file
 * Provides Drupal\social_media_links\PlatformBase.
 */

namespace Drupal\social_media_links;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

class PlatformBase extends PluginBase implements PlatformInterface {

  protected $value;

  public function getValue() {
    return Html::escape($this->value);
  }

  public function setValue($value) {
    $this->value = $value;
  }

  public function getIconName() {
    return !empty($this->pluginDefinition['iconName']) ? $this->pluginDefinition['iconName'] : $this->pluginDefinition['id'];
  }

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getUrlPrefix() {
    return isset($this->pluginDefinition['urlPrefix']) ? $this->pluginDefinition['urlPrefix'] : '';
  }

  public function getUrlSuffix() {
    return isset($this->pluginDefinition['urlSuffix']) ? $this->pluginDefinition['urlSuffix'] : '';
  }

  public function getUrl() {
    return Url::fromUri($this->getUrlPrefix() . $this->getValue() . $this->getUrlSuffix());
  }

  public function generateUrl(Url $url) {
    return $url->toString();
  }

  public static function validateValue(array &$element, FormStateInterface $form_state, array $form) { }

}
