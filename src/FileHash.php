<?php

namespace Drupal\filehash;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\file\FileInterface;

/**
 * Provides the File Hash service.
 */
class FileHash implements FileHashInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the File Hash service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDefinitionUpdateManagerInterface $entity_definition_update_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Adds missing database columns.
   */
  public function addColumns() {
    $original = $this->configFactory->get('filehash.settings')->get('original');
    $fields = filehash_entity_base_field_info($this->entityTypeManager->getDefinition('file'));
    foreach ($this->columns() as $column) {
      $this->entityDefinitionUpdateManager->installFieldStorageDefinition($column, 'file', 'file', $fields[$column]);
      if ($original) {
        $this->entityDefinitionUpdateManager->installFieldStorageDefinition("original_$column", 'file', 'file', $fields["original_$column"]);
      }
    }
  }

  /**
   * Returns array of enabled PHP hash algorithm identifiers.
   *
   * Converts the safe algorithm identifiers used by this module to the
   * algorithm identifers actually used by PHP, which may contain slashes,
   * dashes, etc.
   */
  public function algos() {
    return str_replace(['sha3_', 'sha512_'], ['sha3-', 'sha512/'], $this->columns());
  }

  /**
   * Returns array of enabled File Hash algorithm identifiers.
   */
  public function columns() {
    return $this->intersect($this->configFactory->get('filehash.settings')->get('algos'));
  }

  /**
   * Returns file ID for any duplicates.
   */
  public function duplicateLookup($column, $file, $strict = FALSE) {
    $query = $this->entityTypeManager->getStorage('file')->getQuery('AND')
      ->condition($column, $file->{$column}->value);
    if (!$strict) {
      $query->condition('status', FileInterface::STATUS_PERMANENT);
    }
    $results = $query->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    return reset($results) ?: NULL;
  }

  /**
   * Calculates the file hashes.
   */
  public function hash($file, $column = NULL, $original = FALSE) {
    $uri = $file->getFileUri();
    // Nothing to do if file URI is empty.
    if (NULL === $uri || '' === $uri) {
      return;
    }
    // If column is set, only generate that hash.
    $algos = $column ? [$column => $this->algos()[$column]] : $this->algos();
    foreach ($algos as $column => $algo) {
      // Unreadable files will have NULL hash values.
      if (preg_match('/^blake2b_([0-9]{3})$/', $algo, $matches)) {
        $hash = $this->blake2b($uri, $matches[1] / 8) ?: NULL;
      }
      else {
        $hash = hash_file($algo, $uri) ?: NULL;
      }
      $file->set($column, $hash);
      if ($original) {
        $file->set("original_$column", $hash);
      }
    }
  }

  /**
   * Implements hash_file() for the BLAKE2b hash algorithm.
   *
   * Requires the Sodium PHP extension.
   *
   * @return string|false
   *   Same return type as hash_file().
   */
  public static function blake2b($file, $length, $chunk_size = 8192) {
    if (!function_exists('sodium_crypto_generichash_init')) {
      return FALSE;
    }
    $handle = fopen($file, 'rb');
    if (FALSE === $handle) {
      return FALSE;
    }
    $state = sodium_crypto_generichash_init('', $length);
    while ('' !== ($message = fread($handle, $chunk_size))) {
      if (FALSE === $message) {
        return FALSE;
      }
      if (!sodium_crypto_generichash_update($state, $message)) {
        return FALSE;
      }
    }
    if (!feof($handle)) {
      return FALSE;
    }
    fclose($handle);
    return bin2hex(sodium_crypto_generichash_final($state, $length));
  }

  /**
   * Returns array of field descriptions.
   */
  public static function descriptions() {
    return array_combine(static::KEYS, [
      t('The BLAKE2b-128 hash for this file.'),
      t('The BLAKE2b-160 hash for this file.'),
      t('The BLAKE2b-224 hash for this file.'),
      t('The BLAKE2b-256 hash for this file.'),
      t('The BLAKE2b-384 hash for this file.'),
      t('The BLAKE2b-512 hash for this file.'),
      t('The MD5 hash for this file.'),
      t('The SHA-1 hash for this file.'),
      t('The SHA-224 hash for this file.'),
      t('The SHA-256 hash for this file.'),
      t('The SHA-384 hash for this file.'),
      t('The SHA-512/224 hash for this file.'),
      t('The SHA-512/256 hash for this file.'),
      t('The SHA-512 hash for this file.'),
      t('The SHA3-224 hash for this file.'),
      t('The SHA3-256 hash for this file.'),
      t('The SHA3-384 hash for this file.'),
      t('The SHA3-512 hash for this file.'),
    ]);
  }

  /**
   * Validates File Hash algorithm config.
   */
  public static function intersect($config) {
    return array_intersect_assoc($config ?? [], static::keys());
  }

  /**
   * Returns array of valid File Hash algorithm identifiers.
   */
  public static function keys() {
    return array_combine(static::KEYS, static::KEYS);
  }

  /**
   * Returns array of field labels.
   */
  public static function labels() {
    return array_combine(static::KEYS, [
      t('BLAKE2b-128 hash'),
      t('BLAKE2b-160 hash'),
      t('BLAKE2b-224 hash'),
      t('BLAKE2b-256 hash'),
      t('BLAKE2b-384 hash'),
      t('BLAKE2b-512 hash'),
      t('MD5 hash'),
      t('SHA-1 hash'),
      t('SHA-224 hash'),
      t('SHA-256 hash'),
      t('SHA-384 hash'),
      t('SHA-512/224 hash'),
      t('SHA-512/256 hash'),
      t('SHA-512 hash'),
      t('SHA3-224 hash'),
      t('SHA3-256 hash'),
      t('SHA3-384 hash'),
      t('SHA3-512 hash'),
    ]);
  }

  /**
   * Returns array of hash algorithm hexadecimal output lengths.
   */
  public static function lengths() {
    return array_combine(static::KEYS, [
      32, 40, 56, 64, 96, 128, 32, 40, 56, 64, 96, 56, 64, 128, 56, 64, 96, 128,
    ]);
  }

  /**
   * Returns array of human-readable hash algorithm names.
   */
  public static function names() {
    return array_combine(static::KEYS, [
      t('BLAKE2b-128'),
      t('BLAKE2b-160'),
      t('BLAKE2b-224'),
      t('BLAKE2b-256'),
      t('BLAKE2b-384'),
      t('BLAKE2b-512'),
      t('MD5'),
      t('SHA-1'),
      t('SHA-224'),
      t('SHA-256'),
      t('SHA-384'),
      t('SHA-512/224'),
      t('SHA-512/256'),
      t('SHA-512'),
      t('SHA3-224'),
      t('SHA3-256'),
      t('SHA3-384'),
      t('SHA3-512'),
    ]);
  }

  /**
   * Returns array of field descriptions.
   */
  public static function originalDescriptions() {
    return array_combine(static::KEYS, [
      t('The original BLAKE2b-128 hash for this file.'),
      t('The original BLAKE2b-160 hash for this file.'),
      t('The original BLAKE2b-224 hash for this file.'),
      t('The original BLAKE2b-256 hash for this file.'),
      t('The original BLAKE2b-384 hash for this file.'),
      t('The original BLAKE2b-512 hash for this file.'),
      t('The original MD5 hash for this file.'),
      t('The original SHA-1 hash for this file.'),
      t('The original SHA-224 hash for this file.'),
      t('The original SHA-256 hash for this file.'),
      t('The original SHA-384 hash for this file.'),
      t('The original SHA-512/224 hash for this file.'),
      t('The original SHA-512/256 hash for this file.'),
      t('The original SHA-512 hash for this file.'),
      t('The original SHA3-224 hash for this file.'),
      t('The original SHA3-256 hash for this file.'),
      t('The original SHA3-384 hash for this file.'),
      t('The original SHA3-512 hash for this file.'),
    ]);
  }

  /**
   * Returns array of field labels.
   */
  public static function originalLabels() {
    return array_combine(static::KEYS, [
      t('Original BLAKE2b-128 hash'),
      t('Original BLAKE2b-160 hash'),
      t('Original BLAKE2b-224 hash'),
      t('Original BLAKE2b-256 hash'),
      t('Original BLAKE2b-384 hash'),
      t('Original BLAKE2b-512 hash'),
      t('Original MD5 hash'),
      t('Original SHA-1 hash'),
      t('Original SHA-224 hash'),
      t('Original SHA-256 hash'),
      t('Original SHA-384 hash'),
      t('Original SHA-512/224 hash'),
      t('Original SHA-512/256 hash'),
      t('Original SHA-512 hash'),
      t('Original SHA3-224 hash'),
      t('Original SHA3-256 hash'),
      t('Original SHA3-384 hash'),
      t('Original SHA3-512 hash'),
    ]);
  }

}
