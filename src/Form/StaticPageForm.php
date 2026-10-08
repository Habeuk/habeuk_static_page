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
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    
    // Helpful description for AI workflow.
    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Aide – Workflow IA'),
      '#open' => TRUE,
      '#weight' => -20
    ];
    $form['help']['text'] = [
      '#markup' => '<p>' . $this->t('1. Demandez à l’IA de générer une page HTML complète.<br>
        2. Collez le début (doctype + head + ouverture body) dans <strong>Entête HTML</strong>.<br>
        3. Collez le CSS de la page dans <strong>CSS</strong>.<br>
        4. Collez le JavaScript + fermeture de page dans <strong>JavaScript</strong>.<br>
        5. Publiez → la page est servie instantanément sans aucun CSS/JS Drupal.') . '</p>'
    ];
    
    // Bloc des assets générés (uniquement si l'entité existe déjà).
    if (!$this->entity->isNew()) {
      $form['assets'] = [
        '#type' => 'details',
        '#title' => $this->t('Fichiers CSS / JS générés'),
        '#open' => TRUE,
        '#weight' => 19
      ];
      $form['assets']['text'] = [
        '#markup' => $this->buildAssetsMarkup()
      ];
    }
    
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);
    
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('La page statique %label a été créée.', [
          '%label' => $entity->label()
        ]));
        break;
      
      default:
        $this->messenger()->addStatus($this->t('La page statique %label a été mise à jour.', [
          '%label' => $entity->label()
        ]));
    }
    
    $form_state->setRedirect('entity.hbk_static_page.collection');
    return $status;
  }
  
  /**
   * Construit le HTML listant les fichiers CSS/JS générés.
   */
  protected function buildAssetsMarkup() {
    $id = $this->entity->id();
    $base_name = 'habeuk-static-' . $id;
    $css_uri = 'public://habeuk_static_page/' . $base_name . '.css';
    $js_uri = 'public://habeuk_static_page/' . $base_name . '.js';
    
    $lines = [];
    
    // CSS.
    if (file_exists($css_uri)) {
      $css_url = \Drupal::service('file_url_generator')->generateString($css_uri);
      $css_v = filemtime($css_uri);
      $lines[] = '<strong>CSS :</strong> <code>' . $css_url . '</code>';
      $lines[] = '<pre>&lt;link rel="stylesheet" href="' . $css_url . '?v=' . $css_v . '"&gt;</pre>';
    }
    else {
      $lines[] = '<em>' . $this->t('Aucun fichier CSS généré pour le moment. Sauvegardez la page pour en créer un.') . '</em>';
    }
    
    // JS.
    if (file_exists($js_uri)) {
      $js_url = \Drupal::service('file_url_generator')->generateString($js_uri);
      $js_v = filemtime($js_uri);
      $lines[] = '<strong>JS :</strong> <code>' . $js_url . '</code>';
      $lines[] = '<pre>&lt;script src="' . $js_url . '?v=' . $js_v . '" defer&gt;&lt;/script&gt;</pre>';
    }
    else {
      $lines[] = '<em>' . $this->t('Aucun fichier JS généré pour le moment. Sauvegardez la page pour en créer un.') . '</em>';
    }
    
    return implode('<br>', $lines);
  }
  
}
