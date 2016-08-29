<?php
/**
 * @file
 * Contains \Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Linkedin.
 */

namespace Drupal\social_media_links\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;

/**
 * Provides 'linkedin' platform.
 *
 * @Platform(
 *   id = "linkedin",
 *   name = @Translation("LinkedIn"),
 *   urlPrefix = "http://www.linkedin.com/",
 * )
 */
class Linkedin extends PlatformBase {}