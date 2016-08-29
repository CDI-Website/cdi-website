<?php
/**
 * @file
 * Contains \Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Pinterest.
 */

namespace Drupal\social_media_links\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;

/**
 * Provides 'pinterest' platform.
 *
 * @Platform(
 *   id = "pinterest",
 *   name = @Translation("Pinterest"),
 *   urlPrefix = "http://www.pinterest.com/",
 * )
 */
class Pinterest extends PlatformBase {}