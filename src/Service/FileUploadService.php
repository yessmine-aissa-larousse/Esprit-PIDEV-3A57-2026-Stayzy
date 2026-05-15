<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private const UPLOAD_DIR = '/uploads/forum';

    public function __construct(
        private string $projectDir,
        private SluggerInterface $slugger,
    ) {
    }

    public function uploadFile(UploadedFile $file): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->projectDir . '/public' . self::UPLOAD_DIR;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $newFilename);

        return self::UPLOAD_DIR . '/' . $newFilename;
    }

    public function deleteFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $filepath = $this->projectDir . '/public' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}
