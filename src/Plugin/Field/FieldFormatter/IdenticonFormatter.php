<?php

namespace Drupal\filehash\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Identicon\Identicon;

/**
 * Identicon generator (requires third-party dependency).
 *
 * You'll need to run: composer require yzalis/identicon:^2.0
 *
 * @FieldFormatter(
 *   id = "filehash_identicon",
 *   label = @Translation("Identicon"),
 *   field_types = {
 *     "filehash",
 *   },
 * )
 */
class IdenticonFormatter extends StringFormatter {

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return mixed[]
   *   The output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'alt' => $item->getString(),
        'title' => $item->getString(),
        'class' => ['filehash-identicon'],
      ],
    ];
    if (class_exists(Identicon::class)) {
      $identicon = new Identicon();
      $element['#attributes']['src'] = $identicon->getImageDataUri($item->getString());
    }
    else {
      $element['#attributes']['title'] = $this->t('Identicon generator not found.');
    }
    return $element;
  }

}
