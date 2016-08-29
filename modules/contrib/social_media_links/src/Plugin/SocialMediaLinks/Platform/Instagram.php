<?php
/**
 * @file
 * Contains \Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Instagram.
 */

namespace Drupal\social_media_links\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;

/**
 * Provides 'instagram' platform.
 *
 * @Platform(
 *   id = "instagram",
 *   name = @Translation("Instagram"),
 *   urlPrefix = "http://www.instagram.com/",
 * )
 */
class Instagram extends PlatformBase {}