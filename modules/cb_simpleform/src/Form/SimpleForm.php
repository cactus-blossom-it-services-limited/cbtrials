<?php

namespace Drupal\cb_simpleform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimpleForm
 * Uses the private Tempstore to store form values
 *
 *  @see \Drupal\Core\Form\FormBase
 */

class SimpleForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'cb_simple_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $form['url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('RSS url'),
        ];

        $form['list_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Number in list'),
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit')
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        drupal_set_message($form_state->getValue('url'));
    }
}