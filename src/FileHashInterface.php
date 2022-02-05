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
   * Returns array of enabled File Hash algorithm identifiers, keyed by KEYS.
   */
  public function columns(): array;

  /**
   * Returns file ID for any duplicates.
   */
  public function duplicateLookup(string $column, FileInterface $file, bool $strict = FALSE, bool $original = FALSE): ?string;

  /**
   * Implements hook_entity_base_field_info().
   */
  public function entityBaseFieldInfo(): array;

  /**
   * Implements hook_entity_storage_load().
   *
   * Generates hash if it does not already exist for the file.
   */
  public function entityStorageLoad($files): void;

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  public function filePresave(FileInterface $file): void;

  /**
   * Checks that file is not a duplicate.
   */
  public function validateDedupe(FileInterface $file, bool $strict = FALSE, bool $original = FALSE): array;

  /**
   * Calculates the file hashes and sets values in the file object.
   */
  public function hash($file, ?string $column = NULL, bool $original = FALSE): void;

  /**
   * Returns array of field descriptions, keyed by KEYS.
   */
  public static function descriptions(): array;

  /**
   * Validates File Hash algorithm config, removing any invalid elements.
   */
  public static function intersect($config): array;

  /**
   * Returns array of valid File Hash algorithm identifiers, keyed by KEYS.
   */
  public static function keys(): array;

  /**
   * Returns array of field labels, keyed by KEYS.
   */
  public static function labels(): array;

  /**
   * Returns array of hash algorithm hexadecimal output lengths, keyed by KEYS.
   */
  public static function lengths(): array;

  /**
   * Returns array of human-readable hash algorithm names, keyed by KEYS.
   */
  public static function names(): array;

  /**
   * Returns array of field descriptions, keyed by KEYS.
   */
  public static function originalDescriptions(): array;

  /**
   * Returns array of field labels, keyed by KEYS.
   */
  public static function originalLabels(): array;

}
