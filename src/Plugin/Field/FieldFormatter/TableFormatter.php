<?php

namespace Drupal\filehash\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation of the 'filehash_table' formatter.
 *
 * @FieldFormatter(
 *   id = "filehash_table",
 *   label = @Translation("Table of files with hashes"),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class TableFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $names = filehash_names();
      $header = [
        $this->t('Attachment'),
        $this->t('Size'),
        $this->t('@algo hash', ['@algo' => $names[$this->getSetting('algo')]]),
      ];
      $rows = [];
      foreach ($files as $file) {
        if (isset($file->_referringItem)) {
          $item = $file->_referringItem;
        }
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#description' => ($this->getSetting('use_description_as_link_text') && isset($item)) ? $item->description : NULL,
              '#cache' => ['tags' => $file->getCacheTags()],
            ],
          ],
          ['data' => format_size(method_exists($file, 'getSize') ? $file->getSize() : 0)],
          [
            'data' => [
              '#markup' => (isset($file->filehash) && isset($file->filehash[$this->getSetting('algo')])) ? substr(chunk_split($file->filehash[$this->getSetting('algo')], 1, '<wbr />'), 0, -7) : '',
            ],
          ],
        ];
      }

      $elements[0] = [
        '#theme' => 'table__filehash_formatter_table',
        '#header' => $header,
        '#rows' => $rows,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $columns = filehash_columns();
    $settings['algo'] = array_pop($columns);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $names = filehash_names();
    $options = [];
    foreach (filehash_columns() as $column) {
      $options[$column] = $names[$column];
    }
    $form['algo'] = [
      '#title' => $this->t('Hash algorithm'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('algo'),
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $names = filehash_names();
    if (isset($names[$this->getSetting('algo')])) {
      $summary[] = $this->t('@algo hash', ['@algo' => $names[$this->getSetting('algo')]]);
    }
    return $summary;
  }

}
