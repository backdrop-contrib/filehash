<?php

namespace Drupal\filehash;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Provides the File Hash service.
 */
class FileHash implements FileHashInterface {

  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user making the request.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityDefinitionUpdateManagerInterface $entity_definition_update_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Adds missing database columns.
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
   */
  public function algos(): array {
    return str_replace(['sha3_', 'sha512_'], ['sha3-', 'sha512/'], $this->columns());
  }

  /**
   * Returns array of enabled File Hash algorithm identifiers.
   */
  public function columns(): array {
    return $this->intersect($this->configFactory->get('filehash.settings')->get('algos'));
  }

  /**
   * Returns file ID for any duplicates.
   */
  public function duplicateLookup(string $column, FileInterface $file, bool $strict = FALSE, bool $original = FALSE): ?string {
    // @fixme This code results in *multiple* SQL joins on the file_managed
    // table; if slow maybe it should be refactored to use a normal database
    // query? See also https://www.drupal.org/project/drupal/issues/2875033
    $query = $this->entityTypeManager->getStorage('file')->getQuery('AND');
    if ($original && $this->configFactory->get('filehash.settings')->get('original')) {
      $group = $query->orConditionGroup()
        ->condition("original_$column", $file->{$column}->value, '=')
        ->condition($column, $file->{$column}->value, '=');
      $query->condition($group);
    }
    else {
      $query->condition($column, $file->{$column}->value);
    }
    if (!$strict) {
      $query->condition('status', FileInterface::STATUS_PERMANENT, '=');
    }
    $results = $query->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    return reset($results) ?: NULL;
  }

  /**
   * Implements hook_entity_base_field_info().
   */
  public function entityBaseFieldInfo(): array {
    $columns = $this->columns();
    $labels = $this->labels();
    $lengths = $this->lengths();
    $descriptions = $this->descriptions();
    $original = $this->configFactory->get('filehash.settings')->get('original');
    if ($original) {
      $original_labels = $this->originalLabels();
      $original_descriptions = $this->originalDescriptions();
    }
    $fields = [];
    foreach ($columns as $column) {
      $fields[$column] = BaseFieldDefinition::create('filehash')
        ->setLabel($labels[$column])
        ->setSetting('max_length', $lengths[$column])
        ->setDescription($descriptions[$column]);
      if ($original) {
        $fields["original_$column"] = BaseFieldDefinition::create('filehash')
          ->setLabel($original_labels[$column])
          ->setSetting('max_length', $lengths[$column])
          ->setDescription($original_descriptions[$column]);
      }
    }
    return $fields;
  }

  /**
   * Implements hook_entity_storage_load().
   *
   * Generates hash if it does not already exist for the file.
   */
  public function entityStorageLoad($files): void {
    // @todo Add a setting to toggle the auto-hash behavior?
    foreach ($files as $file) {
      foreach ($this->columns() as $column) {
        if (!$file->{$column}->value) {
          $file->original = clone($file);
          // Entity post-save will clean up the dangling "original" property.
          $file->save();
          break;
        }
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
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
          $this->hash($file, $column);
        }
      }
    }
  }

  /**
   * Calculates the file hashes.
   */
  public function hash($file, ?string $column = NULL, bool $original = FALSE): void {
    $uri = $file->getFileUri();
    // If column is set, only generate that hash.
    $algos = $column ? [$column => $this->algos()[$column]] : $this->algos();
    foreach ($algos as $column => $algo) {
      // Nothing to do if file URI is empty.
      if (NULL === $uri || '' === $uri) {
        $hash = NULL;
      }
      // Unreadable files will have NULL hash values.
      elseif (preg_match('/^blake2b_([0-9]{3})$/', $algo, $matches)) {
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
   * Checks that file is not a duplicate.
   */
  public function validateDedupe(FileInterface $file, bool $strict = FALSE, bool $original = FALSE): array {
    $errors = [];
    foreach ($this->columns() as $column) {
      try {
        $fid = $this->duplicateLookup($column, $file, $strict, $original);
      }
      catch (DatabaseExceptionWrapper $e) {
        $this->addColumns();
        $fid = $this->duplicateLookup($column, $file, $strict, $original);
      }
      if ($fid) {
        $error = $this->t('Sorry, duplicate files are not permitted.');
        if ($this->currentUser->hasPermission('access files overview')) {
          try {
            $url = Url::fromRoute('view.files.page_2', ['arg_0' => $fid], ['attributes' => ['target' => '_blank']]);
            $error = $this->t('This file has already been uploaded as %filename.', [
              '%filename' => Link::fromTextAndUrl(File::load($fid)->label(), $url)->toString(),
            ]);
          }
          catch (\Exception $e) {
            // Maybe the view was disabled?
          }
        }
        $errors[] = $error;
        break;
      }
    }
    return $errors;
  }

  /**
   * Implements hash_file() for the BLAKE2b hash algorithm.
   *
   * Requires the Sodium PHP extension.
   *
   * @return string|false
   *   Same return type as hash_file().
   */
  public static function blake2b(string $uri, int $length, int $chunk_size = 8192) {
    if (!function_exists('sodium_crypto_generichash_init')) {
      return FALSE;
    }
    $handle = fopen($uri, 'rb');
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
  public static function descriptions(): array {
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
  public static function intersect($config): array {
    return array_intersect_assoc($config ?? [], static::keys());
  }

  /**
   * Returns array of valid File Hash algorithm identifiers.
   */
  public static function keys(): array {
    return array_combine(static::KEYS, static::KEYS);
  }

  /**
   * Returns array of field labels.
   */
  public static function labels(): array {
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
  public static function lengths(): array {
    return array_combine(static::KEYS, [
      32, 40, 56, 64, 96, 128, 32, 40, 56, 64, 96, 56, 64, 128, 56, 64, 96, 128,
    ]);
  }

  /**
   * Returns array of human-readable hash algorithm names.
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

  /**
   * Returns array of field descriptions.
   */
  public static function originalDescriptions(): array {
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
  public static function originalLabels(): array {
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
