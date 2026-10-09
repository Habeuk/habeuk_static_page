<?php

namespace Drupal\habeuk_static_page\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Service de génération d'images redimensionnées (dérivés mis en cache).
 */
class ImageResizer {
  
  /**
   * Qualité par défaut selon le format de sortie.
   *
   * WebP tolère une qualité plus basse sans perte visuelle notable,
   * ce qui réduit fortement le poids des fichiers.
   */
  protected const QUALITY = [
    'webp' => 82,
    'jpg' => 85,
    'jpeg' => 85,
    'png' => 90
  ];
  
  /**
   * Schémas de stream wrappers autorisés en entrée.
   */
  protected const ALLOWED_SCHEMES = [
    'public',
    'private'
  ];
  
  public function __construct(protected ImageFactory $imageFactory, protected FileSystemInterface $fileSystem, protected LockBackendInterface $lock, protected LoggerChannelFactoryInterface $loggerFactory) {
  }
  
  /**
   * Génère (ou récupère du cache) une image redimensionnée.
   *
   * @param string $source_uri
   *        URI source (public:// ou private://).
   * @param int $width
   *        Largeur cible en pixels.
   * @param int $height
   *        Hauteur cible en pixels.
   * @param bool $crop
   *        TRUE pour scale & crop, FALSE pour un simple scale.
   * @param string $format
   *        Format de sortie : 'webp', 'jpg', 'png', ou '' pour conserver
   *        l'extension d'origine.
   *        
   * @return string|null URI du fichier généré (public://...) ou NULL en cas
   *         d'échec.
   */
  public function getResizedImage(string $source_uri, int $width, int $height, bool $crop = TRUE, string $format = 'webp'): ?string {
    // 1. Validation des paramètres d'entrée.
    if ($width <= 0 || $height <= 0) {
      return NULL;
    }
    
    $scheme = StreamWrapperManager::getScheme($source_uri);
    if (!in_array($scheme, self::ALLOWED_SCHEMES, TRUE)) {
      return NULL;
    }
    
    // 2. Vérification de l'existence de la source AVANT le lock,
    // pour éviter d'acquérir un verrou inutilement.
    if (!file_exists($source_uri)) {
      return NULL;
    }
    
    // 3. Calcul de l'identifiant unique du dérivé.
    $hash = hash('sha256', implode('|', [
      $source_uri,
      "{$width}x{$height}",
      $crop ? 'c' : 's',
      $format
    ]));
    
    $extension = $this->resolveExtension($source_uri, $format);
    $derivative_uri = "public://styles/habeuk_resize/{$width}x{$height}/{$hash}.{$extension}";
    
    // 4. Cache disque : si le dérivé existe déjà, on le retourne directement.
    if (file_exists($derivative_uri)) {
      return $derivative_uri;
    }
    
    // 5. Lock pour éviter les générations concurrentes.
    $lock_name = 'habeuk_image_resize:' . $hash;
    if (!$this->lock->acquire($lock_name, 30)) {
      // Un autre process génère le même dérivé : on abandonne proprement.
      return NULL;
    }
    
    try {
      // Double vérification après acquisition du lock : un autre process a pu
      // terminer la génération entre-temps.
      if (file_exists($derivative_uri)) {
        return $derivative_uri;
      }
      
      // 6. Création du répertoire de destination si nécessaire.
      $directory = dirname($derivative_uri);
      if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->loggerFactory->get('habeuk_static_page')->error('Impossible de préparer le répertoire de destination : @dir', [
          '@dir' => $directory
        ]);
        return NULL;
      }
      
      // 7. Chargement de l'image source.
      $image = $this->imageFactory->get($source_uri);
      if (!$image->isValid()) {
        $this->loggerFactory->get('habeuk_static_page')->warning('Image source invalide : @uri', [
          '@uri' => $source_uri
        ]);
        return NULL;
      }
      
      // 8. Redimensionnement.
      if ($crop) {
        $image->scaleAndCrop($width, $height);
      }
      else {
        $image->scale($width, $height);
      }
      
      // 9. Sauvegarde avec options adaptées au format.
      $options = $this->buildSaveOptions($format, $image->getToolkitId());
      $image->save($derivative_uri, $options);
      
      // 10. Vérification post-save (certains toolkits échouent
      // silencieusement).
      if (!file_exists($derivative_uri)) {
        $this->loggerFactory->get('habeuk_static_page')->error('Le dérivé n’a pas été créé : @uri', [
          '@uri' => $derivative_uri
        ]);
        return NULL;
      }
      
      return $derivative_uri;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('habeuk_static_page')->error('Erreur resize image (@src) : @msg', [
        '@src' => $source_uri,
        '@msg' => $e->getMessage()
      ]);
      return NULL;
    }
    finally {
      // Le lock est TOUJOURS libéré, même en cas d'exception.
      $this->lock->release($lock_name);
    }
  }
  
  /**
   * Détermine l'extension à utiliser pour le dérivé.
   */
  protected function resolveExtension(string $source_uri, string $format): string {
    if ($format !== '') {
      return strtolower($format);
    }
    $ext = pathinfo($source_uri, PATHINFO_EXTENSION);
    return $ext !== '' ? strtolower($ext) : 'jpg';
  }
  
  /**
   * Construit les options de sauvegarde selon le format et le toolkit.
   *
   * @return array Options passées à ImageInterface::save().
   */
  protected function buildSaveOptions(string $format, string $toolkit_id): array {
    $format = strtolower($format);
    $options = [];
    
    // Qualité : appliquée pour les formats lossy supportés.
    if (isset(self::QUALITY[$format])) {
      $options['quality'] = self::QUALITY[$format];
    }
    
    // Conversion explicite vers WebP si le toolkit est GD ou Imagick.
    // GD supporte WebP si compilé avec --with-webp (vérifiable via gd_info()).
    // Imagick supporte WebP s'il est compilé avec libwebp.
    if ($format === 'webp') {
      if ($toolkit_id === 'gd' && !$this->gdSupportsWebp()) {
        // Fallback : on ne force pas WebP si GD ne le supporte pas.
        // L'appelant recevra un fichier avec l'extension .webp mais dont le
        // contenu restera dans le format d'origine : mieux vaut logger.
        $this->loggerFactory->get('habeuk_static_page')->warning('GD ne supporte pas WebP sur ce serveur ; le dérivé peut ne pas être au format attendu.');
      }
      // Forcer le toolkit à convertir explicitement (utile pour Imagick).
      $options['webp'] = TRUE;
    }
    
    return $options;
  }
  
  /**
   * Vérifie si l'extension GD du serveur supporte WebP.
   */
  protected function gdSupportsWebp(): bool {
    if (!function_exists('gd_info')) {
      return FALSE;
    }
    $info = gd_info();
    return !empty($info['WebP Support']);
  }
  
}