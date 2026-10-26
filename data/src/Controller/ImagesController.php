<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use League\Flysystem\FilesystemOperator;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\ServerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ImagesController extends AbstractController
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly FilesystemOperator $publicStorage,
        private readonly array $defaultOptions = [],
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $server = ServerFactory::create([
            'response' => new SymfonyResponseFactory(),
            'source' => $this->publicStorage,
            'cache' => $this->kernel->getCacheDir() . '/images',
            'base_url' => $this->getParameter('images.path'),
        ]);

        try {
            /** @var Response $response */
            $response = $server->getImageResponse(
                (string)$request->attributes->get('path'),
                array_replace($this->defaultOptions, $request->query->all()),
            );

            return $response;
        } catch (FileNotFoundException) {
            throw $this->createNotFoundException();
        }
    }
}