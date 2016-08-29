<?php /**
 * @file
 * Contains \Drupal\background_image_formatter\Plugin\Field\FieldFormatter\BackgroundImageFormatter.
 */

namespace Drupal\background_image_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the background_image_formatter.
 *
 * @FieldFormatter(
 *  id = "background_image_formatter",
 *  label = @Translation("Background Image"),
 *  field_types = {"image"}
 * )
 */
class BackgroundImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'background_image_output_type' => 'inline',
      'background_image_selector' => '',
        'background_image_text_overlay' => '&nbsp;'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = [];

    $image_styles = image_style_options(FALSE);

    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#options' => $image_styles,
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#description' => t('Select the image style to use.'),
    ];

    $element['background_image_output_type'] = [
      '#title' => t('Output To'),
      '#type' => 'select',
      '#options' => [
        'inline' => t('Write background-image to inline style attribute'),
        'css' => t('Write background-image to CSS selector'),
      ],
      '#default_value' => $this->getSetting('background_image_output_type'),
      '#required' => TRUE,
      '#description' => t('Define how background-image will be printed to the dom.'),
    ];

    $element['background_image_selector'] = [
      '#title' => t('CSS Selector'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('background_image_selector'),
      '#required' => FALSE,
      '#description' => t('CSS selector that image(s) are attached to.'),
    ];

      $element['background_image_text_overlay'] = [
          '#title' => t('CSS Selector'),
          '#type' => 'textfield',
          '#default_value' => $this->getSetting('background_image_text_overlay'),
          '#required' => FALSE,
          '#description' => t('Text Overlayed over background image'),
      ];

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = array();

    $image_styles = image_style_options(FALSE);

    unset($image_styles['']);

    $select_style = $this->getSetting('image_style');

    if (isset($image_styles[$select_style])) {
      $summary[] = t('URL for image style: @style', ['@style' => $image_styles[$select_style]]);
    }
    else {
      $summary[] = t('Original image');
    }

    $summary[] = t('Output type: @output_type', ['@output_type' => $this->getSetting('background_image_output_type')]);


    $summary[] = t('The CSS selector <code>@background_image_selector</code> will be created with the image set to the background-image property.', [
      '@background_image_selector' => $this->getSetting('background_image_selector') . '_id',
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    $image_style = NULL;

    if (!$this->isBackgroundImageDisplay()) {
      return $elements;
    }

    $image_style = $this->getSetting('image_style');


    if (!empty($image_style)) {
      $image_style = ImageStyle::load($image_style);
    }

    foreach ($items as $delta => $item) {

      if (!$item->entity) {
        continue;
      }

      $image_uri = $item->entity->url();

      $id = $item->entity->id();

      if ($image_style) {
        $image_uri = $item->entity->getFileUri();

        $image_uri = ImageStyle::load($image_style->getName())->buildUrl($image_uri);
      }

      $selector = strip_tags($this->getSetting('background_image_selector'));

      $selector .= '_' . $id;

      $theme = array(
        '#background_image_selector' => $selector,
        '#image_uri' => $image_uri,
      );

      switch ($this->getSetting('background_image_output_type')) {
        case 'css':

          $data = [
            '#tag' => 'style',
            '#value' => $this->generateCssString($theme),
          ];

          $elements['#attached']['html_head'][] = [
            $data,
            'background_image_formatter_' . $id,
          ];

          break;

        case 'inline':

          $theme['#theme'] = 'background_image_formatter_inline';

          $elements[$delta] = array(
            '#markup' => \Drupal::service('renderer')->render($theme),
          );

          break;
      }
    }

    return $elements;
  }

  protected function isBackgroundImageDisplay() {
    return $this->getPluginId() == 'background_image_formatter';
  }

  protected function generateCssString($theme) {
    return $theme['#background_image_selector'] . ' {background-image: url("' . $theme['#image_uri'] . '");}';
  }

}
