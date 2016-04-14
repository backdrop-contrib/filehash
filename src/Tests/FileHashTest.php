<?php

/**
 * @file
 * Definition of Drupal\filehash\Tests\FileHashTest.
 */

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
  public static $modules = ['filehash', 'node', 'file', 'file_module_test', 'field_ui'];

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp(){
    parent::setUp();
    $this->web_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->web_user);
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['algos[sha1]' => TRUE];
    $this->drupalPostForm(NULL, $fields, t('Save configuration'));
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
    $this->drupalLogin($this->adminUser);
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
    $this->drupalPostForm(NULL, $fields, t('Save'));
  }
}
