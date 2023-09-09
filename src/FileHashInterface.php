<?php

namespace Drupal\filehash;

use Drupal\Core\StringTranslation\TranslatableMarkup;
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
   * Returns array of valid File Hash algorithm identifiers.
   *
   * @return string[]
   *   Hash algorithm identifiers.
   */
  public static function getAlgorithms(): array;

  /**
   * Returns array of enabled File Hash algorithm names.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Enabled File Hash algorithm names.
   */
  public function getEnabledAlgorithmNames(): array;

  /**
   * Returns array of enabled File Hash algorithm identifiers.
   *
   * @return string[]
   *   Enabled File Hash algorithms.
   */
  public function getEnabledAlgorithms(): array;

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
   * Returns the hash algorithm label.
   */
  public static function getAlgorithmLabel(string $algorithm, bool $original = FALSE): TranslatableMarkup;

  /**
   * Returns the hash algorithm hexadecimal output length.
   */
  public static function getAlgorithmLength(string $algorithm): int;

  /**
   * Returns human-readable hash algorithm name.
   */
  public static function getAlgorithmName(string $algorithm): TranslatableMarkup;

  /**
   * Returns array of available File Hash algorithm names.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   File Hash algorithm human-readable names.
   */
  public static function getAlgorithmNames(): array;

}
