<?php

declare(strict_types=1);

namespace App\Controller;

use App\API\AzureBlobStorageApi;
use App\Entity\Images;
use App\Form\ImageUploadForm;
use App\Service\UploadImageHelper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    private ManagerRegistry $em;

    public function __construct(ManagerRegistry $registry)
    {
        $this->em = $registry;
    }

    #[Route('/', name: 'app_index')]
    public function uploadImage(Request $request, UploadImageHelper $helper, AzureBlobStorageApi $azureBlobStorageApi, SessionInterface $session): ?Response
    {
        $session->set('upload_errors', []);
        $path = $this->getParameter('kernel.project_dir');

        $form = $this->createForm(ImageUploadForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $error = (string) $form->getErrors(true);
                $this->addFlash('error', $error);
                return $this->redirect($this->generateUrl('app_index'));
            }

            $image = $form['image']->getData();
            $offlineMode = $form['offlineMode']->getData();
            $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

            //Save Images function
            $uploadImageRequest = $helper->uploadImage($image, $path, $azureBlobStorageApi, $offlineMode);

            //If any errors
            if ($uploadImageRequest['statusCode'] != 200) {
                $session->set('upload_errors', [$uploadImageRequest['content']]);
            }

            // Return Confirmation to user alongside image details
            return $this->redirect($this->generateUrl('app_get_confirmation', array('imageName' => $imageName)));
        }

        return $this->render('base.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirmation/{imageName}', name: 'app_get_confirmation')]
    public function imageUploadConfirmation(SessionInterface $session, $imageName): ?Response
    {
        // Clear all previous flash messages
        $flashBag = $session->getFlashBag();
        $flashBag->clear();

        //Get Errors - If Any
        $errors = $session->get('upload_errors', []);
        if (empty($errors)) {
            $this->addFlash('success', 'File Was uploaded successfully!');
        } else {
            $this->addFlash('error', $errors[0]);
        }

        $dbImage = $this->em->getRepository(Images::class)->findOneBy(['name' => $imageName]);

        return $this->render('confirmation.html.twig', [
            'dbImage' => $dbImage
        ]);
    }

    #[Route('/created-blobs', name: 'app_get_container_blobs')]
    public function getAllContainerBlobs(AzureBlobStorageApi $azureBlobStorageApi): JsonResponse
    {
        // Get Active blobs (Extra checks)
        $blobs = $azureBlobStorageApi->getCreatedImagesFromContainer();

        return new JsonResponse($blobs);
    }

    #[Route('/delete-blob/{imageName}/{type}', name: 'app_delete_container_blob')]
    public function deleteContainerBlob(AzureBlobStorageApi $azureBlobStorageApi, $imageName, $type): JsonResponse
    {
        // Delete Container Blob (To clear created blob)
        $blobName = $imageName . '.' . $type;
        $deleteBlobRequest = $azureBlobStorageApi->deleteBlobFromContainer($blobName);

        return new JsonResponse($deleteBlobRequest);
    }
}
