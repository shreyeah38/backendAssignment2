<?php

namespace Drupal\mymodule\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;

/**
 * A Queue Worker that processes CSV rows.
 *
 * @QueueWorker(
 *   id = "csv_row_processor",
 *   title = @Translation("CSV Row Processor"),
 *   cron = {"time" = 60}
 * )
 */
class ProcessCsvRow extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $name = $data[0];
    $age = $data[1];
    $email = $data[2];
    $gender = $data[3];

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $existing_nodes = $node_storage->loadByProperties(['field_name' => $name]);

    if (empty($existing_nodes)) {
      $node = $node_storage->create([
        'type' => 'users',
        'title' => $name,
        'field_name' => $name,
        'field_age' => $age,
        'field_email' => $email,
        'field_gender' => $gender,
      ]);
      $node->save();
    } else {
      \Drupal::logger('mymodule')->info('Node already exists for name: @name', ['@name' => $name]);
    }
  }
}
