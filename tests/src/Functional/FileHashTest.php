<?php

namespace Drupal\Tests\filehash\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\Tests\file\Functional\FileFieldTestBase;

/**
 * File Hash tests.
 *
 * @group File Hash
 */
class FileHashTest extends FileFieldTestBase {

  use StringTranslationTrait;

  const BLAKE2B_512 = 'ba80a53f981c4d0d6a2797b69f12f6e94c212f14685ac4b74b12bb6fdbffa2d17d87c5392aab792dc252d5de4533cc9518d38aa8dbf1925ab92386edd4009923';
  const SHA1 = '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed';
  const URI = 'public://druplicon.txt';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'filehash',
    'node',
    'file',
    'file_module_test',
    'field_ui',
  ];

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => TRUE];
    $this->submitForm($fields, $this->t('Save configuration'));
  }

  /**
   * Tests that a file hash is set on the file object.
   */
  public function testFileHash() {
    file_put_contents(static::URI, 'hello world');
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => static::URI,
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $this->assertSame($file->filehash['sha1'], static::SHA1, 'File hash was set correctly at create.');
    $file->save();
    $this->assertSame($file->filehash['sha1'], static::SHA1, 'File hash was set correctly at save.');
    $file = File::load($file->id());
    $this->assertSame($file->filehash['sha1'], static::SHA1, 'File hash was set correctly at load.');
  }

  /**
   * Tests BLAKE2b hash algorithm.
   */
  public function testBlake2b() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[blake2b_512]' => TRUE];
    $this->submitForm($fields, $this->t('Save configuration'));
    file_put_contents(static::URI, 'abc');
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => static::URI,
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $hash = function_exists('sodium_crypto_generichash_init') ? static::BLAKE2B_512 : NULL;
    $this->assertSame($file->filehash['blake2b_512'], $hash, 'File hash was set correctly at create.');
    $file->save();
    $this->assertSame($file->filehash['blake2b_512'], $hash, 'File hash was set correctly at save.');
    $file = File::load($file->id());
    $this->assertSame($file->filehash['blake2b_512'], $hash, 'File hash was set correctly at load.');
  }

  /**
   * Tests the table with file hashes field formatter.
   */
  public function testFileHashField() {
    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $field_storage_settings = [
      'display_field' => '1',
      'display_default' => '1',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    $field_settings = ['description_field' => '1'];
    $widget_settings = [];
    $this->createFileField($field_name, 'node', $type_name, $field_storage_settings, $field_settings, $widget_settings);
    $this->drupalGet("admin/structure/types/manage/$type_name/display");
    $fields = ["fields[$field_name][type]" => 'filehash_table'];
    $this->submitForm($fields, $this->t('Save'));
  }

  /**
   * Tests a file field with dedupe enabled.
   */
  public function testFileHashFieldDuplicate() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => TRUE];
    $this->submitForm($fields, $this->t('Save configuration'));

    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);
    $test_file = $this->getTestFile('text');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid/edit");
    $this->assertSession()->responseContains($this->t('The specified file %name could not be uploaded.', ['%name' => $test_file->getFilename()]));
    $this->assertSession()->pageTextContains($this->t('Sorry, duplicate files are not permitted.'));

    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => FALSE];
    $this->submitForm($fields, $this->t('Save configuration'));

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => TRUE];
    $this->submitForm($fields, $this->t('Save configuration'));

    // Test that a node with duplicate file already attached can be saved.
    $this->drupalGet("node/$nid/edit");
    $this->submitForm([], $this->t('Save'));
    $this->assertSession()->addressEquals("node/$nid");
  }

  /**
   * Tests file hash bulk generation.
   */
  public function testFileHashGenerate() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => FALSE];
    $this->submitForm($fields, $this->t('Save configuration'));

    do {
      $file = $this->getTestFile('text');
      $file->save();
    } while ($file->id() < 5);

    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => TRUE];
    $this->submitForm($fields, $this->t('Save configuration'));

    $this->drupalGet('admin/config/media/filehash/generate');
    $this->submitForm([], $this->t('Generate'));
    $this->assertSession()->pageTextContains('Processed 5 files.');
  }

  /**
   * Tests database cleanup.
   */
  public function testFileHashClean() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha512_256]' => TRUE];
    $this->submitForm($fields, $this->t('Save configuration'));
    $fields = ['algos[sha1]' => FALSE, 'algos[sha512_256]' => FALSE];
    $this->submitForm($fields, $this->t('Save configuration'));
    $this->drupalGet('admin/config/media/filehash/clean');
    $this->submitForm([], $this->t('Delete'));
    $this->assertSession()->pageTextContains('Processed 2 hash algorithm columns.');
  }

}
