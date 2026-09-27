<?php

namespace Drupal\habeuk_static_page\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Static Page entity (pages promo ultra-rapides).
 *
 * @ContentEntityType(
 *   id = "hbk_static_page",
 *   label = @Translation("Page statique (Promo)"),
 *   label_collection = @Translation("Pages statiques (Promo)"),
 *   label_singular = @Translation("page statique"),
 *   label_plural = @Translation("pages statiques"),
 *   label_count = @PluralTranslation(
 *     singular = "@count page statique",
 *     plural = "@count pages statiques",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\habeuk_static_page\ListBuilder\StaticPageListBuilder",
 *     "access" = "Drupal\habeuk_static_page\StaticPageAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\habeuk_static_page\Form\StaticPageForm",
 *       "add" = "Drupal\habeuk_static_page\Form\StaticPageForm",
 *       "edit" = "Drupal\habeuk_static_page\Form\StaticPageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "hbk_static_page",
 *   data_table = "hbk_static_page_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer hbk_static_page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/promo/{hbk_static_page}",
 *     "add-form" = "/admin/content/static-pages/add",
 *     "edit-form" = "/admin/content/static-pages/{hbk_static_page}/edit",
 *     "delete-form" = "/admin/content/static-pages/{hbk_static_page}/delete",
 *     "collection" = "/admin/content/static-pages",
 *   },
 *   field_ui_base_route = "entity.hbk_static_page.collection",
 * )
 */
class StaticPage extends ContentEntityBase implements StaticPageInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->get('header')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFooter() {
    return $this->get('footer')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    // Owner field.
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titre'))
      ->setDescription(new TranslatableMarkup('Titre interne de la page (utilisé aussi pour le <title> si non présent dans le header).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Header : HTML complet du début de page (doctype, head, ouverture body, etc.)
    // string_long = pas de format texte → zéro overhead de filtrage, HTML/JS libre.
    $fields['header'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Entête HTML'))
      ->setDescription(new TranslatableMarkup('Contient le début de la page HTML (doctype, <html>, <head>, ouverture <body>...). Collez ici le HTML généré par l''IA.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Body : contenu principal
    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Body (contenu)'))
      ->setDescription(new TranslatableMarkup('Contenu principal de la page. Peut être du HTML complet ou juste le contenu entre header et footer.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => [
          'rows' => 20,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Footer : fermeture de page + scripts éventuels
    $fields['footer'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Pied de page HTML'))
      ->setDescription(new TranslatableMarkup('Contient la fin de la page HTML (fermeture </body>, </html>, scripts tracking, etc.).'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => [
          'rows' => 8,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Créé'))
      ->setDescription(new TranslatableMarkup('La date de création.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modifié'))
      ->setDescription(new TranslatableMarkup('La date de dernière modification.'));

    // Path alias support (core path module)
    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(new TranslatableMarkup('URL alias'))
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setComputed(TRUE);

    return $fields;
  }

}
