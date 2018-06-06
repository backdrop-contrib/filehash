<?php

namespace Drupal\filehash\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\file\Tests\FileFieldTestBase;

/**
 * File hash tests.
 *
 * @group File hash
 */
class FileHashTest extends FileFieldTestBase {
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
    $this->drupalPostForm('admin/config/media/filehash', $fields, t('Save configuration'));
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
    $this->assertEqual($file->filehash['sha1'], '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed', 'File hash was set correctly.');
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
    $this->drupalPostForm("admin/structure/types/manage/$type_name/display", $fields, t('Save'));
  }

  /**
   * Tests a file field with dedupe enabled.
   */
  public function testFileHashFieldDuplicate() {
    $fields = ['dedupe' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, t('Save configuration'));

    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);
    $test_file = $this->getTestFile('text');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertUrl("node/$nid");

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertUrl("node/$nid/edit");
    $this->assertRaw(t('The specified file %name could not be uploaded.', ['%name' => $test_file->getFilename()]));
    $this->assertText(t('Sorry, duplicate files are not permitted.'));

    $fields = ['dedupe' => FALSE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, t('Save configuration'));

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertUrl("node/$nid");

    $fields = ['dedupe' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, t('Save configuration'));

    // Test that a node with duplicate file already attached can be saved.
    $this->drupalGet("node/$nid/edit");
    $form = $this->xpath("//form[@id='node-$type_name-edit-form']")[0];
    $post = $edit = $upload = [];
    $submit = t('Save');
    // Compatibility with Drupal 8.3 save button.
    if (!$this->handleForm($post, $edit, $upload, $submit, $form)) {
      $submit = t('Save and keep published');
    }
    $this->drupalPostForm(NULL, $edit, $submit);
    $this->assertUrl("node/$nid");
  }

  /**
   * Tests file hash bulk generation.
   */
  public function testFileHashGenerate() {
    $fields = ['algos[sha1]' => FALSE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, t('Save configuration'));

    do {
      $file = $this->getTestFile('text');
      $file->save();
    } while ($file->id() < 5);

    $fields = ['algos[sha1]' => TRUE];
    $this->drupalPostForm('admin/config/media/filehash', $fields, t('Save configuration'));

    $this->drupalPostForm('admin/config/media/filehash/generate', [], t('Generate'));
    $this->assertText('Processed 5 files.');
  }

}
