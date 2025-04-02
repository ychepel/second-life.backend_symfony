<?php

namespace App\Controller;

use App\Entity\RejectionReason;
use App\Repository\RejectionReasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1')]
class RejectionReasonController extends AbstractController
{
    #[Route('/rejection-reasons', name: 'rejection_reasons', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getRejectionReasons(
        RejectionReasonRepository $rejectionReasonRepository
    ): JsonResponse {
        try {
            // Get all rejection reasons
            $reasons = $rejectionReasonRepository->findAll();

            $response = [
                'reasons' => array_map(function (RejectionReason $reason) {
                    return [
                        'id' => $reason->getId(),
                        'name' => $reason->getName()
                    ];
                }, $reasons)
            ];

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
