<?php

namespace App\Repository;

use App\Dto\ImagesDto;
use App\Entity\Images;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Images>
 *
 * @method Images|null find($id, $lockMode = null, $lockVersion = null)
 * @method Images|null findOneBy(array $criteria, array $orderBy = null)
 * @method Images[]    findAll()
 * @method Images[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Images::class);
    }

    public function saveUploadedImage($image, $uploadsDirectory, $offlineMode): array
    {
        $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $imageExtension = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);

        //Check if Image already exists
        $dbImage = $this->findOneBy(['name' => $imageName]);
        if ($dbImage != null) {
            return [
                'statusCode' => 500,
                'content' => 'Image Name already exists',
            ];
        }

        //Save File in upload directory
        $originalPath = $image->getRealPath();
        $destination = $uploadsDirectory . '/public/images/'. $image->getClientOriginalName();
        if (!move_uploaded_file($originalPath, $destination)) {
            return [
                'statusCode' => 500,
                'content' => 'Error Saving file',
            ];
        }

        //Persist row to DB if all checks are gone through
        $this->persistUploadedImageToDB($imageName, $destination, $imageExtension, $offlineMode);

        return [
            'statusCode' => 200,
            'content' => 'File Was Uploaded Successfully',
        ];
    }

    public function persistUploadedImageToDB($name, $path, $type, $offlineMode): bool
    {
        //Check if Image already exists
        $uploadedImageDto = new ImagesDto(
            $name,
            $path,
            $type,
            $offlineMode
        );
        $uploadedImageEntity = Images::createFromDto($uploadedImageDto);
        $this->getEntityManager()->persist($uploadedImageEntity);

        //Flush DB
        $this->getEntityManager()->flush();

        return true;
    }
}
