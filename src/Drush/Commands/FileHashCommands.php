<?php

namespace Drupal\filehash\Drush\Commands;

use Drupal\filehash\Batch\CleanBatch;
use Drupal\filehash\Batch\GenerateBatch;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush 12 integration for File Hash module.
 */
class FileHashCommands extends DrushCommands {

  /**
   * Generate hashes for existing files.
   */
  #[CLI\Command(name: 'filehash:generate', aliases: [
    'fgen', 'filehash-generate',
  ])]
  #[CLI\Usage(name: 'drush filehash:generate', description: 'Generate hashes for existing files.')]
  public function generate(): void {
    batch_set(GenerateBatch::createBatch());
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

  /**
   * Remove database columns for disabled hash algorithms.
   */
  #[CLI\Command(name: 'filehash:clean', aliases: ['filehash-clean'])]
  #[CLI\Usage(name: 'drush filehash:clean')]
  public function clean(): void {
    batch_set(CleanBatch::createBatch());
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

}
