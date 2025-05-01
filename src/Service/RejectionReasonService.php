<?php

namespace App\Service;

use App\Entity\RejectionReason;
use App\Mapper\RejectionReasonMappingService;
use App\Repository\RejectionReasonRepository;

class RejectionReasonService
{
    public function __construct(
        private readonly RejectionReasonRepository $rejectionReasonRepository,
        private readonly RejectionReasonMappingService $mappingService,
    ) {
    }

    public function getAll(): array
    {
        $reasons = $this->rejectionReasonRepository->findAll();

        return array_map(fn(RejectionReason $rejectionReason) => $this->mappingService->toDto($rejectionReason), $reasons);
    }
}
