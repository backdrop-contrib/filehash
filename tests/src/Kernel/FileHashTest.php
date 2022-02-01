<?php

namespace Drupal\Tests\filehash\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\Tests\filehash\Functional\FileHashTestInterface;

/**
 * Using kernel tests rather than functional for speediness.
 *
 * @group filehash
 */
class FileHashTest extends KernelTestBase implements FileHashTestInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'system', 'field', 'user', 'filehash'];

  /**
   * Setup.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['filehash']);
    $user = User::create(['uid' => 1, 'name' => $this->randomMachineName()]);
    $user->enforceIsNew();
    $user->save();
    \Drupal::currentUser()->setAccount($user);
    \Drupal::configFactory()
      ->getEditable('filehash.settings')
      ->set('algos.sha1', 'sha1')
      ->save();
  }

  /**
   * Tests entity query.
   */
  public function testEntityQuery() {
    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
    $file = File::create([
      'uri' => $uri,
      'uid' => 1,
    ]);
    $file->save();
    $this->assertGreaterThan(0, $file->id());
    $count = \Drupal::entityQuery('file')
      ->condition('fid', '1')
      ->condition('sha1', static::SHA1)
      ->accessCheck(TRUE)
      ->count()
      ->execute();
    $this->assertSame('1', $count);
    unlink($uri);
  }

}
