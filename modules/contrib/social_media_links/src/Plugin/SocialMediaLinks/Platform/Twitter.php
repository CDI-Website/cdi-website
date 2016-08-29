<?php
/**
 * @file
 * Contains \Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Twitter.
 */

namespace Drupal\social_media_links\Plugin\SocialMediaLinks\Platform;

use Drupal\social_media_links\PlatformBase;

/**
 * Provides 'twitter' platform.
 *
 * @Platform(
 *   id = "twitter",
 *   name = @Translation("Twitter"),
 *   urlPrefix = "https://www.twitter.com/",
 * )
 */
class Twitter extends PlatformBase {}