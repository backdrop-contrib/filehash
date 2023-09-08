<?php

namespace Drupal\filehash;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;

/**
 * Provides the File Hash service.
 */
class FileHash implements FileHashInterface {

  use StringTranslationTrait;

  const CHUNK_SIZE = 8192;

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
   * Constructs the File Hash service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
    protected MemoryCacheInterface $memoryCache,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function addColumns(): void {
    $original = $this->configFactory->get('filehash.settings')->get('original');
    $fields = $this->entityBaseFieldInfo();
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
   *
   * @return string[]
   *   Enabled hash algorithm identifiers.
   */
  public function algos(): array {
    return str_replace(['sha3_', 'sha512_'], ['sha3-', 'sha512/'], $this->columns());
  }

  /**
   * {@inheritdoc}
   */
  public function columns(): array {
    return array_intersect_assoc($this->configFactory->get('filehash.settings')->get('algos') ?? [], static::keys());
  }

  /**
   * {@inheritdoc}
   */
  public function entityBaseFieldInfo(): array {
    $columns = $this->columns();
    $names = static::names();
    $lengths = static::lengths();
    $original = $this->configFactory->get('filehash.settings')->get('original');
    $fields = [];
    foreach ($columns as $column) {
      $fields[$column] = BaseFieldDefinition::create('filehash')
        ->setLabel($this->t('@algo hash', ['@algo' => $names[$column]]))
        ->setSetting('max_length', $lengths[$column])
        ->setDescription($this->t('The @algo hash for this file.', ['@algo' => $names[$column]]));
      if ($original) {
        $fields["original_$column"] = BaseFieldDefinition::create('filehash')
          ->setLabel($this->t('Original @algo hash', ['@algo' => $names[$column]]))
          ->setSetting('max_length', $lengths[$column])
          ->setDescription($this->t('The original @algo hash for this file.', ['@algo' => $names[$column]]));
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityStorageLoad(array $files): void {
    if (!$this->configFactory->get('filehash.settings')->get('autohash')) {
      return;
    }
    foreach ($files as $file) {
      foreach ($this->columns() as $column) {
        if (!$file->{$column}->value && $this->shouldHash($file) && !$this->memoryCache->get($file->id())) {
          // To avoid endless loops, auto-hash each file once per execution.
          $this->memoryCache->set($file->id(), TRUE);
          $file->save();
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function filePresave(FileInterface $file): void {
    if ($this->configFactory->get('filehash.settings')->get('rehash')) {
      // Regenerate all hashes.
      $this->hash($file);
    }
    else {
      // Only generate missing hashes.
      foreach ($this->columns() as $column) {
        if (empty($file->{$column}->value)) {
          $columns[] = $column;
        }
      }
      if (isset($columns)) {
        $this->hash($file, $columns);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hash(FileInterface $file, ?array $columns = NULL, bool $original = FALSE): void {
    // If columns are set, only generate those hashes.
    $algos = isset($columns) ? array_intersect_key($this->algos(), array_flip($columns)) : $this->algos();
    if (!$algos) {
      return;
    }
    $setFileHashes = function (array $states = []) use ($file, $algos, $original) : void {
      foreach ($algos as $column => $algo) {
        // Unreadable files will have NULL hash values.
        $hash = $states[$column] ?? NULL;
        $file->set($column, $hash);
        if ($original) {
          $file->set("original_$column", $hash);
        }
      }
    };
    if (!$this->shouldHash($file)) {
      $setFileHashes();
      return;
    }
    $suppressWarnings = $this->configFactory->get('filehash.settings')->get('suppress_warnings');
    // Use hash_file() if possible as it provides an optimized code path.
    if (count($algos) === 1 && !str_starts_with($algo = reset($algos), 'blake2b_')) {
      $column = key($algos);
      $states[$column] = ($suppressWarnings ? @hash_file($algo, $file->getFileUri()) : hash_file($algo, $file->getFileUri())) ?: NULL;
      $setFileHashes($states);
      return;
    }
    $handle = $suppressWarnings ? @fopen($file->getFileUri(), 'rb') : fopen($file->getFileUri(), 'rb');
    if (FALSE === $handle) {
      $setFileHashes();
      return;
    }
    foreach ($algos as $column => $algo) {
      if (preg_match('/^blake2b_([0-9]{3})$/', $algo, $matches)) {
        if (function_exists('sodium_crypto_generichash_init')) {
          $lengths[$column] = (int) $matches[1] / 8;
          $states[$column] = sodium_crypto_generichash_init('', $lengths[$column]);
        }
      }
      else {
        $states[$column] = hash_init($algo);
      }
    }
    if (empty($states)) {
      $setFileHashes();
      return;
    }
    while ('' !== ($data = fread($handle, static::CHUNK_SIZE))) {
      if (FALSE === $data) {
        $setFileHashes();
        return;
      }
      foreach ($states as $column => &$state) {
        isset($lengths[$column]) ? sodium_crypto_generichash_update($state, $data) : hash_update($state, $data);
      }
    }
    if (!feof($handle)) {
      $setFileHashes();
      return;
    }
    fclose($handle);
    foreach ($states as $column => &$state) {
      $state = isset($lengths[$column]) ? bin2hex(sodium_crypto_generichash_final($state, $lengths[$column])) : hash_final($state);
    }
    $setFileHashes($states);
  }

  /**
   * {@inheritdoc}
   */
  public function shouldHash(FileInterface $file): bool {
    // Nothing to do if file URI is empty.
    if (!$file->getFileUri()) {
      return FALSE;
    }
    $types = $this->configFactory->get('filehash.settings')->get('mime_types');
    if ($types && !in_array($file->getMimeType(), $types)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function keys(): array {
    return array_combine(static::KEYS, static::KEYS);
  }

  /**
   * {@inheritdoc}
   */
  public static function lengths(): array {
    return array_combine(static::KEYS, static::LENGTHS);
  }

  /**
   * {@inheritdoc}
   */
  public static function names(): array {
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

}
