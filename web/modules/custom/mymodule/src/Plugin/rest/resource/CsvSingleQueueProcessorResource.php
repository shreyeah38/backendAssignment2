<?php

namespace Drupal\mymodule\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

use Drupal\Core\Queue\QueueWorkerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to process a single queued CSV item via POST requests.
 *
 * @RestResource(
 *   id = "csv_single_queue_processor_resource",
 *   label = @Translation("CSV Single Queue Processor Resource"),
 *   uri_paths = {
 *     "create" = "/api/csv/single-queue-process"
 *   }
 * )
*/
class CsvSingleQueueProcessorResource extends ResourceBase {

  /**
   * The queue worker manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManager
   */
  protected $queueWorkerManager;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CsvSingleQueueProcessorResource object.
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
   * @param \Drupal\Core\Queue\QueueWorkerManager $queueWorkerManager
   *   The queue worker manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, QueueWorkerManager $queueWorkerManager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->queueWorkerManager = $queueWorkerManager;
    $this->database = $database;
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
      $container->get('plugin.manager.queue_worker'),
      $container->get('database')
    );
  }

  /**
   * Processes a single queued item.
   *
   * @param array $data
   *   The request data containing the file ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response object.
   */
  public function post($data) {
    // dd($data);
    $item_id = $data['item_id'];

    if(!($item_id)) {
      return new ResourceResponse(['error' => 'file_id is required.'], 400);
    }
    $processed_items = [];
    $connection = Database::getConnection();

    // Query the queue table to find the specific item.
    $query = $this->database->select('queue', 'q')
      ->fields('q', ['data'])
      ->condition('item_id', $item_id)
      ->condition('name', 'csv_row_processor');
      $result = $query->execute()->fetchAssoc();

    if ($result) {
      $item_data = unserialize($result['data']);

      // Process the item using the queue worker.
      $queue_worker = $this->queueWorkerManager->createInstance('csv_row_processor');
      $queue_worker->processItem($item_data);
      $processed_items[] = $item_data;
      // Delete the item after processing.
      $connection->delete('queue')
      ->condition('item_id', $item_id)
      ->condition('name', 'csv_row_processor')
      ->execute();

      return new ResourceResponse([
        'message' => 'Queue processing completed.',
        'processed_items' => $processed_items,
      ], 200);
    } else {
      return new ResourceResponse([
        'status' => 'Queue item not found or already processed.',
        'results' => [
          $item_id => 'Item not found or already processed',
        ],
      ], 404);
    }
  }
}
