<?php

namespace Drupal\cb_invoices\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cb_invoices\CbInvoicesRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a database entry, with all the interesting fields.
 *
 * @ingroup cb_invoices
 */
class CbInvoicesAddForm implements FormInterface, ContainerInjectionInterface
{

    use StringTranslationTrait;
    use MessengerTrait;

    /**
     * Our database repository service.
     *
     * @var \Drupal\cb_invoices\CbInvoicesRepository
     */
    protected $repository;

    /**
     * The current user.
     *
     * We'll need this service in order to check if the user is logged in.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * {@inheritdoc}
     *
     * We'll use the ContainerInjectionInterface pattern here to inject the
     * current user and also get the string_translation service.
     */
    public static function create(ContainerInterface $container)
    {
        $form = new static(
            $container->get('cb_invoices.repository'),
            $container->get('current_user')
        );
        // The StringTranslationTrait trait manages the string translation service
        // for us. We can inject the service here.
        $form->setStringTranslation($container->get('string_translation'));
        $form->setMessenger($container->get('messenger'));
        return $form;
    }

    /**
     * Construct the new form object.
     */
    public function __construct(CbInvoicesRepository $repository, AccountProxyInterface $current_user)
    {
        $this->repository = $repository;
        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'cb_invoices_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = [];

        $form['message'] = [
            '#markup' => $this->t('Add an entry to the cb_invoices table.'),
        ];

        $form['add'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add a person entry'),
        ];
        $form['add']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#size' => 15,
        ];
        $form['add']['surname'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Surname'),
            '#size' => 15,
        ];
        $form['add']['age'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Age'),
            '#size' => 5,
            '#description' => $this->t("Values greater than 127 will cause an exception. Try it - it's a great example why exception handling is needed with DTBNG."),
        ];
        $form['add']['price'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Price'),
            '#size' => 12,
            '#description' => $this->t("Values greater than 127 will cause an exception. Try it - it's a great example why exception handling is needed with DTBNG."),
        ];
        $form['add']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        // Verify that the user is logged-in.
        if ($this->currentUser->isAnonymous()) {
            $form_state->setError($form['add'], $this->t('You must be logged in to add values to the database.'));
        }
        // Confirm that age is numeric.
        if (!intval($form_state->getValue('age'))) {
            $form_state->setErrorByName('age', $this->t('Age needs to be a number'));
        }

        // Confirm that price is numeric.
        if (!intval($form_state->getValue('price'))) {
            $form_state->setErrorByName('price', $this->t('Price needs to be a decimal'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Gather the current user so the new record has ownership.
        $account = $this->currentUser;
        // Save the submitted entry.
        $entry = [
            'name' => $form_state->getValue('name'),
            'surname' => $form_state->getValue('surname'),
            'age' => $form_state->getValue('age'),
            'price' => $form_state->getValue('price'),
            'uid' => $account->id(),
        ];
        $return = $this->repository->insert($entry);
        if ($return) {
            $this->messenger()->addMessage($this->t('Created entry @entry', ['@entry' => print_r($entry, TRUE)]));
        }
    }

}
