<?php

namespace Drupal\filehash\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\filehash\FileHashInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the config save event for filehash.settings.
 */
class FileHashConfigSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the filehashConfigSubscriber.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected FileHashInterface $fileHash,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Updates File Hash schema when needed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The ConfigCrudEvent to process.
   */
  public function onSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() !== 'filehash.settings' || (!$event->isChanged('algos') && !$event->isChanged('original'))) {
      return;
    }
    $this->fileHash->addColumns();
    // Invalidate the views data cache if configured algorithms were modified.
    if ($this->moduleHandler->moduleExists('views')) {
      $this->cacheTagsInvalidator->invalidateTags(['views_data']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
