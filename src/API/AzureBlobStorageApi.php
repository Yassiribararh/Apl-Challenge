<?php

declare(strict_types=1);

namespace App\API;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AzureBlobStorageApi
{
    private const CONTAINER_NAME = 'apl-recruitment-images';
    private const BLOB_ENDPOINT = 'https://aplrecruitment.blob.core.windows.net/';
    private const SHARED_ACCESS_SIGNATURE = 'sv=2022-11-02&ss=bf&srt=sco&sp=rwdlactf&se=2024-05-03T23:04:09Z&st=2024-04-26T15:04:09Z&spr=https&sig=3Z%2FPpe8xm08ZUkklVlrr80MfhaPO8O5GKjQ6LaEfymY%3D';

    public function uploadImageToContainer($image): bool|array
    {

        //Check if image exist already
        $check = $this->getCreatedImagesFromContainer();
        foreach ($check as $blobName) {
            if ($blobName == $image->getClientOriginalName()) {
                return [
                    'statusCode' => 500,
                    'content' => 'Image Blob name already exists',
                ];
            }
        }

        $blobName = $image->getClientOriginalName();
        $fileLen = filesize($image->getRealPath());

        // Final endpoint
        $url = self::BLOB_ENDPOINT . self::CONTAINER_NAME . "/" . $blobName . "?" . urldecode(self::SHARED_ACCESS_SIGNATURE);

        try {
            $client = HttpClient::create();
            $response = $client->request('PUT', $url, [
                'body' => fopen($image->getRealPath(), 'r'),
                'headers' => [
                    'Content-Type' => $image->getClientMimeType(),
                    'x-ms-blob-cache-control' => 'max-age=3600',
                    'x-ms-blob-type' => 'BlockBlob',
                    'x-ms-date' => gmdate('D, d M Y H:i:s \G\M\T'),
                    'x-ms-version' => '2019-12-12',
                    'Content-Length' => $fileLen
                ]
            ]);

            //Check if the upload was successful (status code 201 indicates success)
            if ($response->getStatusCode() === 201) {
                return [
                    'statusCode' => 200,
                    'content' => $response->getContent()
                ];
            } else {
                return [
                    'statusCode' => $response->getStatusCode(),
                    'content' => $response->getContent()
                ];
            }

        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            // Handle exceptions
            return [
                'statusCode' => $e->getCode(),
                'content' => $e->getMessage()
            ];
        }
    }


    public function getCreatedImagesFromContainer(): bool|array
    {

        // Final endpoint
        $url = self::BLOB_ENDPOINT . self::CONTAINER_NAME . "?restype=container&comp=list&" . self::SHARED_ACCESS_SIGNATURE;

        try {
            // Initialize HttpClient
            $client = HttpClient::create();

            // Send request to list blobs
            $response = $client->request('GET', $url);
            $content = $response->getContent();
            $xml = simplexml_load_string($content);

            $blobs = [];
            foreach ($xml->Blobs->Blob as $blob) {
                $blobs[] = (string)$blob->Name;
            }

            // Return the list of blob names
            return [
                'statusCode' => $response->getStatusCode(),
                'content' => $blobs
            ];

        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            // Handle exceptions
            return [
                'statusCode' => $e->getCode(),
                'content' => $e->getMessage()
            ];
        }
    }

    //Used To clear uploaded test files
    public function deleteBlobFromContainer($blobName): array
    {
        // Final endpoint
        $url = self::BLOB_ENDPOINT . self::CONTAINER_NAME . "/" . $blobName . "?" . urldecode(self::SHARED_ACCESS_SIGNATURE);

        try {

            $client = HttpClient::create();
            $response = $client->request('DELETE', $url, [
                'headers' => [
                    'x-ms-date' => gmdate('D, d M Y H:i:s \G\M\T'),
                    'x-ms-version' => '2019-12-12',
                ]
            ]);

            // status code 202 indicates success
            if ($response->getStatusCode() === 202) {
                return [
                    'statusCode' => 200,
                    'content' => $response->getContent()
                ];
            } else {
                return [
                    'statusCode' => $response->getStatusCode(),
                    'content' => $response->getContent()
                ];
            }

        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            // Handle exceptions
            return [
                'statusCode' => $e->getCode(),
                'content' => $e->getMessage()
            ];
        }
    }
}