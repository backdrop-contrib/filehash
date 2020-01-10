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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'filehash',
    'node',
    'file',
    'file_module_test',
    'field_ui',
  ];

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $fields = ['algos[sha1]' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, $this->t('Save configuration'));
  }

  /**
   * Tests that a file hash is set on the file object.
   */
  public function testFileHash() {
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();
    $this->assertEquals($file->filehash['sha1'], '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed', 'File hash was set correctly.');
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
    $fields = ["fields[$field_name][type]" => 'filehash_table'];
    $this->drupalPostForm("admin/structure/types/manage/$type_name/display", $fields, $this->t('Save'));
  }

  /**
   * Tests a file field with dedupe enabled.
   */
  public function testFileHashFieldDuplicate() {
    $fields = ['dedupe' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, $this->t('Save configuration'));

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

    $fields = ['dedupe' => FALSE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, $this->t('Save configuration'));

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    $fields = ['dedupe' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, $this->t('Save configuration'));

    // Test that a node with duplicate file already attached can be saved.
    $this->drupalGet("node/$nid/edit");
    $this->drupalPostForm(NULL, [], $this->t('Save'));
    $this->assertSession()->addressEquals("node/$nid");
  }

  /**
   * Tests file hash bulk generation.
   */
  public function testFileHashGenerate() {
    $fields = ['algos[sha1]' => FALSE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, $this->t('Save configuration'));

    do {
      $file = $this->getTestFile('text');
      $file->save();
    } while ($file->id() < 5);

    $fields = ['algos[sha1]' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, $this->t('Save configuration'));

    $this->drupalPostForm('admin/config/media/filehash/generate', [], $this->t('Generate'));
    $this->assertSession()->pageTextContains('Processed 5 files.');
  }

}
