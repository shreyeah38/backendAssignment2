<?php

namespace Drupal\mymodule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;

class CsvUploadController extends ControllerBase {

  /**
   * Handles the CSV file upload and processes the rows.
   */
  public function uploadCsv(Request $request) {
    // Get the uploaded file.
    $file = $request->files->get('csv_file');
    if (!$file || $file->getClientOriginalExtension() !== 'csv') {
      return new JsonResponse(['error' => 'Invalid file format. Only CSV files are allowed.'], 400);
    }

    // Save the file temporarily.
    $directory = 'public://uploads';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    try {
      $saved_file = \Drupal::service('file_system')->saveData(file_get_contents($file->getPathname()), $directory . '/' . $file->getClientOriginalName(), FileSystemInterface::EXISTS_REPLACE);
    } catch (FileException $e) {
      return new JsonResponse(['error' => 'Failed to save the file.'], 500);
    }

    // Read the configuration value for the number of rows to process.
    $config = $this->config('mymodule.settings');
    $n = $config->get('numeric_value');

    // Open the CSV file and count the rows.
    $row_count = 0;
    $rows = [];

    if (($handle = fopen($file->getPathname(), 'r')) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $rows[] = $data;
        $row_count++;
      }
      fclose($handle);
    }

    if ($row_count < $n) {
      return new JsonResponse(['error' => 'The CSV file contains fewer rows than the required minimum of ' . $n . '.'], 400);
    }

    if ($row_count > $n) {
      $logger = \Drupal::service('logger.factory')->get('csv_upload');
      for ($i = $n; $i < $row_count; $i++) {
        $logger->info('Extra row logged: @row', ['@row' => implode(',', $rows[$i])]);
      }
    }

    return new JsonResponse([
      'message' => 'File uploaded successfully.',
      'file_path' => $saved_file,
    ]);
  }

}
