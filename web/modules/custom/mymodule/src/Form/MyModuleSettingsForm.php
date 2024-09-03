<?php

namespace Drupal\mymodule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MyModuleSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['mymodule.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'mymodule_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('mymodule.settings');

        $form['numeric_value'] = [
            '#type' => 'number',
            '#title' => $this->t('Numeric Value'),
            '#description' => $this->t('Enter a numeric value.'),
            '#default_value' => $config->get('numeric_value'),
            '#required' => TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('mymodule.settings')
            ->set('numeric_value', $form_state->getValue('numeric_value'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
