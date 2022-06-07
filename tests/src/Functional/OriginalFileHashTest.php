<?php

namespace Drupal\Tests\filehash\Functional;

use Drupal\Tests\file\Functional\FileFieldTestBase;

/**
 * File Hash tests.
 *
 * @group filehash
 */
class OriginalFileHashTest extends FileFieldTestBase implements FileHashTestInterface {

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
    'filehash_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/media/filehash');
    $fields = [
      'algos[sha1]' => TRUE,
      'dedupe' => 1,
      'rehash' => TRUE,
      'original' => TRUE,
      'dedupe_original' => TRUE,
    ];
    $this->submitForm($fields, 'Save configuration');
  }

  /**
   * Tests a file field with original hash and original hash dedupe enabled.
   *
   * The filehash_test module will modify uploaded files when validating.
   */
  public function testFileHashFieldDuplicateOriginal(): void {
    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';
    $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);
    $test_file = $this->getTestFile('text');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    // Try to upload original file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid/edit");
    $this->assertSession()->pageTextContains(strtr('The specified file %name could not be uploaded.', ['%name' => $test_file->getFilename()]));
    $this->assertSession()->pageTextContains('Sorry, duplicate files are not permitted.');

    // Turn off original dedupe.
    $this->drupalGet('admin/config/media/filehash');
    $fields = ['dedupe_original' => 0];
    $this->submitForm($fields, 'Save configuration');

    // Try again to upload original file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid");

    // Test field-level dedupe enabled.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.$field_name");
    $fields = [
      'third_party_settings[filehash][dedupe]' => TRUE,
      'third_party_settings[filehash][dedupe_original]' => TRUE,
    ];
    $this->submitForm($fields, 'Save settings');

    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->addressEquals("node/$nid/edit");
    $this->assertSession()->pageTextContains(strtr('The specified file %name could not be uploaded.', ['%name' => $test_file->getFilename()]));
    $this->assertSession()->pageTextContains('Sorry, duplicate files are not permitted.');
  }

}
