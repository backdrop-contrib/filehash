<?php

/**
 * @file
 * Tokens for file hash module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function filehash_token_info() {
  $info = [];
  $names = filehash_names();
  foreach (filehash_columns() as $column) {
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
 */
function filehash_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'file' && !empty($data['file'])) {
    foreach (filehash_columns() as $column) {
      if (isset($tokens["filehash-$column"])) {
        $replacements[$tokens["filehash-$column"]] = $data['file']->filehash[$column] ?? '';
      }
      if (isset($tokens["filehash-$column-pair-1"])) {
        $replacements[$tokens["filehash-$column-pair-1"]] = substr($data['file']->filehash[$column] ?? '', 0, 2);
      }
      if (isset($tokens["filehash-$column-pair-2"])) {
        $replacements[$tokens["filehash-$column-pair-2"]] = substr($data['file']->filehash[$column] ?? '', 2, 2);
      }
    }
  }
  return $replacements;
}
