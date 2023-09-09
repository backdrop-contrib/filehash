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
  foreach (Drupal::service('filehash')->getEnabledAlgorithmNames() as $algorithm => $name) {
    $info['tokens']['file']["filehash-$algorithm"] = [
      'name' => Drupal::service('filehash')->getAlgorithmLabel($algorithm),
      'description' => t('The @algo hash of the file.', ['@algo' => $name]),
    ];
    $info['tokens']['file']["filehash-$algorithm-pair-1"] = [
      'name' => t('@algo hash: Initial pair', ['@algo' => $name]),
      'description' => t('The @algo hash of the file: first and second characters.', ['@algo' => $name]),
    ];
    $info['tokens']['file']["filehash-$algorithm-pair-2"] = [
      'name' => t('@algo hash: Second pair', ['@algo' => $name]),
      'description' => t('The @algo hash of the file: third and fourth characters.', ['@algo' => $name]),
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
    foreach (Drupal::service('filehash')->getEnabledAlgorithms() as $algorithm) {
      if (isset($tokens["filehash-$algorithm"])) {
        $replacements[$tokens["filehash-$algorithm"]] = $data['file']->{$algorithm}->value ?? '';
      }
      if (isset($tokens["filehash-$algorithm-pair-1"])) {
        $replacements[$tokens["filehash-$algorithm-pair-1"]] = substr($data['file']->{$algorithm}->value ?? '', 0, 2);
      }
      if (isset($tokens["filehash-$algorithm-pair-2"])) {
        $replacements[$tokens["filehash-$algorithm-pair-2"]] = substr($data['file']->{$algorithm}->value ?? '', 2, 2);
      }
    }
  }
  return $replacements;
}
