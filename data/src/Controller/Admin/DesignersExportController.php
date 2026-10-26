<?php

/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use ArrayObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[IsGranted(User::ROLE_MANAGER)]
class DesignersExportController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    #[Route('/admin/designers.csv', name: 'admin.designers.export.csv')]
    public function csv(Request $request): Response
    {
        $response = new StreamedResponse(function () {
            $output = fopen('php://output', 'w');

            $dateFormat = 'd.m.Y H:i:s';

            $context = [
                AbstractNormalizer::GROUPS => ['csv'],
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (mixed $object): mixed {
                    return is_object($object) && method_exists($object, 'getId') ? $object->getId() : null;
                },
                DateTimeNormalizer::FORMAT_KEY => $dateFormat,
            ];

            $headers = [];

            /** @var User[] $designers */
            $designers = $this->userRepository->findByRole(User::ROLE_DESIGNER);
            foreach ($designers as $designer) {
                $userData = $this->normalizer->normalize($designer, context: $context);
                $userData = $userData instanceof ArrayObject ? $userData->getArrayCopy() : (array)$userData;

                $profileData = $this->normalizer->normalize($designer->getProfile(), context: $context);
                $profileData = $profileData instanceof ArrayObject ? $profileData->getArrayCopy() : (array)$profileData;

                $profileData = array_combine(
                    array_map(function ($key) {
                        return 'profile.' . $key;
                    }, array_keys($profileData)),
                    $profileData
                );

                $requisitesData = $this->normalizer->normalize($designer->getRequisites(), context: $context);
                $requisitesData = $requisitesData instanceof ArrayObject ? $requisitesData->getArrayCopy() : (array)$requisitesData;

                $requisitesData = array_combine(
                    array_map(function ($key) {
                        return 'requisites.' . $key;
                    }, array_keys($requisitesData)),
                    $requisitesData
                );

                $data = array_merge($userData, $profileData, $requisitesData);

                $data = array_map(function ($value) {
                    if (is_array($value)) {
                        return implode(', ', array_filter($value, 'is_scalar'));
                    } else {
                        return is_scalar($value) ? $value : '';
                    }
                }, $data);

                ob_start();
                if (empty($headers)) {
                    $headers = array_keys($data);
                    fputcsv($output, $headers, ';');
                }

                fputcsv($output, array_combine($headers, $data), ';');
                $content = ob_get_clean();

                echo iconv('UTF-8', 'Windows-1251', $content);
            }
        });

        $response->headers->set('Content-Type', 'text/csv; charset=windows-1251');
        $response->headers->set('Content-Disposition', 'filename="designers.csv"');

        return $response;
    }
}