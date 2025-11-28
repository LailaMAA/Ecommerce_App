<?php
require_once 'config/config.php';
require_once 'classes/Security.php';

class ImageManager {
    private $pdo;
    private $uploadPath;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->uploadPath = UPLOAD_PATH;
        $this->allowedTypes = ALLOWED_IMAGE_TYPES;
        $this->maxFileSize = MAX_FILE_SIZE;
        
        // Création des dossiers nécessaires
        $this->createDirectories();
    }
    
    private function createDirectories() {
        $directories = [
            $this->uploadPath,
            $this->uploadPath . 'products/',
            $this->uploadPath . 'products/original/',
            $this->uploadPath . 'products/thumbnails/',
            $this->uploadPath . 'products/watermarked/',
            $this->uploadPath . 'watermarks/',
            $this->uploadPath . 'temp/'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    public function uploadProductImage($file, $productId, $isPrimary = false) {
        try {
            // Validation du fichier
            Security::validateFileUpload($file);
            
            // Génération du nom de fichier
            $filename = Security::generateFilename($file['name'], 'product_');
            $originalPath = $this->uploadPath . 'products/original/' . $filename;
            
            // Upload du fichier original
            if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
                throw new Exception("Erreur lors de l'upload du fichier");
            }
            
            // Récupération des informations de l'image
            $imageInfo = getimagesize($originalPath);
            if (!$imageInfo) {
                unlink($originalPath);
                throw new Exception("Fichier image invalide");
            }
            
            // Conversion automatique en différents formats
            $convertedImages = $this->convertToMultipleFormats($originalPath, $filename);
            
            // Ajout du watermark
            $watermarkedImages = $this->addWatermarkToImages($convertedImages);
            
            // Création des miniatures
            $thumbnails = $this->createThumbnails($originalPath, $filename);
            
            // Sauvegarde en base de données
            $imageId = $this->saveImageToDatabase($productId, $file['name'], $filename, $imageInfo, $isPrimary);
            
            // Mise à jour des autres images du produit si c'est l'image principale
            if ($isPrimary) {
                $this->setAsPrimaryImage($productId, $imageId);
            }
            
            return [
                'success' => true,
                'image_id' => $imageId,
                'filename' => $filename,
                'formats' => array_keys($convertedImages),
                'thumbnails' => array_keys($thumbnails)
            ];
            
        } catch (Exception $e) {
            // Nettoyage en cas d'erreur
            if (isset($originalPath) && file_exists($originalPath)) {
                unlink($originalPath);
            }
            throw $e;
        }
    }
    
    private function convertToMultipleFormats($originalPath, $baseFilename) {
        $convertedImages = [];
        $imageInfo = getimagesize($originalPath);
        $mimeType = $imageInfo['mime'];
        
        // Chargement de l'image originale
        $originalImage = $this->loadImage($originalPath, $mimeType);
        if (!$originalImage) {
            throw new Exception("Impossible de charger l'image originale");
        }
        
        // Formats de sortie
        $formats = [
            'jpg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            'webp' => IMAGETYPE_WEBP
        ];
        
        foreach ($formats as $extension => $imageType) {
            $outputPath = $this->uploadPath . 'products/' . $extension . '/' . pathinfo($baseFilename, PATHINFO_FILENAME) . '.' . $extension;
            
            // Création du dossier si nécessaire
            $dir = dirname($outputPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Conversion
            if ($this->saveImage($originalImage, $outputPath, $imageType)) {
                $convertedImages[$extension] = $outputPath;
            }
        }
        
        imagedestroy($originalImage);
        return $convertedImages;
    }
    
    private function addWatermarkToImages($images) {
        $watermarkedImages = [];
        
        // Chargement du watermark
        $watermarkPath = $this->uploadPath . 'watermarks/watermark.png';
        if (!file_exists($watermarkPath)) {
            $this->createDefaultWatermark();
        }
        
        $watermark = imagecreatefrompng($watermarkPath);
        if (!$watermark) {
            throw new Exception("Impossible de charger le watermark");
        }
        
        foreach ($images as $format => $imagePath) {
            $watermarkedPath = $this->uploadPath . 'products/watermarked/' . basename($imagePath);
            
            // Création du dossier si nécessaire
            $dir = dirname($watermarkedPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Application du watermark
            if ($this->applyWatermark($imagePath, $watermark, $watermarkedPath)) {
                $watermarkedImages[$format] = $watermarkedPath;
            }
        }
        
        imagedestroy($watermark);
        return $watermarkedImages;
    }
    
    private function applyWatermark($imagePath, $watermark, $outputPath) {
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        $image = $this->loadImage($imagePath, $mimeType);
        if (!$image) {
            return false;
        }
        
        // Redimensionnement du watermark si nécessaire
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        
        // Calcul de la taille du watermark (20% de l'image)
        $newWatermarkWidth = $imageWidth * 0.2;
        $newWatermarkHeight = ($watermarkHeight * $newWatermarkWidth) / $watermarkWidth;
        
        if ($newWatermarkWidth > $watermarkWidth) {
            $newWatermarkWidth = $watermarkWidth;
            $newWatermarkHeight = $watermarkHeight;
        }
        
        // Redimensionnement du watermark
        $resizedWatermark = imagecreatetruecolor($newWatermarkWidth, $newWatermarkHeight);
        imagealphablending($resizedWatermark, false);
        imagesavealpha($resizedWatermark, true);
        imagecopyresampled($resizedWatermark, $watermark, 0, 0, 0, 0, $newWatermarkWidth, $newWatermarkHeight, $watermarkWidth, $watermarkHeight);
        
        // Position du watermark (coin inférieur droit)
        $watermarkX = $imageWidth - $newWatermarkWidth - 20;
        $watermarkY = $imageHeight - $newWatermarkHeight - 20;
        
        // Application du watermark
        imagecopy($image, $resizedWatermark, $watermarkX, $watermarkY, 0, 0, $newWatermarkWidth, $newWatermarkHeight);
        
        // Sauvegarde
        $result = $this->saveImage($image, $outputPath, $imageInfo[2]);
        
        imagedestroy($image);
        imagedestroy($resizedWatermark);
        
        return $result;
    }
    
    private function createThumbnails($originalPath, $baseFilename) {
        $thumbnails = [];
        $sizes = [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600]
        ];
        
        $imageInfo = getimagesize($originalPath);
        $mimeType = $imageInfo['mime'];
        
        $originalImage = $this->loadImage($originalPath, $mimeType);
        if (!$originalImage) {
            return $thumbnails;
        }
        
        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailPath = $this->uploadPath . 'products/thumbnails/' . $sizeName . '_' . $baseFilename;
            
            $thumbnail = $this->createThumbnail($originalImage, $dimensions[0], $dimensions[1]);
            if ($thumbnail && $this->saveImage($thumbnail, $thumbnailPath, $imageInfo[2])) {
                $thumbnails[$sizeName] = $thumbnailPath;
            }
            
            if ($thumbnail) {
                imagedestroy($thumbnail);
            }
        }
        
        imagedestroy($originalImage);
        return $thumbnails;
    }
    
    private function createThumbnail($originalImage, $maxWidth, $maxHeight) {
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);
        
        // Calcul des nouvelles dimensions en gardant les proportions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = $originalWidth * $ratio;
        $newHeight = $originalHeight * $ratio;
        
        // Création de la miniature
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbnail, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        return $thumbnail;
    }
    
    private function loadImage($path, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    private function saveImage($image, $path, $imageType) {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $path, 90);
            case IMAGETYPE_PNG:
                return imagepng($image, $path, 9);
            case IMAGETYPE_GIF:
                return imagegif($image, $path);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $path, 90);
            default:
                return false;
        }
    }
    
    private function createDefaultWatermark() {
        $watermarkPath = $this->uploadPath . 'watermarks/watermark.png';
        
        // Création d'un watermark simple
        $watermark = imagecreatetruecolor(200, 50);
        $transparent = imagecolorallocatealpha($watermark, 0, 0, 0, 127);
        imagefill($watermark, 0, 0, $transparent);
        
        $textColor = imagecolorallocate($watermark, 255, 255, 255);
        $font = 5; // Police système
        
        // Texte du watermark
        $text = SITE_NAME;
        imagestring($watermark, $font, 10, 15, $text, $textColor);
        
        imagepng($watermark, $watermarkPath);
        imagedestroy($watermark);
    }
    
    private function saveImageToDatabase($productId, $originalFilename, $filename, $imageInfo, $isPrimary) {
        $stmt = $this->pdo->prepare("
            INSERT INTO product_images (product_id, original_filename, file_path, file_type, file_size, width, height, is_primary, watermark_added) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $productId,
            $originalFilename,
            $filename,
            $imageInfo['mime'],
            filesize($this->uploadPath . 'products/original/' . $filename),
            $imageInfo[0],
            $imageInfo[1],
            $isPrimary ? 1 : 0,
            1
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    private function setAsPrimaryImage($productId, $imageId) {
        // Retirer le statut principal des autres images
        $stmt = $this->pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        // Définir la nouvelle image principale
        $stmt = $this->pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
        $stmt->execute([$imageId]);
    }
    
    public function getProductImages($productId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_images 
            WHERE product_id = ? 
            ORDER BY is_primary DESC, sort_order ASC, created_at ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function deleteProductImage($imageId) {
        // Récupération des informations de l'image
        $stmt = $this->pdo->prepare("SELECT * FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();
        
        if (!$image) {
            throw new Exception("Image non trouvée");
        }
        
        // Suppression des fichiers
        $baseFilename = pathinfo($image['file_path'], PATHINFO_FILENAME);
        $extensions = ['jpg', 'png', 'webp'];
        
        foreach ($extensions as $ext) {
            $paths = [
                $this->uploadPath . 'products/original/' . $image['file_path'],
                $this->uploadPath . 'products/' . $ext . '/' . $baseFilename . '.' . $ext,
                $this->uploadPath . 'products/watermarked/' . $baseFilename . '.' . $ext,
                $this->uploadPath . 'products/thumbnails/small_' . $image['file_path'],
                $this->uploadPath . 'products/thumbnails/medium_' . $image['file_path'],
                $this->uploadPath . 'products/thumbnails/large_' . $image['file_path']
            ];
            
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        
        // Suppression de la base de données
        $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        
        return true;
    }
    
    public function getImageUrl($imagePath, $size = 'original', $watermarked = false) {
        $baseUrl = SITE_URL . '/' . UPLOAD_PATH;
        
        if ($watermarked) {
            return $baseUrl . 'products/watermarked/' . basename($imagePath);
        }
        
        switch ($size) {
            case 'small':
                return $baseUrl . 'products/thumbnails/small_' . basename($imagePath);
            case 'medium':
                return $baseUrl . 'products/thumbnails/medium_' . basename($imagePath);
            case 'large':
                return $baseUrl . 'products/thumbnails/large_' . basename($imagePath);
            default:
                return $baseUrl . 'products/original/' . basename($imagePath);
        }
    }
}
?>
