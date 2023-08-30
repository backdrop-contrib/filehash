<?php

namespace Drupal\filehash_test;

use Drupal\file\Validation\FileValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds "1" to the end of every managed file.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Handles the file validation event.
   */
  public function onFileValidation(FileValidationEvent $event): void {
    file_put_contents($event->file->getFileUri(), '1', FILE_APPEND);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [FileValidationEvent::class => 'onFileValidation'];
  }

}
