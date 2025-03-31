<?php

namespace App\Controller;

use App\Entity\RejectionReason;
use App\Repository\RejectionReasonRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class RejectionReasonController extends AbstractController
{
    #[Route('/rejection-reasons', name: 'rejection_reasons', methods: ['GET'])]
    public function getRejectionReasons(
        Request $request,
        RejectionReasonRepository $rejectionReasonRepository,
        JWTEncoderInterface $jwtEncoder
    ): JsonResponse {
        try {
            // Get access token from cookies
            $accessToken = $request->cookies->get('access_token');
            if (!$accessToken) {
                return new JsonResponse([
                    'error' => 'Access token not found'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // Decode token and verify role
            $tokenData = $jwtEncoder->decode($accessToken);
            if (!isset($tokenData['role']) || $tokenData['role'] !== 'admin') {
                return new JsonResponse([
                    'error' => 'Access denied'
                ], JsonResponse::HTTP_FORBIDDEN);
            }

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

            return new JsonResponse($response);
        } catch (JWTDecodeFailureException $e) {
            return new JsonResponse([
                'error' => 'Invalid token'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
