<?php

namespace App\Controller;

use App\Service\RejectionReasonService;
use Psr\Log\LoggerInterface;
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
        RejectionReasonService $rejectionReasonService,
        LoggerInterface $logger,
    ): JsonResponse {
        try {
            $reasons = $rejectionReasonService->getAll();

            return $this->json(['reasons' => $reasons]);
        } catch (\Exception $e) {
            $logger->error('Error getting rejection reasons: '.$e->getMessage());

            return $this->json([
                'error' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
