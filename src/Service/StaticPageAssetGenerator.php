<?php

namespace Drupal\habeuk_static_page\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\habeuk_static_page\Entity\StaticPageInterface;

/**
 * Génère les fichiers CSS et JS pour les pages statiques.
 */
class StaticPageAssetGenerator {
  
  /**
   * Dossier de destination relatif au dossier public.
   */
  const ASSET_DIRECTORY = 'public://habeuk_static_page';
  
  /**
   * Le service file_system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;
  
  /**
   * Constructeur.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }
  
  /**
   * Génère (ou régénère) les fichiers CSS et JS d'une page statique.
   *
   * @return array Tableau avec les URI des fichiers générés + un flag
   *         "changed".
   */
  public function generate(StaticPageInterface $page) {
    $this->ensureDirectoryExists();
    
    $id = $page->id();
    $base_name = 'habeuk-static-' . $id;
    
    $css_uri = self::ASSET_DIRECTORY . '/' . $base_name . '.css';
    $js_uri = self::ASSET_DIRECTORY . '/' . $base_name . '.js';
    
    $css = (string) $page->getBody();
    $js = (string) $page->getFooter();
    
    $css_changed = $this->syncFile($css_uri, $css);
    $js_changed = $this->syncFile($js_uri, $js);
    
    return [
      'css' => $css_uri,
      'js' => $js_uri,
      'changed' => $css_changed || $js_changed
    ];
  }
  
  /**
   * Supprime les fichiers associés à une page statique.
   */
  public function delete(StaticPageInterface $page) {
    $id = $page->id();
    $base_name = 'habeuk-static-' . $id;
    
    foreach ([
      '.css',
      '.js'
    ] as $ext) {
      $uri = self::ASSET_DIRECTORY . '/' . $base_name . $ext;
      $this->deleteIfExists($uri);
    }
  }
  
  protected function ensureDirectoryExists() {
    $directory = self::ASSET_DIRECTORY;
    
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new \RuntimeException('Impossible de créer le dossier ' . $directory);
    }
  }
  
  /**
   * Synchronise un fichier avec le contenu fourni.
   *
   * - Si le contenu est vide (ou uniquement des espaces), supprime le fichier.
   * - Si le contenu est identique à celui du fichier existant, ne fait rien.
   * - Sinon, écrit (ou réécrit) le fichier.
   *
   * @param string $uri
   *        URI du fichier cible.
   * @param string $content
   *        Contenu à écrire.
   *        
   * @return bool TRUE si le fichier a été modifié (créé, réécrit ou supprimé),
   *         FALSE sinon.
   */
  protected function syncFile($uri, $content) {
    // Considérer comme vide si rien ou uniquement des espaces.
    $is_empty = trim($content) === '';
    
    if ($is_empty) {
      // Supprime le fichier s'il existe. Retourne TRUE uniquement si une
      // suppression a réellement eu lieu.
      return $this->deleteIfExists($uri);
    }
    
    // Lecture rapide : si le fichier existe et que le contenu est identique,
    // on ne touche à rien (mtime inchangé → cache HTTP préservé).
    if (file_exists($uri)) {
      $existing = file_get_contents($uri);
      if ($existing === $content) {
        return FALSE;
      }
    }
    
    $this->fileSystem->saveData($content, $uri, FileSystemInterface::EXISTS_REPLACE);
    return TRUE;
  }
  
  /**
   * Supprime un fichier s'il existe.
   *
   * @return bool TRUE si le fichier existait et a été supprimé, FALSE sinon.
   */
  protected function deleteIfExists($uri) {
    if (!file_exists($uri)) {
      return FALSE;
    }
    try {
      $this->fileSystem->delete($uri);
      return TRUE;
    }
    catch (\Exception $e) {
      // On log l'erreur mais on ne casse pas la sauvegarde de l'entité.
      \Drupal::logger('habeuk_static_page')->warning('Impossible de supprimer le fichier @uri : @message', [
        '@uri' => $uri,
        '@message' => $e->getMessage()
      ]);
      return FALSE;
    }
  }
  
}