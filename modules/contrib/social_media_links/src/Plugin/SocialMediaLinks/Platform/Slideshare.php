<?php
/**
 * @file
 * Contains \Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Slideshare.
 */

namespace Drupal\social_media_links\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;

/**
 * Provides 'slideshare' platform.
 *
 * @Platform(
 *   id = "slideshare",
 *   name = @Translation("SlideShare"),
 *   urlPrefix = "http://www.slideshare.net/",
 * )
 */
class Slideshare extends PlatformBase {}