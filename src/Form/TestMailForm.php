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
  const DEFAULT_DESCRIPTION = <<<HTML
    <h2 style="font-family: Arial, Helvetica, sans-serif; font-size: 20px; font-weight: bold; color: #1a3c6e; margin: 0 0 12px 0; padding: 0;">
      Test d'envoi de mail
    </h2>
    
    <p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; margin: 0 0 12px 0;">
      Ceci est un <strong style="color: #1a3c6e;">mail de test</strong> envoyé depuis le module
      <span style="background-color: #eef3fa; color: #1a3c6e; padding: 2px 6px; border-radius: 3px; font-family: Consolas, Monaco, monospace; font-size: 13px;">
        habeuk_static_page
      </span>.
    </p>
    
    <p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; margin: 0 0 12px 0;">
      Vous pouvez modifier ce contenu, il sera envoyé tel quel dans le corps du mail.
    </p>
    
    <hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">
    
    <p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.5; color: #777777; margin: 0;">
      <em>Habeuk — Développement web &amp; solutions digitales</em><br>
      <a href="https://habeuk.com" style="color: #1a3c6e; text-decoration: underline;">https://habeuk.com</a>
    </p>    
  HTML;
  
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
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => "<strong>" . date("d-m-y h:i:s") . "</strong>" . self::DEFAULT_DESCRIPTION,
      '#format' => 'full_html', // ← le format par défaut
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
    $replyTo = trim((string) $form_state->getValue('from'));
    $value = $form_state->getValue('description');
    $description = !empty($value["value"]) ? trim((string) $value["value"]) : '';
    
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    
    // Les données passées au hook_mail()
    $params = [
      'subject' => "Test d'envoi depuis habeuk_static_page " . date("d-m-y h:i:s"),
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