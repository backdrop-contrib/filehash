<?php

namespace Drupal\filehash\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator, Connection $connection, ModuleHandlerInterface $module_handler) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Updates File Hash schema when needed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The ConfigCrudEvent to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() !== 'filehash.settings' || !$event->isChanged('algos')) {
      return;
    }
    $schema = $this->connection->schema();
    foreach (filehash_algos() as $column => $algo) {
      $spec['fields'] = [
        $column => [
          'description' => "The $algo hash for this file.",
          'type' => 'varchar_ascii',
          'length' => strlen(hash($algo, '')),
          'not null' => FALSE,
        ],
      ];
      if (!$schema->fieldExists('filehash', $column)) {
        $schema->addField('filehash', $column, $spec['fields'][$column]);
      }
      if (!$schema->indexExists('filehash', "{$column}_idx")) {
        $schema->addIndex('filehash', "{$column}_idx", [$column], $spec);
      }
    }
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
