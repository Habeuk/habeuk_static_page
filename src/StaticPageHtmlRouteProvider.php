<?php

namespace Drupal\habeuk_static_page;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Static Page entities.
 */
class StaticPageHtmlRouteProvider extends AdminHtmlRouteProvider {
  
  /**
   *
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $entity_type_id = $entity_type->id();
    
    // Rétablir les chemins personnalisés.
    if ($route = $collection->get("entity.$entity_type_id.collection")) {
      $route->setPath('/admin/content/static-pages');
    }
    if ($route = $collection->get("entity.$entity_type_id.add_form")) {
      $route->setPath('/admin/content/static-pages/add');
    }
    if ($route = $collection->get("entity.$entity_type_id.edit_form")) {
      $route->setPath('/admin/content/static-pages/{hbk_static_page}/edit');
    }
    if ($route = $collection->get("entity.$entity_type_id.delete_form")) {
      $route->setPath('/admin/content/static-pages/{hbk_static_page}/delete');
    }
    
    // Route settings.
    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }
    
    // Canonical.
    if ($route = $collection->get("entity.$entity_type_id.canonical")) {
      $route->setOption('_admin_route', FALSE);
    }
    
    return $collection;
  }
  
  /**
   * Gets the settings form route.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route->setDefaults([
        '_form' => 'Drupal\habeuk_static_page\Form\StaticPageSettingsForm',
        '_title' => "{$entity_type->getLabel()} settings"
      ])->setRequirement('_permission', $entity_type->getAdminPermission())->setOption('_admin_route', TRUE);
      
      return $route;
    }
    return NULL;
  }
  
}