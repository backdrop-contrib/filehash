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
  foreach (filehash_algos() as $algo) {
    $info['tokens']['file']["filehash-$algo"] = [
      'name' => t('@algo hash', ['@algo' => $names[$algo]]),
      'description' => t('The @algo hash of the file.', ['@algo' => $names[$algo]]),
    ];
    $info['tokens']['file']["filehash-$algo-pair-1"] = [
      'name' => t('@algo hash: Initial pair', ['@algo' => $names[$algo]]),
      'description' => t('The @algo hash of the file: first and second characters.', ['@algo' => $names[$algo]]),
    ];
    $info['tokens']['file']["filehash-$algo-pair-2"] = [
      'name' => t('@algo hash: Second pair', ['@algo' => $names[$algo]]),
      'description' => t('The @algo hash of the file: third and fourth characters.', ['@algo' => $names[$algo]]),
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
    foreach (filehash_algos() as $algo) {
      // Generate the file hash if it is needed but does not yet exist.
      if (empty($data['file']->filehash[$algo])) {
        foreach ($tokens as $name => $original) {
          if (strpos($name, "filehash-$algo") === 0) {
            $data['file']->filehash[$algo] = hash_file($algo, $data['file']->getFileUri());
            break;
          }
        }
      }
      if (isset($tokens["filehash-$algo"])) {
        $replacements[$tokens["filehash-$algo"]] = $data['file']->filehash[$algo];
      }
      if (isset($tokens["filehash-$algo-pair-1"])) {
        $replacements[$tokens["filehash-$algo-pair-1"]] = substr($data['file']->filehash[$algo], 0, 2);
      }
      if (isset($tokens["filehash-$algo-pair-2"])) {
        $replacements[$tokens["filehash-$algo-pair-2"]] = substr($data['file']->filehash[$algo], 2, 2);
      }
    }
  }
  return $replacements;
}
