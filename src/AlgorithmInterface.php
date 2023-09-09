<?php

namespace Drupal\filehash;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Hash algorithm interface.
 */
interface AlgorithmInterface {

  /**
   * Returns the human-readable hash algorithm name.
   */
  public function getName(): TranslatableMarkup;

  /**
   * Returns the PHP hash algorithm identifier.
   *
   * Converts the safe hash algorithm identifiers used by this module to the
   * hash algorithm identifers actually used by PHP, which may contain slashes,
   * dashes, etc.
   *
   * @return ?string
   *   The PHP hash algorithm identifier, or null if not applicable.
   */
  public function getHashAlgo(): ?string;

  /**
   * Returns the hash algorithm hexadecimal output length.
   */
  public function getHexadecimalLength(): int;

  /**
   * Returns the mechanism used by this hash algorithm.
   */
  public function getMechanism(): Mechanism;

  /**
   * Initialize a hash for streaming.
   */
  public function hashInit(): \HashContext|string|NULL;

  /**
   * Complete the hash.
   */
  public function hashUpdate(\HashContext|string &$state, string $data): bool;

  /**
   * Finalize an incremental hash and return resulting digest.
   */
  public function hashFinal(\HashContext|string &$state): string;

}
