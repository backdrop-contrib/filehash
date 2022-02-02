<?php

namespace Drupal\filehash;

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
  public function addColumns();

  /**
   * Returns array of enabled File Hash algorithm identifiers.
   */
  public function columns();

  /**
   * Returns file ID for any duplicates.
   */
  public function duplicateLookup($column, $file, $strict = FALSE, $original = FALSE);

  /**
   * Calculates the file hashes.
   */
  public function hash($file, $column = NULL, $original = FALSE);

  /**
   * Returns array of field descriptions.
   */
  public static function descriptions();

  /**
   * Validates File Hash algorithm config.
   */
  public static function intersect($config);

  /**
   * Returns array of valid File Hash algorithm identifiers.
   */
  public static function keys();

  /**
   * Returns array of field labels.
   */
  public static function labels();

  /**
   * Returns array of hash algorithm hexadecimal output lengths.
   */
  public static function lengths();

  /**
   * Returns array of human-readable hash algorithm names.
   */
  public static function names();

  /**
   * Returns array of field descriptions.
   */
  public static function originalDescriptions();

  /**
   * Returns array of field labels.
   */
  public static function originalLabels();

}
