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
        // TODO: Implement buildForm() method.
    }
}