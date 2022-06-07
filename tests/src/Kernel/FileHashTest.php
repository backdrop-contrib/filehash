<?php

namespace Drupal\Tests\filehash\Kernel;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\filehash\Functional\FileHashTestInterface;
use Drupal\user\Entity\User;

/**
 * Using kernel tests rather than functional for speediness.
 *
 * @group filehash
 */
class FileHashTest extends KernelTestBase implements FileHashTestInterface {

  /**
   * {@inheritdoc}
   *
   * @var string[]
   */
  protected static $modules = ['file', 'system', 'field', 'user', 'filehash'];

  /**
   * {@inheritdoc}
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
   * Tests that a file hash is set on the file object.
   */
  public function testFileHash(): void {
    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
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
    $this->assertSame(static::SHA1, $file->sha1->value, 'File hash was set correctly at create.');
    $file->save();
    $this->assertSame(static::SHA1, $file->sha1->value, 'File hash was set correctly at save.');
    $file = File::load($file->id());
    $this->assertSame(static::SHA1, $file->sha1->value, 'File hash was set correctly at load.');
    $file->delete();
  }

  /**
   * Tests entity query and always rehash setting.
   */
  public function testEntityQuery(): void {
    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
    $file = File::create([
      'uri' => $uri,
      'uid' => 1,
    ]);
    $file->save();
    $this->assertGreaterThan(0, $file->id());
    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('1', $count);

    // Modify contents and save, with rehash disabled.
    file_put_contents($uri, static::DIFFERENT_CONTENTS);
    $file->save();

    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('1', $count);
    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::DIFFERENT_SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('0', $count);

    // Enable rehash and save file again.
    \Drupal::configFactory()
      ->getEditable('filehash.settings')
      ->set('rehash', TRUE)
      ->save();
    $file->save();

    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('0', $count);

    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::DIFFERENT_SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('1', $count);

    unlink($uri);
  }

  /**
   * Tests the save original hash setting.
   */
  public function testOriginalSetting(): void {
    \Drupal::configFactory()
      ->getEditable('filehash.settings')
      ->set('rehash', TRUE)
      ->set('original', TRUE)
      ->save();
    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
    $file = File::create([
      'uri' => $uri,
      'uid' => 1,
    ]);
    $file->save();

    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::SHA1)
      ->condition('original_sha1', static::SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('1', $count);

    file_put_contents($uri, static::DIFFERENT_CONTENTS);
    $file->save();

    $count = \Drupal::entityQuery('file')
      ->condition('sha1', static::DIFFERENT_SHA1)
      ->condition('original_sha1', static::SHA1)
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertSame('1', $count);

    unlink($uri);
  }

  /**
   * Tests the MIME types setting.
   */
  public function testMimeTypesSetting(): void {
    \Drupal::configFactory()
      ->getEditable('filehash.settings')
      ->set('mime_types', ['application/octet-stream'])
      ->save();

    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
    $file = File::create([
      'uri' => $uri,
      'uid' => 1,
    ]);
    $file->save();
    $this->assertNull($file->sha1->value);
    $file->delete();

    $uri = 'temporary://' . $this->randomMachineName() . '.txt';
    file_put_contents($uri, static::CONTENTS);
    $file = File::create([
      'uri' => $uri,
      'uid' => 1,
      'filemime' => 'application/octet-stream',
    ]);
    $file->save();
    $this->assertSame(static::SHA1, $file->sha1->value);
    $file->delete();
  }

  /**
   * Tests that a warning is logged if nonexistent file is hashed.
   */
  public function testNonexistentFile(): void {
    $this->expectWarning();
    // "Failed" on PHP 8 or "failed" on PHP 7.
    $this->expectWarningMessage('ailed to open stream');
    File::create(['uri' => "temporary://{$this->randomMachineName()}.txt"]);
  }

}
