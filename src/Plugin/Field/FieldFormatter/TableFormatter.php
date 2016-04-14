<?php

/**
 * @file
 * Contains \Drupal\filehash\Plugin\Field\FieldFormatter\TableFormatter.
 */

namespace Drupal\filehash\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

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
class TableFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items)) {
      $names = filehash_names();
      $header = [t('Attachment'), t('Size'), t('@algo hash', ['@algo' => $names[$this->getSetting('algo')]])];
      $rows = [];
      foreach ($files as $delta => $file) {
        $rows[] = [
          ['data' => [
            '#theme' => 'file_link',
            '#file' => $file,
            '#cache' => ['tags' => $file->getCacheTags()],
          ]],
          ['data' => format_size($file->getSize())],
          ['data' => [
            '#markup' => substr(chunk_split($file->filehash[$this->getSetting('algo')], 1, '<wbr />'), 0, -7),
          ]],
        ];
      }

      $elements[0] = [];
      if (!empty($rows)) {
        $elements[0] = [
          '#theme' => 'table__filehash_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $algos = filehash_algos();
    return ['algo' => array_pop($algos)];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $names = filehash_names();
    $options = [];
    foreach (filehash_algos() as $algo) {
      $options[$algo] = $names[$algo];
    }
    $element['algo'] = [
      '#title' => t('Hash algorithm'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('algo'),
      '#options' => $options,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $algos = filehash_names();
    $settings = [];
    if (isset($algos[$this->getSetting('algo')])) {
      $settings[] = t('@algo hash', ['@algo' => $algos[$this->getSetting('algo')]]);
    }
    return $settings;
  }

}
