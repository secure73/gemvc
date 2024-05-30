<?php

namespace Gemvc\Helper;

use GdImage;

class ImageHelper extends FileHelper
{
    /**
     * @param string $sourceFile The image path
     * @param string $outputFile The output image path, if null will be the same as $sourceFile
     */
    public function __construct(string $sourceFile, ?string $outputFile = null)
    {
        parent::__construct($sourceFile, $outputFile);
    }

    /**
     * @param ?int $width  The new width of the image, if null will be calculated based on height
     * @param ?int $height The new height of the image, if null will be calculated based on width
     */
    public function toJPEG(?int $width = null, ?int $height = null): bool
    {
        $phpImage = $this->toPhphImageObject($width, $height);
        if ($phpImage) {
            if (imagejpeg($phpImage, $this->outputFile, 100)) {
                imagedestroy($phpImage);
                return true;
            } else {
                $this->error = "given file could not be converted to JPEG image";
            }
        }
        return false;
    }

    /**
     * @param ?int $width  The new width of the image, if null will be calculated based on height
     * @param ?int $height The new height of the image, if null will be calculated based on width
     */
    public function ToPNG(int $width = null, int $height = null): bool
    {
        $phpImage = $this->toPhphImageObject($width, $height);
        if ($phpImage) {
            if (imagepng($phpImage, $this->outputFile, 100)) {
                imagedestroy($phpImage);
                return true;
            } else {
                $this->error = "given file could not be converted to PNG";
            }
        }
        return false;
    }

    public static function isImage(string $filePath): bool
    {
        // Get the MIME type and image information
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo !== false) {
            // Check if the MIME type corresponds to an image
            $imageMimeTypes = array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP,
                IMAGETYPE_WEBP,
                IMAGETYPE_ICO,
                IMAGETYPE_TIFF_II,
                // TIFF little-endian (Intel byte order)
                IMAGETYPE_TIFF_MM // TIFF big-endian (Motorola byte order)
            );
            if (in_array($imageInfo[2], $imageMimeTypes)) {
                return true;
            }
        }
        return false;
    }

    public function getAsBase64String(): string|false
    {
        $content = $this->readFileContents();
        if ($content) {
            return base64_encode($content);
        }
        return false;
    }


    protected function toPhphImageObject(int $width = null, int $height = null): GdImage|false
    {
        if ($this->error) {
            return false;
        }
        $result = $this->readFileContents();
        if (!$result) {
            $this->error = "Failed to read file contents";
            return false;
        }
        $image = imagecreatefromstring($result);
        if (!$image) {
            $this->error = "Given file could not be converted to PhphImage";
            return false;
        }
        if ($width or $height) {
            $image = $this->resize($image, $width, $height);
        }
        return $image;
    }


    private function resize(GdImage $image, int $width = null, int $height = null): GdImage|false
    {
        $newWidth = 0;
        $newHeight = 0;
        $resizedImage = false;
        if (!$width && !$height) {
            $newWidth = imagesx($image);
            $newHeight = imagesy($image);
        }
        if ($width && !$height) {
            $newWidth = $width;
            $newHeight = $this->calculateNewImageHeight($image, $width);
        }
        if (!$width && $height) {
            $newHeight = $height;
            $newWidth = $this->calculateNewImageWidth($image, $height);
        }
        if ($width && $height) {
            $newWidth = $width;
            $newHeight = $height;
        }

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($resizedImage) {
            if (imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($image), imagesy($image))) {
                return $resizedImage;
            } else {
                $this->error = "could not resize image";
                imagedestroy($resizedImage);
                return false;
            }
        }
        return false;
    }

    private function calculateNewImageHeight(GdImage $image, int $newWidth): int
    {
        $ratio = $newWidth / imagesx($image);
        return intval(imagesy($image) * $ratio);
    }
    private function calculateNewImageWidth(GdImage $image, int $newHeight): int
    {
        $ratio = $newHeight / imagesy($image);
        return intval(imagesx($image) * $ratio);
    }
}
