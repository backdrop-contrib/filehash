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
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    if ($files = $this->getEntitiesToView($items)) {
      $names = filehash_names();
      $header = array(t('Attachment'), t('Size'), t('@algo hash', array('@algo' => $names[$this->getSetting('algo')])));
      $rows = array();
      foreach ($files as $delta => $file) {
        $rows[] = array(
          array(
            'data' => array(
              '#theme' => 'file_link',
              '#file' => $file,
              '#cache' => array(
                'tags' => $file->getCacheTags(),
              ),
            ),
          ),
          array('data' => format_size($file->getSize())),
          array('data' => array(
            '#markup' => substr(chunk_split($file->filehash[$this->getSetting('algo')], 1, '<wbr />'), 0, -7)),
          ),
        );
      }

      $elements[0] = array();
      if (!empty($rows)) {
        $elements[0] = array(
          '#theme' => 'table__filehash_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        );
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $algos = filehash_algos();
    return array(
      'algo' => array_pop($algos),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $names = filehash_names();
    $options = array();
    foreach (filehash_algos() as $algo) {
      $options[$algo] = $names[$algo];
    }
    $element['algo'] = array(
      '#title' => t('Hash algorithm'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('algo'),
      '#options' => $options,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $algos = filehash_names();
    $settings = array();
    if (isset($algos[$this->getSetting('algo')])) {
      $settings[] = t('@algo hash', array('@algo' => $algos[$this->getSetting('algo')]));
    }
    return $settings;
  }

}
