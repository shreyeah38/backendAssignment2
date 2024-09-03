<?php

namespace Drupal\mymodule\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to process queued CSV items via POST requests.
 *
 * @RestResource(
 *   id = "csv_queue_processor_resource",
 *   label = @Translation("CSV Queue Processor Resource"),
 *   uri_paths = {
 *     "create" = "/api/csv/queue-process"
 *   }
 * )
 */
class CsvQueueProcessorResource extends ResourceBase {

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The queue worker manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManager
   */
  protected $queueWorkerManager;

  /**
   * Constructs a new CsvQueueProcessorResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   * @param \Drupal\Core\Queue\QueueWorkerManager $queueWorkerManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, QueueFactory $queueFactory, QueueWorkerManager $queueWorkerManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->queueFactory = $queueFactory;
    $this->queueWorkerManager = $queueWorkerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('queue_processing_resource'),
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * Responds to POST requests to process queued items.
   *
   * @param array $data
   *   The request data containing queued item IDs.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response object.
   */
  public function post(array $data) {
    $queued_item_ids = $data['queued_item_ids'] ?? [];

    if (empty($queued_item_ids) || !is_array($queued_item_ids)) {
      return new ResourceResponse(['error' => 'queued_item_ids array is required.'], 400);
    }

    $queue = $this->queueFactory->get('csv_row_processor');
    $queue_worker = $this->queueWorkerManager->createInstance('csv_row_processor');
    $results = [];

    foreach ($queued_item_ids as $item_id) {
      $item = $queue->claimItem($item_id);

      if ($item) {
        // Process the item using the queue worker.
        $queue_worker->processItem($item->data);
        $results[$item_id] = 'Processed successfully';

        // After processing, delete the item.
        $queue->deleteItem($item);
      } else {
        $results[$item_id] = 'Item not found or already processed';
      }
    }

    return new ResourceResponse([
      'status' => 'Queue items processed.',
      'results' => $results,
    ], 200);
  }
}
