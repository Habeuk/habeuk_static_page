<?php

namespace Drupal\habeuk_static_page;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Static Page entity.
 *
 * Pages publiées = accessibles à tout le monde (anonymes inclus).
 * Pages non publiées = réservées aux administrateurs.
 */
class StaticPageAccessControlHandler extends EntityAccessControlHandler {
  
  /**
   *
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\habeuk_static_page\Entity\StaticPageInterface $entity */
    switch ($operation) {
      case 'view':
        // Page publiée → accessible à tout le monde (y compris anonyme).
        if ($entity->isPublished()) {
          return AccessResult::allowed()->addCacheableDependency($entity);
        }
        // Page non publiée → uniquement les admins.
        return AccessResult::allowedIfHasPermission($account, 'administer hbk_static_page')->addCacheableDependency($entity);
      
      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit hbk_static_page')->orIf(AccessResult::allowedIfHasPermission($account, 'administer hbk_static_page'))->addCacheableDependency($entity);
      
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete hbk_static_page')->orIf(AccessResult::allowedIfHasPermission($account, 'administer hbk_static_page'))->addCacheableDependency($entity);
    }
    
    return AccessResult::neutral();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create hbk_static_page')->orIf(AccessResult::allowedIfHasPermission($account, 'administer hbk_static_page'));
  }
  
}