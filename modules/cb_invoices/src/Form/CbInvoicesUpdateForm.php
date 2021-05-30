<?php

namespace Drupal\cb_invoices\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cb_invoices\CbInvoicesRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sample UI to update a record.
 *
 * @ingroup cb_invoices
 */
class CbInvoicesUpdateForm extends FormBase
{

    /**
     * Our database repository service.
     *
     * @var \Drupal\cb_invoices\CbInvoicesRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'cb_invoices_update_form';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $form = new static($container->get('cb_invoices.repository'));
        $form->setStringTranslation($container->get('string_translation'));
        $form->setMessenger($container->get('messenger'));
        return $form;
    }

    /**
     * Construct the new form object.
     */
    public function __construct(CbInvoicesRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Sample UI to update a record.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Wrap the form in a div.
        $form = [
            '#prefix' => '<div id="updateform">',
            '#suffix' => '</div>',
        ];
        // Add some explanatory text to the form.
        $form['message'] = [
            '#markup' => $this->t('Demonstrates a database update operation.'),
        ];
        // Query for items to display.
        $entries = $this->repository->load();
        // Tell the user if there is nothing to display.
        if (empty($entries)) {
            $form['no_values'] = [
                '#value' => $this->t('No entries exist in the cb_invoices table.'),
            ];
            return $form;
        }

        $keyed_entries = [];
        $options = [];
        foreach ($entries as $entry) {
            $options[$entry->pid] = $this->t('@pid: @name @surname (@age) @price', [
                '@pid' => $entry->pid,
                '@name' => $entry->name,
                '@surname' => $entry->surname,
                '@age' => $entry->age,
                '@price' => $entry->price,
            ]);
            $keyed_entries[$entry->pid] = $entry;
        }

        // Grab the pid.
        $pid = $form_state->getValue('pid');
        // Use the pid to set the default entry for updating.
        $default_entry = !empty($pid) ? $keyed_entries[$pid] : $entries[0];

        // Save the entries into the $form_state. We do this so the AJAX callback
        // doesn't need to repeat the query.
        $form_state->setValue('entries', $keyed_entries);

        $form['pid'] = [
            '#type' => 'select',
            '#options' => $options,
            '#title' => $this->t('Choose entry to update'),
            '#default_value' => $default_entry->pid,
            '#ajax' => [
                'wrapper' => 'updateform',
                'callback' => [$this, 'updateCallback'],
            ],
        ];

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Updated first name'),
            '#size' => 15,
            '#default_value' => $default_entry->name,
        ];

        $form['surname'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Updated last name'),
            '#size' => 15,
            '#default_value' => $default_entry->surname,
        ];
        $form['age'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Updated age'),
            '#size' => 4,
            '#default_value' => $default_entry->age,
            '#description' => $this->t('Values greater than 127 will cause an exception'),
        ];

        $form['add']['price'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Updated price'),
            '#size' => 12,
            '#default_value' => $default_entry->price,
            '#description' => $this->t("Enter a decimal value"),
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Update'),
        ];
        return $form;
    }

    /**
     * AJAX callback handler for the pid select.
     *
     * When the pid changes, populates the defaults from the database in the form.
     */
    public function updateCallback(array $form, FormStateInterface $form_state)
    {
        // Gather the DB results from $form_state.
        $entries = $form_state->getValue('entries');
        // Use the specific entry for this $form_state.
        $entry = $entries[$form_state->getValue('pid')];
        // Setting the #value of items is the only way I was able to figure out
        // to get replaced defaults on these items. #default_value will not do it
        // and shouldn't.
        foreach (['name', 'surname', 'age', 'price'] as $item) {
            $form[$item]['#value'] = $entry->$item;
        }
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        // Confirm that age is numeric.
        if (!intval($form_state->getValue('age'))) {
            $form_state->setErrorByName('age', $this->t('Age needs to be a number'));
        }

        // Confirm that price is numeric.
        if (!intval($form_state->getValue('price'))) {
            $form_state->setErrorByName('price', $this->t('Price needs to be a decimal value'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Gather the current user so the new record has ownership.
        $account = $this->currentUser();
        // Save the submitted entry.
        $entry = [
            'pid' => $form_state->getValue('pid'),
            'name' => $form_state->getValue('name'),
            'surname' => $form_state->getValue('surname'),
            'age' => $form_state->getValue('age'),
            'price' => $form_state->getValue('price'),
            'uid' => $account->id(),
        ];
        $count = $this->repository->update($entry);
        $this->messenger()->addMessage($this->t('Updated entry @entry (@count row updated)', [
            '@count' => $count,
            '@entry' => print_r($entry, TRUE),
        ]));
    }

}
