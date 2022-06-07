<?php

/**
 * @file
 * Tokens for file hash module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 *
 * @phpstan-ignore-next-line Hook implementations should not duplicate @return
 * documentation.
 */
function filehash_token_info() {
  $info = [];
  $names = Drupal::service('filehash')->names();
  foreach (Drupal::service('filehash')->columns() as $column) {
    $info['tokens']['file']["filehash-$column"] = [
      'name' => t('@algo hash', ['@algo' => $names[$column]]),
      'description' => t('The @algo hash of the file.', ['@algo' => $names[$column]]),
    ];
    $info['tokens']['file']["filehash-$column-pair-1"] = [
      'name' => t('@algo hash: Initial pair', ['@algo' => $names[$column]]),
      'description' => t('The @algo hash of the file: first and second characters.', ['@algo' => $names[$column]]),
    ];
    $info['tokens']['file']["filehash-$column-pair-2"] = [
      'name' => t('@algo hash: Second pair', ['@algo' => $names[$column]]),
      'description' => t('The @algo hash of the file: third and fourth characters.', ['@algo' => $names[$column]]),
    ];
  }
  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @phpstan-ignore-next-line Hook implementations should not duplicate @param
 * documentation.
 */
function filehash_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'file' && !empty($data['file'])) {
    foreach (Drupal::service('filehash')->columns() as $column) {
      if (isset($tokens["filehash-$column"])) {
        $replacements[$tokens["filehash-$column"]] = $data['file']->{$column}->value ?? '';
      }
      if (isset($tokens["filehash-$column-pair-1"])) {
        $replacements[$tokens["filehash-$column-pair-1"]] = substr($data['file']->{$column}->value ?? '', 0, 2);
      }
      if (isset($tokens["filehash-$column-pair-2"])) {
        $replacements[$tokens["filehash-$column-pair-2"]] = substr($data['file']->{$column}->value ?? '', 2, 2);
      }
    }
  }
  return $replacements;
}
