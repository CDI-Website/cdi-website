<?php
/**
 * @file
 * Provides Drupal\social_media_links\PlatformInterface
 */

namespace Drupal\social_media_links;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines an interface for social media links platform plugins.
 */
interface PlatformInterface extends PluginInspectionInterface {

  /**
   * Return the name of the icon. In most cases, the icon name is the
   * id of the platform.
   *
   * @return string
   */
  public function getIconName();

  /**
   * Return the name of the platform.
   *
   * @return string
   */
  public function getName();

  /**
   * Return the url prefix of the platform.
   *
   * @return string
   */
  public function getUrlPrefix();

  /**
   * Return the url suffix of the platform.
   *
   * @return string
   */
  public function getUrlSuffix();

  /**
   * Return the full url, including urlPrefix, user value and urlSuffix
   * This method is useful to change the url to match platform specific
   * requirements.
   * E.g.: "mailto:VALUE" for email platform or "user-path:/" for internal urls.
   *
   * @return \Drupal\Core\Url
   */
  public function getUrl();

  /**
   * Generates the final url for the output.
   *
   * @param Url $url
   *   A Url object with the full plattform url.
   *
   * @return string
   */
  public function generateUrl(Url $url);

  /**
   * Validates the user input of a platform before the value is saved.
   *
   * @return mixed
   */
  public static function validateValue(array &$element, FormStateInterface $form_state, array $form);

}
