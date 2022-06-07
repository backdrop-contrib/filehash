<?php

namespace Drupal\Tests\filehash\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\file\Functional\FileFieldTestBase;

/**
 * File Hash tests.
 *
 * @group filehash
 */
class FileHashTest extends FileFieldTestBase implements FileHashTestInterface {

  const BLAKE2B_512 = 'ba80a53f981c4d0d6a2797b69f12f6e94c212f14685ac4b74b12bb6fdbffa2d17d87c5392aab792dc252d5de4533cc9518d38aa8dbf1925ab92386edd4009923';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filehash',
    'node',
    'file',
    'file_module_test',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => TRUE];
    $this->submitForm($fields, 'Save configuration');
  }

  /**
   * Tests BLAKE2b hash algorithm.
   */
  public function testBlake2b(): void {
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
      // @phpstan-ignore-next-line Core 9.2 compatibility.
      'status' => defined(FileInterface::class . '::STATUS_PERMANENT') ? FileInterface::STATUS_PERMANENT : FILE_STATUS_PERMANENT,
    ]);
    $hash = function_exists('sodium_crypto_generichash_init') ? static::BLAKE2B_512 : NULL;
    $this->assertSame($hash, $file->blake2b_512->value, 'File hash was set correctly at create.');
    $file->save();
    $this->assertSame($hash, $file->blake2b_512->value, 'File hash was set correctly at save.');
    $file = File::load($file->id());
    $this->assertSame($hash, $file->blake2b_512->value, 'File hash was set correctly at load.');
    $file->delete();
  }

  /**
   * Tests the table with file hashes field formatter.
   */
  public function testFileHashField(): void {
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
  public function testFileHashFieldDuplicate(): void {
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

    // Enable strict dedupe.
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => 2];
    $this->submitForm($fields, 'Save configuration');

    // Test that a node with duplicate file already attached can be saved.
    $this->drupalGet("node/$nid/edit");
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals("node/$nid");

    // Test that duplicate files cannot be uploaded at the same time.
    $this->drupalGet("node/add/$type_name");
    $test_file_2 = $this->getTestFile('binary');
    $edit['files[' . $field_name . '_0]'] = \Drupal::service('file_system')->realpath($test_file_2->getFileUri());
    $this->submitForm($edit, 'Upload');
    $this->submitForm([], 'Preview');
    $nid_2 = $this->uploadNodeFile($test_file_2, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid_2/edit");

    // Enable normal dedupe.
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => 1];
    $this->submitForm($fields, 'Save configuration');

    // Test that duplicate files can be uploaded at the same time.
    $nid_2 = $this->uploadNodeFile($test_file_2, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid_2");

    // Disable global dedupe setting.
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe' => 0];
    $this->submitForm($fields, 'Save configuration');

    // Test field-level dedupe enabled.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.$field_name");
    $fields = ['third_party_settings[filehash][dedupe]' => 2];
    $this->submitForm($fields, 'Save settings');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid/edit");
    $this->assertSession()->pageTextContains(strtr('The specified file %name could not be uploaded.', ['%name' => $test_file->getFilename()]));
    $this->assertSession()->pageTextContains('Sorry, duplicate files are not permitted.');

    // Test field-level dedupe disabled.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.$field_name");
    $fields = ['third_party_settings[filehash][dedupe]' => 0];
    $this->submitForm($fields, 'Save settings');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");
  }

  /**
   * Tests file hash bulk generation.
   */
  public function testFileHashGenerate(): void {
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
  public function testFileHashClean(): void {
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
