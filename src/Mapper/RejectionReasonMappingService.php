<?php

namespace App\Mapper;

use App\Dto\RejectionReasonDto;
use App\Entity\RejectionReason;

class RejectionReasonMappingService extends MappingEntityWithImage
{
    public function toDto(RejectionReason $rejectionReason): RejectionReasonDto
    {
        return new RejectionReasonDto($rejectionReason->getId(), $rejectionReason->getName());
    }
}
