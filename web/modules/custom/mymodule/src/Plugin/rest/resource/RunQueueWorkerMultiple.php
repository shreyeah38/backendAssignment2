<?php
 
namespace Drupal\mymodule\Plugin\rest\resource;
 
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
 
/**
 * Provides a resource to process queue items.
 *
 * @RestResource(
 *   id = "run_queue_worker_multiple",
 *   label = @Translation("Run Queue Worker Multiple"),
 *   uri_paths = {
 *     "create" = "/api/v1/runQueueWorkerMultiple",
 *   }
 * )
 */
class RunQueueWorkerMultiple extends ResourceBase {
 
    protected $queueFactory;
    protected $queueWorkerManager;
    protected $loggerFactory;
 
    public function __construct(array $configuration, $plugin_id, $plugin_definition,
      array $serializer_formats, LoggerInterface $logger, QueueFactory $queue_factory,
      QueueWorkerManagerInterface $queue_worker_manager, LoggerChannelFactoryInterface $logger_factory) {
      parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
      $this->queueFactory = $queue_factory;
      $this->queueWorkerManager = $queue_worker_manager;
      $this->loggerFactory = $logger_factory;
    }
 
    public static function create(ContainerInterface $container, array $configuration,
      $plugin_id, $plugin_definition) {
      return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->getParameter('serializer.formats'),
        $container->get('logger.factory')->get('rest'),
        $container->get('queue'),
        $container->get('plugin.manager.queue_worker'),
        $container->get('logger.factory')
      );
    }
   
    public function post($data) {
      $queue_worker_id = $data['queueId'];
   
      if (empty($queue_worker_id)) {
        return new ResourceResponse([
          'success' => FALSE,
          'message' => 'queueId cannot be empty',
          'status' => 'error',
        ], 400);
      }
      if (!$this->queueWorkerManager->hasDefinition($queue_worker_id)) {
        return new ResourceResponse([
          'success' => FALSE,
          'message' => 'Invalid queueId provided',
          'status' => 'error',
        ], 400);
      }
   
      $queue = $this->queueFactory->get($queue_worker_id);
     
      $queue_worker = $this->queueWorkerManager->createInstance($queue_worker_id);
      $number_of_queue = $queue->numberOfItems();
     
      if ($number_of_queue == 0) {
        return new ResourceResponse([
          'success' => TRUE,
          'message' => 'The queue is empty',
          'queue' => $queue_worker_id,
        ], 200);
      }
 
      $processed_count = 0;
      for ($i = 0; $i < $number_of_queue; $i++) {
        $item = $queue->claimItem();
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
          $processed_count++;
        } catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        }
      }
   
      return new ResourceResponse([
        'success' => TRUE,
        'message' => 'Queue items processed',
        'processed_count' => $processed_count,
      ], 200);
    }
}