<?php

namespace Gemvc\Helper;

use GdImage;

class FileHelper
{
    public string $sourceFile;
    public string $outputFile;
    public ?string $error = null;
    public ?string $secret;

    public function __construct(string $sourceFile, string $outputFile = null)
    {
        $this->error = null;
        $this->sourceFile = $sourceFile;
        $this->outputFile = $outputFile ?? $sourceFile;

        if (!$this->isDestinationDirectoryExists()) {
            $this->error = "Destination directory does not exist: " . dirname($this->outputFile);
        } elseif (!file_exists($this->sourceFile)) {
            $this->error = "Source file not found: $this->sourceFile";
        }
    }

    public function copy(): bool
    {
        return $this->executeCommand("copy", "cp");
    }

    public function move(): bool
    {
        return $this->executeCommand("move", "mv");
    }

    public function delete(): bool
    {
        return $this->executeCommand("delete", "rm");
    }

    private function executeCommand(string $action, string $command): bool
    {
        if ($this->error) {
            return false;
        }

        $result = shell_exec("$command " . escapeshellarg($this->sourceFile) . " " . escapeshellarg($this->outputFile));
        if ($result === null) {
            $this->error = "Could not $action file $this->sourceFile to $this->outputFile";
            return false;
        }
        return true;
    }

    public function moveAndEncrypt(): bool
    {
        if ($this->error) {
            return false;
        }

        if (!$this->move()) {
            return false;
        }

        return $this->encrypt() !== false;
    }

    public function encrypt(): false|string
    {
        return $this->cryptOperation('encrypt');
    }

    public function decrypt(): false|string
    {
        return $this->cryptOperation('decrypt');
    }

    private function cryptOperation(string $operation): false|string
    {
        if ($this->error || !$this->secret) {
            $this->error = $this->error ?? "Missing secret, secret is not set";
            return false;
        }

        $fileContents = $this->readFileContents();
        if (!$fileContents) {
            return false;
        }

        $method = $operation . 'String';
        $fileContents = CryptHelper::$method($fileContents, $this->secret);
        if (!$fileContents) {
            $this->error = "Cannot $operation file contents: " . $this->sourceFile;
            return false;
        }

        return $this->writeFileContents($fileContents) ? $this->outputFile : false;
    }

    public function deleteSourceFile(): bool
    {
        return $this->deleteFile($this->sourceFile);
    }

    public function deleteDestinationFile(): bool
    {
        return $this->deleteFile($this->outputFile);
    }

    private function deleteFile(string $file): bool
    {
        if ($this->error) {
            return false;
        }

        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    public function isDestinationDirectoryExists(): bool
    {
        return is_dir(dirname($this->outputFile));
    }

    public function toBase64File(): false|string
    {
        return $this->base64Operation('encode');
    }

    public function fromBase64ToOrigin(): false|string
    {
        return $this->base64Operation('decode');
    }

    private function base64Operation(string $operation): false|string
    {
        if ($this->error) {
            return false;
        }
        $fileContents = $this->readFileContents();
        if (!$fileContents) {
            return false;
        }
        $method = 'base64_' . $operation;

        if (!function_exists($method)) {
            return false;
        }
        $fileContents = $method($fileContents);
        return $this->writeFileContents($fileContents) ? $this->outputFile : false;
    }

    public function getFileSize(string $filePath): string
    {
        $size = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public function readFileContents(): false|string
    {
        if ($this->error) {
            return false;
        }

        $fileContents = file_get_contents($this->sourceFile);
        if ($fileContents === false) {
            $this->error = "Failed to read file $this->sourceFile";
            return false;
        }
        return $fileContents;
    }

    private function writeFileContents(string $contents): bool
    {
        if ($this->error) {
            return false;
        }

        if (file_put_contents($this->outputFile, $contents) === false) {
            $this->error = "Failed to write file to destination $this->outputFile";
            return false;
        }
        return true;
    }

    public function convertToWebP(int $quality = 80): bool
    {
        if ($this->error) {
            return false;
        }

        if (!extension_loaded('gd')) {
            $this->error = "GD extension is not loaded";
            return false;
        }

        $info = getimagesize($this->sourceFile);
        if ($info === false) {
            $this->error = "Unable to get image info";
            return false;
        }

        $image = $this->createImageFromFile($info[2]);
        if (!$image) {
            return false;
        }

        $this->outputFile = $this->changeExtension($this->outputFile, 'webp');

        if (!imagewebp($image, $this->outputFile, $quality)) {
            $this->error = "Failed to create WebP image";
            imagedestroy($image);
            return false;
        }

        imagedestroy($image);
        return true;
    }

    public function setJpegQuality(int $quality = 75): bool
    {
        return $this->setImageQuality('jpeg', $quality);
    }

    public function setPngQuality(int $quality = 9): bool
    {
        return $this->setImageQuality('png', $quality);
    }

    private function setImageQuality(string $type, int $quality): bool
    {
        if ($this->error) {
            return false;
        }

        if (!extension_loaded('gd')) {
            $this->error = "GD extension is not loaded";
            return false;
        }

        $info = getimagesize($this->sourceFile);
        if ($info === false) {
            $this->error = "Unable to get image info";
            return false;
        }

        $image = $this->createImageFromFile($info[2]);
        if (!$image) {
            return false;
        }

        $function = "image$type";
        if (!function_exists($function)) {
            $this->error = "Function $function does not exist";
            imagedestroy($image);
            return false;
        }

        if (!$function($image, $this->outputFile, $quality)) {
            $this->error = "Failed to create $type image";
            imagedestroy($image);
            return false;
        }

        imagedestroy($image);
        return true;
    }

    private function createImageFromFile(int $type): false|GdImage
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($this->sourceFile);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($this->sourceFile);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($this->sourceFile);
            default:
                $this->error = "Unsupported image type";
                return false;
        }
    }

    private function changeExtension(string $filename, string $newExtension): string
    {
        $info = pathinfo($filename);
        $dirname = $info['dirname'] ?? '.';
        return $dirname . DIRECTORY_SEPARATOR . $info['filename'] . '.' . $newExtension;
    }
}
