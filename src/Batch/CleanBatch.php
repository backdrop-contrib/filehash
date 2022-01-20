<?php

namespace Drupal\filehash\Batch;

/**
 * Drops disabled database columns.
 */
class CleanBatch {

  /**
   * Creates the batch definition.
   *
   * @return array
   *   The batch definition.
   */
  public static function createBatch() {
    return [
      'operations' => [['\Drupal\filehash\Batch\CleanBatch::process', []]],
      'finished' => [static::class, 'finished'],
      'title' => t('Processing file hash batch'),
      'init_message' => t('File hash batch is starting.'),
      'progress_message' => t('Please wait...'),
      'error_message' => t('File hash batch has encountered an error.'),
    ];
  }

  /**
   * Returns database columns that are pending delete.
   */
  public static function columns() {
    $columns = [];
    $enabled = filehash_columns();
    foreach (filehash_names() as $column => $name) {
      if (empty($enabled[$column]) && \Drupal::database()->schema()->fieldExists('filehash', $column)) {
        $columns[$column] = $name;
      }
    }
    return $columns;
  }

  /**
   * Batch process callback.
   */
  public static function process(&$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['sandbox']['count'] = count(self::columns());
    }
    $enabled = filehash_columns();
    foreach (filehash_names() as $column => $name) {
      if (empty($enabled[$column]) && \Drupal::database()->schema()->fieldExists('filehash', $column)) {
        \Drupal::database()->schema()->dropField('filehash', $column);
        $context['message'] = t('Dropped %name column.', ['%name' => $name]);
        $context['results']['processed']++;
        break;
      }
    }
    $context['finished'] = $context['sandbox']['count'] ? $context['results']['processed'] / $context['sandbox']['count'] : 1;
  }

  /**
   * Batch finish callback.
   */
  public static function finished($success, $results, $operations) {
    $variables = ['@processed' => $results['processed']];
    if ($success) {
      \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural($results['processed'], 'Processed @processed hash algorithm column.', 'Processed @processed hash algorithm columns.', $variables));
    }
    else {
      \Drupal::messenger()->addWarning(\Drupal::translation()->formatPlural($results['processed'], 'An error occurred after processing @processed hash algorithm column.', 'An error occurred after processing @processed hash algorithm columns.', $variables));
    }
  }

}
