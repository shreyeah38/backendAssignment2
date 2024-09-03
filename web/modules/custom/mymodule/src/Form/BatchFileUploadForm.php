<?php
 
/**
 * @file
 * A form to collect data for Content details.
 */
 
namespace Drupal\mymodule\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\InvokeCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
 
class BatchFileUploadForm extends FormBase {
 
 
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
    return 'moduledevelopment_form';
  }
 
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSV upload'),
    ];
    $form['file_upload'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'upload_wrapper'],
      '#states' => [
        'visible' => [
          ':input[name="checkbox"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
 
    $form['file_upload']['csv_file'] = [
      '#type'=> 'file',
      '#title' => t('Upload file'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
       ],
    ];
 
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
 
    return $form;
  }
 
 
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('checkbox')) {
      $validators = $form['file_upload']['csv_file']['#upload_validators'];
      $file = file_save_upload('csv_file', $validators, FALSE, 0);
      if ($file) {
        $form_state->setValue('csv_file', $file);
      }
      else {
        $form_state->setErrorByName('csv_file', $this->t('No file was uploaded or the file is not a CSV.'));
      }
    }
  }
 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('checkbox')) {
      $file = $form_state->getValue('csv_file');
      $file->setPermanent();
      $file->save();
 
      $batch = [
        'title' => $this->t('Processing CSV file'),
        'operations' => [],
        'finished' => [$this, 'batchFinished'],
      ];
 
      $file_path = $file->getFileUri();
      if (($handle = fopen($this->fileSystem->realpath($file_path), 'r')) !== FALSE) {
        while (($data = fgetcsv($handle, 100, ",")) !== FALSE) {
          $batch['operations'][] = [
            [$this, 'processRow'],
            [$data],
          ];
        }
        fclose($handle);
      }
 
      batch_set($batch);
    }
  }
 
  /**
   * Batch process callback for each row.
   */
  public function processRow(array $data, &$context) {
    // Assume first column is the ID and second column is the title.
    $age = $data[1];
    $name = $data[0];
    $email = $data[3];
    $gender = $data[2];
 
 
    $node_storage = $this->entityTypeManager->getStorage('node');
    $existing_nodes = $node_storage->loadByProperties(['field_name' => $name]);
 
    if (empty($existing_nodes)) {
      $node = $node_storage->create([
        'type' => 'users',
        'title' => $name,
        'field_age' => $age,
        'field_name' => $name,
        'field_email' => $email,
        'field_gender' => $gender,
      ]);
      $node->save();
    }
  }
 
  /**
   * Batch finished callback.
   */
  public function batchFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addStatus($this->t('CSV file processed successfully.'));
    }
    else {
      \Drupal::messenger()->addError($this->t('An error occurred while processing the CSV file.'));
    }
  }
}