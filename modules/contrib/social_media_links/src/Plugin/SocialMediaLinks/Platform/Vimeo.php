<?php
/**
 * @file
 * Contains \Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Vimeo.
 */

namespace Drupal\social_media_links\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;

/**
 * Provides 'vimeo' platform.
 *
 * @Platform(
 *   id = "vimeo",
 *   name = @Translation("Vimeo"),
 *   urlPrefix = "http://www.vimeo.com/",
 * )
 */
class Vimeo extends PlatformBase {}