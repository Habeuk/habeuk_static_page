<?php

namespace Drupal\habeuk_static_page\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Static Page entities.
 */
class StaticPageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Titre');
    $header['status'] = $this->t('Statut');
    $header['changed'] = $this->t('Modifié');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\habeuk_static_page\Entity\StaticPageInterface $entity */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.hbk_static_page.canonical',
      ['hbk_static_page' => $entity->id()]
    );
    $row['status'] = $entity->isPublished() ? $this->t('Publié') : $this->t('Non publié');
    $row['changed'] = \Drupal::service('date.formatter')->format($entity->getChangedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Add a "Voir" link that opens the fast public page.
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('Voir (page rapide)'),
        'weight' => -10,
        'url' => $entity->toUrl('canonical'),
      ];
    }

    return $operations;
  }

}
