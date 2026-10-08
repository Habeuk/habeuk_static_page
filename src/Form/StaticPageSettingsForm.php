<?php

namespace Drupal\habeuk_static_page\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure les paramètres des pages statiques.
 *
 * Cette page sert principalement de base pour les onglets Field UI
 * ("Gérer les champs", "Gérer l'affichage", etc.).
 */
class StaticPageSettingsForm extends ConfigFormBase {
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'habeuk_static_page_settings';
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'habeuk_static_page.settings'
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['info'] = [
      '#markup' => '<p>' . $this->t("Cette page sert de point d'ancrage pour la gestion des champs et de l'affichage de l'entité Page statique. Utilisez les onglets ci-dessus pour gérer les champs et les formulaires.") . '</p>'
    ];
    
    return parent::buildForm($form, $form_state);
  }
  
}