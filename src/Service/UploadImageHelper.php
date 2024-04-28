<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Images;
use Doctrine\ORM\EntityManagerInterface;

final class UploadImageHelper
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function uploadImage($image, $path, $azureBlobStorageApi, $offlineMode): array
    {
        $tempFilePath = $image->getPathname();

        // Check the max permitted dimensions of the image (1024 x 1024)
        $imageSizeCheck = $this->checkImageSizes($tempFilePath);
        if (!$imageSizeCheck) {
            return [
                'statusCode' => 500,
                'content' => 'Failed to pass max dimensions check, please choose an image with a max dimensions of 1024 x 1024',
            ];
        }

        //Only upload to Azure if not on offline mode
        if (!$offlineMode) {
            //Upload Image blob to Azure container
            $upload = $azureBlobStorageApi->uploadImageToContainer($image);
            if ($upload['statusCode'] != 200) {
                return [
                    'statusCode' => '500',
                    'content' => $upload['content'],
                ];
            }
        }

        // Store Image details in the database and the File in Asset directory for audit purposes
        $request = $this->entityManager->getRepository(Images::class)->saveUploadedImage($image, $path, $offlineMode);

        return [
            'statusCode' => $request['statusCode'],
            'content' => $request['content']
        ];
    }
    public function checkImageSizes($imageFilePath): bool
    {
        list($width, $height) = getimagesize($imageFilePath);

        if ($width > 1024 OR $height > 1024) {
            return false;
        }

        return true;
    }
}
