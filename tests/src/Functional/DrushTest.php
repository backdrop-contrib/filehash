<?php

namespace Drupal\Tests\filehash\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * @coversDefaultClass \Drupal\filehash\Commands\FileHashCommands
 *
 * @group filehash
 */
class DrushTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filehash'];

  /**
   * Tests drush commands.
   */
  public function testCommands(): void {
    $this->drush('filehash:generate');
  }

}
