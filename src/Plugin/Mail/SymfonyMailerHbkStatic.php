<?php

namespace Drupal\habeuk_static_page\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\SymfonyMailer;
use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Mime\Email;

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
      $email = new Email();
      
      $headers = $email->getHeaders();
      foreach ($message['headers'] as $name => $value) {
        if (!in_array(strtolower($name), self::SKIP_HEADERS, TRUE)) {
          if (in_array(strtolower($name), self::MAILBOX_LIST_HEADERS, TRUE)) {
            $value = str_getcsv($value, escape: '\\');
          }
          $headers->addHeader($name, $value);
        }
      }
      
      $email->to($message['to'])->subject($message['subject'])->html($message['body']);
      
      $mailer = $this->getMailer();
      $mailer->send($email);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal\Core\Utility\Error::logException($this->logger, $e);
      return FALSE;
    }
  }
  
}