<?php
/**
 * @file
 * Provides Drupal\social_media_links\IconsetInterface
 */

namespace Drupal\social_media_links;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for social media links iconset plugins.
 */
interface IconsetInterface extends PluginInspectionInterface {

  /**
   * Return the name of the iconset.
   *
   * @return string
   */
  public function getName();

  /**
   * Return the name of the publisher.
   *
   * @return string
   */
  public function getPublisher();

  /**
   * Return the url of the publisher.
   *
   * @return string
   */
  public function getPublisherUrl();

  /**
   * Return the url to download the iconset.
   *
   * @return string
   */
  public function getDownloadUrl();

  /**
   * Return the available styles.
   *
   * @return array
   */
  public function getStyle();

  /**
   * Return the path of an icon for the given platform (iconName) and style.
   *
   * @param $iconName
   * @param $style
   *
   * @return string
   */
  public function getIconPath($iconName, $style);

}
