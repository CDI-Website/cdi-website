<?php
/**
 * @file
 * Contains \Drupal\cdi_expanding_search_block\Plugin\Block\SearchBlock.
 */
namespace Drupal\cdi_expanding_search_block\Plugin\Block;
use Drupal\Core\Block\BlockBase;
/**
 * Provides a 'cdi_expanding_search_block' block.
 *
 * @Block(
 *   id = "cdi_expanding_search_block",
 *   admin_label = @Translation("Search block"),
 *   category = @Translation("Search button that animates a field display")
 * )
 */
class SearchBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'cdi_expanding_search_block',
      '#attached' => [
        'library' => ['cdi_expanding_search_block/cdi_expanding_search_block']
      ]
    );
  }
}