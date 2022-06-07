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
   * Returns file ID for any duplicates.
   */
  public function duplicateLookup(string $column, FileInterface $file, bool $strict = FALSE, bool $original = FALSE): ?string;

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
   * @param string|null $column
   *   File hash algorithm identifier.
   * @param bool $original
   *   Whether or not to set the original file hash.
   */
  public function hash(FileInterface $file, ?string $column = NULL, bool $original = FALSE): void;

  /**
   * Checks that file is not a duplicate.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Array of validation errors.
   */
  public function validateDedupe(FileInterface $file, bool $strict = FALSE, bool $original = FALSE): array;

  /**
   * Returns array of field descriptions.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Field descriptions.
   */
  public static function descriptions(): array;

  /**
   * Validates File Hash algorithm config, removing any invalid elements.
   *
   * @param string[]|null $config
   *   Hash algorithm configuration.
   *
   * @return string[]
   *   Hash algorithm identifiers.
   */
  public static function intersect($config): array;

  /**
   * Returns array of valid File Hash algorithm identifiers.
   *
   * @return string[]
   *   Hash algorithm identifiers.
   */
  public static function keys(): array;

  /**
   * Returns array of field labels.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Field labels.
   */
  public static function labels(): array;

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

  /**
   * Returns array of field descriptions.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Field descriptions.
   */
  public static function originalDescriptions(): array;

  /**
   * Returns array of field labels.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Field labels.
   */
  public static function originalLabels(): array;

}
