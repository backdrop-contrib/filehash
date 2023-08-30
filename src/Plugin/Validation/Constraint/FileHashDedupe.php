<?php

namespace Drupal\filehash\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports disallowing duplicate files.
 *
 * @Constraint(
 *   id = "FileHashDedupe",
 *   label = @Translation("File hash dedupe", context = "Validation"),
 *   type = "file"
 * )
 */
class FileHashDedupe extends Constraint {

  /**
   * The default validation error message.
   */
  public string $message = 'Sorry, duplicate files are not permitted.';

  /**
   * If TRUE, include the original file hashes in the duplicate search.
   */
  public bool $original = FALSE;

  /**
   * If TRUE, include temporary files in the duplicate search.
   */
  public bool $strict = FALSE;

}
