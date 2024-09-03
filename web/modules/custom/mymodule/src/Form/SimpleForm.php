<?php

namespace Drupal\mymodule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;

class SimpleForm extends FormBase {

   /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
 
  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;
 
  /**
   * Constructs a new CsvImportForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }


   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enable_upload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSV Upload'),
      // '#ajax' => [
      //   'callback' => '::toggleUploadField',
      //   'wrapper' => 'upload-wrapper',
      // ],
    ];

    $form['upload'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'upload-wrapper'],
      '#states' => [
        'visible' => [
          ':input[name="enable_upload"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['upload']['csv_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload CSV Upload'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];

    // $form['upload'] = [
    //   '#type' => 'container',
    //   '#attributes' => ['id' => 'upload-wrapper'],
    // ];

    // if ($form_state->getValue('enable_upload')) {
    //   $form['upload']['csv_file'] = [
    //     '#type' => 'file',
    //     '#title' => $this->t('Upload CSV file'),
    //     '#upload_validators' => [
    //       'file_validate_extensions' => ['csv'],
    //     ],
    //   ];
    // }

    // $form['actions'] = [
    //   '#type' => 'actions',
    // ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function toggleUploadField(array &$form, FormStateInterface $form_state) {
    return $form['upload'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('enable_upload')) {
      $validators = $form['upload']['csv_file']['#upload_validators'];
      $file = file_save_upload('csv_file', $validators, FALSE, 0);
      if ($file) {
        $form_state->setValue('csv_file', $file);
      } else {
        $form_state->setErrorByName('csv_file', $this->t('No file was uploaded or the file is not a CSV.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('enable_upload')) {
      $file = $form_state->getValue('csv_file');
      $file->setPermanent();
      $file->save();
  
      $queue = \Drupal::queue('csv_row_processor');
      $queue->createQueue();
  
      $file_path = $file->getFileUri();
      if (($handle = fopen($this->fileSystem->realpath($file_path), 'r')) !== FALSE) {
        while (($data = fgetcsv($handle, 100, ",")) !== FALSE) {
          \Drupal::logger('csv_row_processor')->info('Queueing data: @data', ['@data' => print_r($data, TRUE)]);
          $queue->createItem($data);
        }
        fclose($handle);
      }
  
      \Drupal::messenger()->addStatus($this->t('CSV file processed successfully and queued for further processing.'));
    }
  }
  
}
