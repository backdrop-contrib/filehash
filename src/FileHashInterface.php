<?php

namespace Drupal\filehash;

use Drupal\file\FileInterface;

/**
 * Basic definitions for File Hash module.
 */
interface FileHashInterface {

  /**
   * Array of valid File Hash algorithm identifiers.
   */
  const KEYS = [
    'blake2b_128',
    'blake2b_160',
    'blake2b_224',
    'blake2b_256',
    'blake2b_384',
    'blake2b_512',
    'md5',
    'sha1',
    'sha224',
    'sha256',
    'sha384',
    'sha512_224',
    'sha512_256',
    'sha512',
    'sha3_224',
    'sha3_256',
    'sha3_384',
    'sha3_512',
  ];

  /**
   * Array of hexadecimal lengths for each supported hash algorithm.
   */
  const LENGTHS = [
    32, 40, 56, 64, 96, 128, 32, 40, 56, 64, 96, 56, 64, 128, 56, 64, 96, 128,
  ];

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
