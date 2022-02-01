<?php

namespace Drupal\Tests\filehash\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\file\Functional\FileFieldTestBase;

/**
 * File Hash tests.
 *
 * @group File Hash
 */
class FileHashTest extends FileFieldTestBase implements FileHashTestInterface {

  const BLAKE2B_512 = 'ba80a53f981c4d0d6a2797b69f12f6e94c212f14685ac4b74b12bb6fdbffa2d17d87c5392aab792dc252d5de4533cc9518d38aa8dbf1925ab92386edd4009923';

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
    $this->submitForm($fields, 'Save configuration');
  }

  /**
   * Tests that a file hash is set on the file object.
   */
  public function testFileHash() {
    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => $uri,
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $this->assertSame(static::SHA1, $file->sha1->value, 'File hash was set correctly at create.');
    $file->save();
    $this->assertSame(static::SHA1, $file->sha1->value, 'File hash was set correctly at save.');
    $file = File::load($file->id());
    $this->assertSame(static::SHA1, $file->sha1->value, 'File hash was set correctly at load.');
    unlink($uri);
  }

  /**
   * Tests BLAKE2b hash algorithm.
   */
  public function testBlake2b() {
    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[blake2b_512]' => TRUE];
    $this->submitForm($fields, 'Save configuration');
    file_put_contents($uri, 'abc');
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => $uri,
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $hash = function_exists('sodium_crypto_generichash_init') ? static::BLAKE2B_512 : NULL;
    $this->assertSame($hash, $file->blake2b_512->value, 'File hash was set correctly at create.');
    $file->save();
    $this->assertSame($hash, $file->blake2b_512->value, 'File hash was set correctly at save.');
    $file = File::load($file->id());
    $this->assertSame($hash, $file->blake2b_512->value, 'File hash was set correctly at load.');
    unlink($uri);
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
    $this->submitForm($fields, 'Save');
  }

  /**
   * Tests a file field with dedupe enabled.
   */
  public function testFileHashFieldDuplicate() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => 1];
    $this->submitForm($fields, 'Save configuration');

    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);
    $test_file = $this->getTestFile('text');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid/edit");
    $this->assertSession()->pageTextContains(strtr('The specified file %name could not be uploaded.', ['%name' => $test_file->getFilename()]));
    $this->assertSession()->pageTextContains('Sorry, duplicate files are not permitted.');

    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => 0];
    $this->submitForm($fields, 'Save configuration');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => 1];
    $this->submitForm($fields, 'Save configuration');

    // Test that a node with duplicate file already attached can be saved.
    $this->drupalGet("node/$nid/edit");
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals("node/$nid");
  }

  /**
   * Tests file hash bulk generation.
   */
  public function testFileHashGenerate() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => FALSE];
    $this->submitForm($fields, 'Save configuration');

    do {
      $file = $this->getTestFile('text');
      $file->save();
    } while ($file->id() < 5);

    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => TRUE];
    $this->submitForm($fields, 'Save configuration');

    $this->drupalGet('admin/config/media/filehash/generate');
    $this->submitForm([], 'Generate');
    $this->assertSession()->pageTextContains('Processed 5 files.');
  }

  /**
   * Tests database cleanup.
   */
  public function testFileHashClean() {
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha512_256]' => TRUE];
    $this->submitForm($fields, 'Save configuration');
    $fields = ['algos[sha1]' => FALSE, 'algos[sha512_256]' => FALSE];
    $this->submitForm($fields, 'Save configuration');
    $this->drupalGet('admin/config/media/filehash/clean');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Processed 2 hash algorithm columns.');
  }

}
