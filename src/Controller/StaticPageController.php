<?php

namespace Drupal\habeuk_static_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\habeuk_static_page\Entity\StaticPageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for ultra-fast static page rendering.
 *
 * This controller completely bypasses the Drupal theme system,
 * asset libraries, and most of the render pipeline.
 * It returns pure HTML for maximum performance (promo pages).
 */
class StaticPageController extends ControllerBase {
  
  /**
   * Renders the static page as pure HTML.
   *
   * @param \Drupal\habeuk_static_page\Entity\StaticPageInterface $hbk_static_page
   *        The static page entity.
   *        
   * @return \Drupal\Core\Cache\CacheableResponse A cacheable HTML response.
   */
  public function view(StaticPageInterface $hbk_static_page) {
    if (!$hbk_static_page->isPublished() && !$this->currentUser()->hasPermission('administer hbk_static_page')) {
      throw new NotFoundHttpException();
    }
    
    // Build the full HTML document by simple concatenation.
    // This is intentionally minimal for maximum speed.
    $html = (string) $hbk_static_page->getHeader();
    
    // Create a CacheableResponse so Drupal Page Cache & Dynamic Page Cache
    // work.
    $response = new CacheableResponse($html, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
      // Strong caching for anonymous users (promo pages rarely change).
      'Cache-Control' => 'public, max-age=3600'
    ]);
    
    // Attach cache metadata from the entity.
    $cache_metadata = CacheableMetadata::createFromObject($hbk_static_page);
    // Also add the list tag so bulk operations invalidate correctly.
    $cache_metadata->addCacheTags([
      'hbk_static_page_list'
    ]);
    // Make it vary by user permissions for unpublished content.
    $cache_metadata->addCacheContexts([
      'user.permissions'
    ]);
    $response->addCacheableDependency($cache_metadata);
    
    return $response;
  }
  
  /**
   * Title callback (used by admin routes if needed).
   */
  public function title(StaticPageInterface $hbk_static_page) {
    return $hbk_static_page->label();
  }
  
}
