<?php

namespace Drupal\filehash;

use Drupal\file\FileInterface;

/**
 * Basic definitions for File Hash module.
 */
interface FileHashInterface {

  /**
   * Setting for strict-level dedupe (includes temporary files).
   */
  const STRICT_DEDUPE = 2;

  /**
   * Adds missing database columns.
   */
  public function addColumns(): void;

  /**
   * Returns array of enabled File Hash algorithm identifiers.
   *
   * @return string[]
   *   Enabled File Hash algorithms.
   */
  public function columns(): array;

  /**
   * Implements hook_entity_base_field_info().
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   Array of base field definitions.
   */
  public function entityBaseFieldInfo(): array;

  /**
   * Implements hook_entity_storage_load().
   *
   * Generates hash if it does not already exist for the file.
   *
   * @param \Drupal\file\FileInterface[] $files
   *   Array of file entities.
   */
  public function entityStorageLoad(array $files): void;

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  public function filePresave(FileInterface $file): void;

  /**
   * Calculates the file hashes and sets values in the file object.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file object.
   * @param string[]|null $columns
   *   Array of File Hash algorithm identifiers.
   * @param bool $original
   *   Whether or not to set the original file hash.
   */
  public function hash(FileInterface $file, ?array $columns = NULL, bool $original = FALSE): void;

  /**
   * Returns TRUE if file should be hashed.
   */
  public function shouldHash(FileInterface $file): bool;

  /**
   * Returns array of valid File Hash algorithm identifiers.
   *
   * @return string[]
   *   Hash algorithm identifiers.
   */
  public static function keys(): array;

  /**
   * Returns array of hash algorithm hexadecimal output lengths.
   *
   * @return int[]
   *   Hash algorithm output lengths.
   */
  public static function lengths(): array;

  /**
   * Returns array of human-readable hash algorithm names.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Hash algorithm names.
   */
  public static function names(): array;

}
