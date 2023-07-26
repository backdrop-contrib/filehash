<?php

namespace Drupal\filehash\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * @FieldFormatter(
 *   id = "filehash",
 *   label = @Translation("File Hash"),
 *   field_types = {
 *     "filehash",
 *   },
 * )
 */
class FileHashFormatter extends StringFormatter {

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return mixed[]
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $item->getString(),
      '#attributes' => ['class' => ['filehash-value']],
      '#attached' => ['library' => ['filehash/field']],
    ];
  }

}
