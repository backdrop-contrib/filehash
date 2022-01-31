<?php

namespace Drupal\filehash\Batch;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\filehash\FileHash;

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
      'operations' => [[[static::class, 'process'], []]],
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
    foreach (\Drupal::entityDefinitionUpdateManager()->getChangeList() as $entity_type_id => $change_list) {
      if ($entity_type_id === 'file') {
        foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
          if ($change === EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED) {
            // Only add File Hash columns to the list.
            $base_column = preg_replace('/^original_/', '', $field_name);
            if (isset(FileHash::keys()[$base_column])) {
              $columns[$field_name] = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition($field_name, 'file')->getLabel();
            }
          }
        }
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
    $fields = self::columns();
    foreach ($fields as $column => $name) {
      $definition = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition($column, 'file');
      \Drupal::entityDefinitionUpdateManager()->uninstallFieldStorageDefinition($definition);
      $context['message'] = t('Dropped %name column.', ['%name' => $name]);
      $context['results']['processed']++;
      break;
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
