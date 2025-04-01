<?php

namespace App\Controller;

use App\Entity\RejectionReason;
use App\Repository\RejectionReasonRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                return $this->json([
                    'error' => 'Access token not found'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Decode token and verify role
            $tokenData = $jwtEncoder->decode($accessToken);
            if (!isset($tokenData['role']) || $tokenData['role'] !== 'admin') {
                return $this->json([
                    'error' => 'Access denied'
                ], Response::HTTP_FORBIDDEN);
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

            return $this->json($response);
        } catch (JWTDecodeFailureException $e) {
            return $this->json([
                'error' => 'Invalid token'
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
