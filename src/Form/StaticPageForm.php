<?php

namespace Drupal\habeuk_static_page\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Form controller for the Static Page entity edit forms.
 */
class StaticPageForm extends ContentEntityForm {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Helpful description for AI workflow.
    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Aide – Workflow IA'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['help']['text'] = [
      '#markup' => '<p>' . $this->t('1. Demandez à l’IA de générer une page HTML complète (ou séparée en header / body / footer).<br>
2. Collez le début (doctype + head + ouverture body) dans <strong>Entête HTML</strong>.<br>
3. Collez le contenu principal dans <strong>Body</strong>.<br>
4. Collez la fermeture + scripts dans <strong>Pied de page HTML</strong>.<br>
5. Publiez → la page est servie instantanément sans aucun CSS/JS Drupal.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('La page statique %label a été créée.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('La page statique %label a été mise à jour.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect('entity.hbk_static_page.collection');
    return $status;
  }

}
