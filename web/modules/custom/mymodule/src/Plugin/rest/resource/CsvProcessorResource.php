<?php

namespace Drupal\mymodule\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to process CSV files via POST requests.
 *
 * @RestResource(
 *   id = "csv_processor_resource",
 *   label = @Translation("CSV Processor Resource"),
 *   uri_paths = {
 *     "create" = "/api/csv/process"
 *   }
 * )
 */
class CsvProcessorResource extends ResourceBase {

  /**
   * Responds to POST requests to process a CSV file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response object.
   */
  public function post(Request $request) {
    $file_system = \Drupal::service('file_system');
    $queue_factory = \Drupal::service('queue');

    // Get the numeric value from the configuration.
    $config = \Drupal::config('mymodule.settings');
    $numeric_value = $config->get('numeric_value');

    $data = $request->getContent();
    $decoded_data = json_decode($data, TRUE);
    $file_path = $decoded_data['file_path'];

    if (empty($file_path)) {
      return new ResourceResponse(['error' => 'File path is required.'], 400);
    }

    $queue = $queue_factory->get('csv_row_processor');
    $queue->createQueue();

    $queued_item_ids = [];

    if (($handle = fopen($file_system->realpath($file_path), 'r')) !== FALSE) {
      $row_count = 0;
      while (($data = fgetcsv($handle, 100, ",")) !== FALSE) {
        if ($row_count >= $numeric_value) {
          break;
        }
        \Drupal::logger('csv_row_processor')->info('Queueing data: @data', ['@data' => print_r($data, TRUE)]);
        $item_id = $queue->createItem($data);
        $queued_item_ids[] = $item_id;
        $row_count++;
      }
      fclose($handle);
    } else {
      return new ResourceResponse(['error' => 'Could not read file.'], 500);
    }

    return new ResourceResponse([
      'status' => 'File processed successfully.',
      'queueId' => 'csv_row_processor',
    ], 200);
  }
}
