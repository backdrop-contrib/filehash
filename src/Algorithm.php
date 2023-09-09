<?php

/**
 * @file
 * Provides the set of available hash algorithms.
 */

namespace Drupal\filehash;

use Drupal\Core\StringTranslation\TranslatableMarkup;

enum Algorithm: string implements AlgorithmInterface {

  case Blake2b128 = 'blake2b_128';
  case Blake2b160 = 'blake2b_160';
  case Blake2b224 = 'blake2b_224';
  case Blake2b256 = 'blake2b_256';
  case Blake2b384 = 'blake2b_384';
  case Blake2b512 = 'blake2b_512';
  case Md5 = 'md5';
  case Sha1 = 'sha1';
  case Sha224 = 'sha224';
  case Sha256 = 'sha256';
  case Sha384 = 'sha384';
  case Sha512224 = 'sha512_224';
  case Sha512256 = 'sha512_256';
  case Sha512 = 'sha512';
  case Sha3224 = 'sha3_224';
  case Sha3256 = 'sha3_256';
  case Sha3384 = 'sha3_384';
  case Sha3512 = 'sha3_512';

  /**
   * Returns the hash algorithm binary output length.
   */
  public function getByteLength(): int {
    return $this->getHexadecimalLength() / 2;
  }

  /**
   * {@inheritdoc}
   */
  public function getHashAlgo(): ?string {
    return match ($this->getMechanism()) {
      Mechanism::Hash => str_replace(['sha3_', 'sha512_'], ['sha3-', 'sha512/'], $this->value),
      Mechanism::Sodium => NULL,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getHexadecimalLength(): int {
    return match ($this) {
      self::Blake2b128, self::Md5 => 32,
      self::Blake2b160, self::Sha1 => 40,
      self::Blake2b224, self::Sha224, self::Sha512224, self::Sha3224 => 56,
      self::Blake2b256, self::Sha256, self::Sha512256, self::Sha3256 => 64,
      self::Blake2b384, self::Sha384, self::Sha3384 => 96,
      self::Blake2b512, self::Sha512, self::Sha3512 => 128,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getMechanism(): Mechanism {
    return match ($this) {
      self::Blake2b128, self::Blake2b160, self::Blake2b224, self::Blake2b256, self::Blake2b384, self::Blake2b512 => Mechanism::Sodium,
      default => Mechanism::Hash,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): TranslatableMarkup {
    return match ($this) {
      self::Blake2b128 => t('BLAKE2b-128'),
      self::Blake2b160 => t('BLAKE2b-160'),
      self::Blake2b224 => t('BLAKE2b-224'),
      self::Blake2b256 => t('BLAKE2b-256'),
      self::Blake2b384 => t('BLAKE2b-384'),
      self::Blake2b512 => t('BLAKE2b-512'),
      self::Md5 => t('MD5'),
      self::Sha1 => t('SHA-1'),
      self::Sha224 => t('SHA-224'),
      self::Sha256 => t('SHA-256'),
      self::Sha384 => t('SHA-384'),
      self::Sha512224 => t('SHA-512/224'),
      self::Sha512256 => t('SHA-512/256'),
      self::Sha512 => t('SHA-512'),
      self::Sha3224 => t('SHA3-224'),
      self::Sha3256 => t('SHA3-256'),
      self::Sha3384 => t('SHA3-384'),
      self::Sha3512 => t('SHA3-512'),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function hashInit(): \HashContext|string|NULL {
    return match ($this->getMechanism()) {
      Mechanism::Hash => hash_init($this->getHashAlgo()),
      Mechanism::Sodium => function_exists('sodium_crypto_generichash_init') ? sodium_crypto_generichash_init('', $this->getByteLength()) : NULL,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function hashUpdate(\HashContext|string &$state, string $data): bool {
    return match ($this->getMechanism()) {
      Mechanism::Hash => hash_update($state, $data),
      Mechanism::Sodium => sodium_crypto_generichash_update($state, $data),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function hashFinal(\HashContext|string &$state): string {
    return match ($this->getMechanism()) {
      Mechanism::Hash => hash_final($state),
      Mechanism::Sodium => bin2hex(sodium_crypto_generichash_final($state, $this->getByteLength())),
    };
  }

}
