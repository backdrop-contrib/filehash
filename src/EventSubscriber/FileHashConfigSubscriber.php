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
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The File Hash service.
   *
   * @var \Drupal\filehash\FileHashInterface
   */
  protected $fileHash;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the filehashConfigSubscriber.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\filehash\FileHashInterface $filehash
   *   The File Hash service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator, FileHashInterface $filehash, ModuleHandlerInterface $module_handler) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->fileHash = $filehash;
    $this->moduleHandler = $module_handler;
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
