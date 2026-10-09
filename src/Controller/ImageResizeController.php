<?php

namespace Drupal\habeuk_static_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\habeuk_static_page\Service\ImageResizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Contrôleur de livraison des images redimensionnées à la volée.
 */
class ImageResizeController extends ControllerBase {
  
  /**
   * Formats de sortie autorisés.
   */
  protected const ALLOWED_FORMATS = [
    'webp',
    'jpg',
    'jpeg',
    'png'
  ];
  
  /**
   * Schémas autorisés pour la source.
   */
  protected const ALLOWED_SCHEMES = [
    'public',
    'private'
  ];
  
  public function __construct(protected ImageResizer $resizer, protected FileSystemInterface $fileSystem, protected MimeTypeGuesserInterface $mimeTypeGuesser) {
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('habeuk_static_page.image_resizer'), $container->get('file_system'), $container->get('file.mime_type.guesser'));
  }
  
  /**
   * Livre une image redimensionnée.
   */
  public function deliver(Request $request, int $width, int $height, string $scheme): BinaryFileResponse {
    // 1. Validation stricte du schéma (whitelist).
    if (!in_array($scheme, self::ALLOWED_SCHEMES, TRUE)) {
      throw new NotFoundHttpException();
    }
    
    // 2. Récupération et validation des paramètres de requête.
    $file = $request->query->get('file');
    if (!is_string($file) || $file === '' || str_contains($file, '..') || str_contains($file, "\0")) {
      throw new NotFoundHttpException();
    }
    
    // Normalisation du booléen crop.
    $crop = filter_var($request->query->get('c', '1'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $crop = $crop ?? TRUE;
    
    // Validation du format (whitelist).
    $format = strtolower((string) $request->query->get('f', 'webp'));
    if (!in_array($format, self::ALLOWED_FORMATS, TRUE)) {
      $format = 'webp';
    }
    
    // 3. Limites de sécurité strictes.
    if ($width < 50 || $width > 2800 || $height < 50 || $height > 2800) {
      throw new NotFoundHttpException();
    }
    
    // 4. Reconstruction de l'URI source.
    $source_uri = $scheme . '://' . $file;
    
    // 5. Génération (ou récupération du cache) du dérivé.
    $derivative_uri = $this->resizer->getResizedImage($source_uri, $width, $height, $crop, $format);
    
    if (!$derivative_uri) {
      throw new NotFoundHttpException();
    }
    
    // 6. Résolution du chemin réel.
    $real_path = $this->fileSystem->realpath($derivative_uri);
    if (!$real_path || !is_file($real_path)) {
      throw new NotFoundHttpException();
    }
    
    // 7. Détermination du Content-Type.
    // L'interface Symfony\Component\Mime\MimeTypeGuesserInterface
    // expose la méthode guessMimeType() depuis Symfony 4.3+.
    // Le service file.mime_type.guesser de Drupal l'implémente.
    $content_type = $this->mimeTypeGuesser->guessMimeType($derivative_uri) ?? 'application/octet-stream';
    
    $headers = [
      'Content-Type' => $content_type,
      'Cache-Control' => 'public, max-age=31536000, immutable',
      'X-Content-Type-Options' => 'nosniff'
    ];
    
    // 8. Réponse binaire (le dérivé reste en cache disque, donc FALSE).
    $response = new BinaryFileResponse($real_path, 200, $headers, FALSE);
    
    // Support des requêtes conditionnelles.
    $response->setAutoEtag();
    $response->setAutoLastModified();
    $response->isNotModified($request);
    
    return $response;
  }
  
}