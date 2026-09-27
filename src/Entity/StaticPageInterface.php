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
   * @return string
   *   Title of the entity.
   */
  public function getTitle();

  /**
   * Sets the title.
   *
   * @param string $title
   *   The title.
   *
   * @return \Drupal\habeuk_static_page\Entity\StaticPageInterface
   *   The called entity.
   */
  public function setTitle($title);

  /**
   * Gets the header HTML.
   *
   * @return string|null
   *   The header content.
   */
  public function getHeader();

  /**
   * Gets the body HTML.
   *
   * @return string|null
   *   The body content.
   */
  public function getBody();

  /**
   * Gets the footer HTML.
   *
   * @return string|null
   *   The footer content.
   */
  public function getFooter();

  /**
   * Gets the creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime();

  /**
   * Sets the creation timestamp.
   *
   * @param int $timestamp
   *   The creation timestamp.
   *
   * @return \Drupal\habeuk_static_page\Entity\StaticPageInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}
