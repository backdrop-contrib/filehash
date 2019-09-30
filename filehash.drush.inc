<?php

/**
 * @file
 * Drush integration for File Hash module.
 */

use Drupal\filehash\Batch\GenerateBatch;

/**
 * Implements hook_drush_command().
 */
function filehash_drush_command() {
  $commands['filehash-generate'] = [
    'description' => 'Generate hashes for existing files.',
    'examples' => [
      'drush filehash-generate',
    ],
    'aliases' => ['fgen'],
  ];

  return $commands;
}

/**
 * Drush callback for filehash-generate command.
 */
function drush_filehash_generate() {
  batch_set(GenerateBatch::createBatch());
  $batch =& batch_get();
  $batch['progressive'] = FALSE;
  drush_backend_batch_process();
}
