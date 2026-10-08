<?php

namespace Drupal\habeuk_static_page\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Static Page entity.
 */
interface StaticPageInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {
  
  /**
   * Gets the title.
   *
   * @return string Title of the entity.
   */
  public function getTitle();
  
  /**
   * Sets the title.
   *
   * @param string $title
   *        The title.
   *        
   * @return \Drupal\habeuk_static_page\Entity\StaticPageInterface The called
   *         entity.
   */
  public function setTitle($title);
  
  /**
   * Gets the header HTML.
   *
   * @return string|null The header content.
   */
  public function getHeader();
  
  /**
   * Retourne le contenu CSS de la page.
   */
  public function getCss();
  
  /**
   * Retourne le contenu JavaScript de la page.
   */
  public function getJavascript();
  
  /**
   * Gets the creation timestamp.
   *
   * @return int Creation timestamp of the entity.
   */
  public function getCreatedTime();
  
  /**
   * Sets the creation timestamp.
   *
   * @param int $timestamp
   *        The creation timestamp.
   *        
   * @return \Drupal\habeuk_static_page\Entity\StaticPageInterface The called
   *         entity.
   */
  public function setCreatedTime($timestamp);
  
}
