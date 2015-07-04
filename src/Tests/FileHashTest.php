<?php

/**
 * @file
 * Definition of Drupal\filehash\Tests\FileHashTest.
 */

namespace Drupal\filehash\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * File hash tests.
 *
 * @group File hash
 */
class FileHashTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filehash');

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp(){
    parent::setUp();
    $this->web_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($this->web_user);
    $this->drupalGet('admin/config/media/filehash');
    $fields = array(
      'algos[sha1]' => TRUE,
    );
    $this->drupalPostForm(NULL, $fields, t('Save configuration'));
  }

  /**
   * Tests that a file hash is set on the file object.
   */
  public function testFileHash() {
    $file = File::create(array(
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();
    $this->assertEqual($file->filehash['sha1'], '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed', 'File hash was set correctly.');
  }
}

