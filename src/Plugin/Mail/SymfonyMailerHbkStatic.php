<?php

namespace Drupal\habeuk_static_page\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\SymfonyMailer;
use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Mime\Email;

/**
 * il faudra ajouter composer require symfony/html-sanitizer plus tard afin de
 * suprimer du contenu intessirable.
 *
 * use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
 * use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
 *
 * $config = (new HtmlSanitizerConfig())
 * // Autoriser les éléments essentiels pour un email
 * ->allowSafeElements()
 * // Autoriser quelques attributs de style inline
 * ->allowAttribute('style', '*')
 * ->allowAttribute('class', '*')
 * // Autoriser les liens
 * ->allowElement('a', ['href', 'title'])
 * // Autoriser les images
 * ->allowElement('img', ['src', 'alt', 'width', 'height']);
 *
 * $sanitizer = new HtmlSanitizer($config);
 *
 * // Dans votre plugin, avant d'envoyer :
 * $cleanHtml = $sanitizer->sanitize($wrapped_body);
 * $email->html($cleanHtml);
 */
// use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
#[Mail(id: 'symfony_mailer_hbk_static', label: new TranslatableMarkup('Symfony mailer Hbk Static'))]
class SymfonyMailerHbkStatic extends SymfonyMailer {
  
  /**
   *
   * {@inheritdoc} Ne fait AUCUNE conversion HTML → texte. Le body reste du
   *               HTML.
   */
  public function format(array $message) {
    // On joint simplement les parties, sans htmlToText()
    $message['body'] = implode("\n", $message['body']);
    return $message;
  }
  
  /**
   * En fonction du besoin regarde :
   * https://grok.com/c/bfbc39d0-394c-4c92-8c29-00b90c68c675?rid=1fb78aef-8b73-4a67-b723-e7039dc916b9
   *
   * {@inheritdoc}
   */
  public function mail(array $message) {
    try {
      $wrapped_body = $this->wrapBody($message['body'], $message['params'] ?? []);
      $email = new Email();
      // 1. Headers standards (From, Sender, Reply-To, etc.)
      $headers = $email->getHeaders();
      foreach ($message['headers'] as $name => $value) {
        if (!in_array(strtolower($name), self::SKIP_HEADERS, TRUE)) {
          if (in_array(strtolower($name), self::MAILBOX_LIST_HEADERS, TRUE)) {
            $value = str_getcsv($value, escape: '\\');
          }
          $headers->addHeader($name, $value);
        }
      }
      
      // 2. Destinataire principal + sujet + corps HTML
      
      $email->to($message['to'])->subject($message['subject'])->html($wrapped_body);
      
      // 3. Reply-To explicite via $params (prioritaire sur le header)
      if (!empty($message['params']['reply_to'])) {
        $email->replyTo($message['params']['reply_to']);
      }
      
      // 4. CC explicite via $params
      if (!empty($message['params']['cc'])) {
        foreach ($this->normalizeAddresses($message['params']['cc']) as $address) {
          $email->cc($address);
        }
      }
      
      // 5. BCC explicite via $params
      if (!empty($message['params']['bcc'])) {
        foreach ($this->normalizeAddresses($message['params']['bcc']) as $address) {
          $email->bcc($address);
        }
      }
      
      // 6. Pièces jointes
      $attachments = $this->extractAttachments($message);
      foreach ($attachments as $attachment) {
        $email->attachFromPath($attachment['path'], $attachment['name'], $attachment['mime']);
      }
      
      $mailer = $this->getMailer();
      $mailer->send($email);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal\Core\Utility\Error::logException($this->logger, $e);
      return FALSE;
    }
  }
  
  /**
   * Enveloppe le corps HTML dans le template de wrap.
   */
  protected function wrapBody(string $body, array $params = []): string {
    $header = $params['header'] ?? '';
    $footer = $params['footer'] ?? '';
    
    $render = [
      '#theme' => 'habeuk_email_wrap',
      '#body' => \Drupal\Core\Render\Markup::create($body),
      '#header' => $header ? \Drupal\Core\Render\Markup::create($header) : NULL,
      '#footer' => $footer ? \Drupal\Core\Render\Markup::create($footer) : NULL
    ];
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = \Drupal::service('renderer');
    $html = $renderer->renderPlain($render);
    
    // Nettoyage des commentaires HTML (utile surtout en mode dev)
    $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);
    return $html;
  }
  
  /**
   * Normalise une liste d'adresses (string CSV ou array) en tableau propre.
   *
   * @param string|array $input
   *        Adresses séparées par des virgules, ou tableau d'adresses.
   *        
   * @return array Tableau d'adresses nettoyées.
   */
  protected function normalizeAddresses($input): array {
    if (is_array($input)) {
      $addresses = $input;
    }
    else {
      $addresses = explode(',', (string) $input);
    }
    $addresses = array_map('trim', $addresses);
    return array_filter($addresses, fn ($a) => $a !== '');
  }
  
  /**
   * Extrait les pièces jointes du tableau de message.
   *
   * @param array $message
   *        Le tableau de message Drupal.
   *        
   * @return array Un tableau de chemins absolus et leurs métadonnées.
   */
  protected function extractAttachments(array $message): array {
    $attachments = [];
    $file_system = \Drupal::service('file_system');
    
    $sources = [
      $message['attachments'] ?? [],
      $message['params']['attachments'] ?? []
    ];
    
    foreach ($sources as $source) {
      if (!empty($source) && is_array($source)) {
        foreach ($source as $attachment) {
          if (is_array($attachment)) {
            $uri = $attachment['filepath'] ?? $attachment['path'] ?? NULL;
            if ($uri) {
              // LE POINT CLÉ : convertir public://... en chemin absolu
              $real_path = $file_system->realpath($uri);
              if ($real_path && is_file($real_path)) {
                $attachments[] = [
                  'path' => $real_path,
                  'name' => $attachment['filename'] ?? basename($real_path),
                  'mime' => $attachment['filemime'] ?? mime_content_type($real_path)
                ];
              }
            }
          }
        }
      }
    }
    return $attachments;
  }
  
}