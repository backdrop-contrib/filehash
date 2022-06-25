<?php

namespace Drupal\filehash\Batch;

use Drupal\file\Entity\File;

/**
 * Generates file hashes in bulk.
 */
class GenerateBatch {

  /**
   * Creates the batch definition.
   *
   * @return mixed[]
   *   The batch definition.
   */
  public static function createBatch() {
    return [
      'operations' => [[[static::class, 'process'], []]],
      'finished' => [static::class, 'finished'],
      'title' => t('Processing file hash batch'),
      'init_message' => t('File hash batch is starting.'),
      'progress_message' => t('Please wait...'),
      'error_message' => t('File hash batch has encountered an error.'),
    ];
  }

  /**
   * Returns count of files in file_managed table.
   *
   * @return int
   *   The count of managed files.
   */
  public static function count() {
    return \Drupal::database()->query('SELECT COUNT(*) FROM {file_managed}')->fetchField();
  }

  /**
   * Batch process callback.
   *
   * @param mixed[]|iterable $context
   *   Batch context.
   */
  public static function process(&$context): void {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['updated'] = 0;
      $context['sandbox']['count'] = self::count();
    }
    $files = \Drupal::database()->select('file_managed')
      ->fields('file_managed', ['fid'])
      ->orderBy('fid')
      ->range($context['results']['processed'], 1)
      ->execute();
    foreach ($files as $file) {
      // Fully load file object.
      $file = File::load($file->fid);
      $variables = ['%url' => $file->getFileUri()];
      $context['message'] = t('Generated file hash for %url.', $variables);
      $context['results']['processed']++;
    }
    $context['finished'] = $context['sandbox']['count'] ? $context['results']['processed'] / $context['sandbox']['count'] : 1;
  }

  /**
   * Batch finish callback.
   *
   * @param bool $success
   *   Whether or not the batch succeeded.
   * @param int[] $results
   *   Number of files processed.
   * @param mixed[] $operations
   *   Batch operations.
   */
  public static function finished($success, array $results, array $operations): void {
    $variables = ['@processed' => $results['processed']];
    if ($success) {
      \Drupal::messenger()->addMessage(t('Processed @processed files.', $variables));
    }
    else {
      \Drupal::messenger()->addWarning(t('An error occurred after processing @processed files.', $variables));
    }
  }

}
