<?php
declare(strict_types = 1);

namespace Drupal\habeuk_static_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Stephane888\Debug\Repositories\ConfigDrupal;

/**
 * Formulaire de test d'envoi de mail.
 */
final class TestMailForm extends FormBase {
  
  /**
   * Valeur par défaut du champ "envoyé par".
   */
  const DEFAULT_FROM = 'stephanekouwa@habeuk.com';
  
  /**
   * Valeur par défaut du champ "envoyer à".
   */
  const DEFAULT_TO = 'habeuk@habeuk.com';
  
  /**
   * Valeur par défaut du champ description.
   */
  const DEFAULT_DESCRIPTION = 'Ceci est un mail de test envoyé depuis le module habeuk_static_page.';
  
  /**
   * Le service mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->mailManager = $container->get('plugin.manager.mail');
    return $instance;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'habeuk_static_page_test_mail_form';
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    
    //
    $form['from'] = [
      '#type' => 'email',
      '#title' => $this->t('Envoyé par'),
      '#description' => $this->t('Si vide, @default sera utilisé.', [
        '@default' => self::DEFAULT_FROM
      ]),
      '#default_value' => self::DEFAULT_FROM,
      '#required' => FALSE
    ];
    
    $form['to'] = [
      '#type' => 'email',
      '#title' => $this->t('Envoyer à'),
      '#default_value' => self::DEFAULT_TO,
      '#required' => TRUE
    ];
    
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => self::DEFAULT_DESCRIPTION,
      '#rows' => 6,
      '#required' => TRUE
    ];
    
    $form['actions'] = [
      '#type' => 'actions'
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Envoyer'),
      '#button_type' => 'primary'
    ];
    
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $to = trim((string) $form_state->getValue('to'));
    if ($to === '' || !\Drupal::service('email.validator')->isValid($to)) {
      $form_state->setErrorByName('to', $this->t("L'adresse du destinataire est invalide."));
    }
    
    $from = trim((string) $form_state->getValue('from'));
    if ($from !== '' && !\Drupal::service('email.validator')->isValid($from)) {
      $form_state->setErrorByName('from', $this->t("L'adresse de l'expéditeur est invalide."));
    }
  }
  
  /**
   * Envoit de formulaire.
   * Le from provient de
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $to = trim((string) $form_state->getValue('to'));
    $replyTo = trim((string) $form_state->getValue('from')); // ← renomme, c'est
                                                             // un Reply-To
    $description = trim((string) $form_state->getValue('description'));
    
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    
    // Les données passées au hook_mail()
    $params = [
      'subject' => "Test d'envoi depuis habeuk_static_page",
      'body' => $description,
      // tu peux aussi passer l'adresse "from" du formulaire si hook_mail doit
      // la lire
      'from' => $replyTo ?: self::DEFAULT_FROM
    ];
    
    // Appel standard : module, key, to, langcode, params, reply, send
    $result = $this->mailManager->mail('habeuk_static_page', 'test_mail', $to, $langcode, $params, $replyTo ?: NULL, TRUE);
    
    if (!empty($result['result'])) {
      $this->messenger()->addStatus($this->t('Mail envoyé à @to.', [
        '@to' => $to
      ]));
    }
    else {
      $this->messenger()->addError($this->t("Échec de l'envoi du mail à @to.", [
        '@to' => $to
      ]));
    }
  }
  
  /**
   * Envoit de formulaire.
   *
   * {@inheritdoc}
   */
  public function submitFormOLD(array &$form, FormStateInterface $form_state): void {
    $to = trim((string) $form_state->getValue('to'));
    $from = trim((string) $form_state->getValue('from'));
    $description = trim((string) $form_state->getValue('description'));
    
    if ($from === '') {
      $from = self::DEFAULT_FROM;
    }
    
    $subject = $this->t("Test d'envoi depuis habeuk_static_page");
    $body = $description;
    
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    
    $params = [
      'subject' => $subject,
      'body' => $body
    ];
    
    $result = $this->mailManager->mail('habeuk_static_page', 'test_mail', $to, $langcode, $params, $from, TRUE);
    
    if (!empty($result['result'])) {
      $this->messenger()->addStatus($this->t('Mail envoyé à @to.', [
        '@to' => $to
      ]));
    }
    else {
      $this->messenger()->addError($this->t("Échec de l'envoi du mail à @to.", [
        '@to' => $to
      ]));
    }
  }
  
}