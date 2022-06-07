<?php

namespace Drupal\filehash\Commands;

use Drupal\filehash\Batch\CleanBatch;
use Drupal\filehash\Batch\GenerateBatch;
use Drush\Commands\DrushCommands;

/**
 * Drush 9 integration for File Hash module.
 */
class FileHashCommands extends DrushCommands {

  /**
   * Generate hashes for existing files.
   *
   * @aliases fgen,filehash-generate
   * @command filehash:generate
   * @usage drush filehash:generate
   *   Generate hashes for existing files.
   */
  public function generate(): void {
    batch_set(GenerateBatch::createBatch());
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

  /**
   * Remove database columns for disabled hash algorithms.
   *
   * @aliases filehash-clean
   * @command filehash:clean
   * @usage drush filehash:clean
   */
  public function clean(): void {
    batch_set(CleanBatch::createBatch());
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

}
