<?php

namespace App\Entity;

use App\Enum\OfferStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'offer_status_history')]
class OfferStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $offerId = null;

    #[ORM\Column(type: 'offer_status_enum', nullable: false)]
    private OfferStatus $status;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(targetEntity: RejectionReason::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?RejectionReason $rejectionReason = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOfferId(): ?int
    {
        return $this->offerId;
    }

    public function setOfferId(int $offerId): self
    {
        $this->offerId = $offerId;

        return $this;
    }

    public function getStatus(): OfferStatus
    {
        return $this->status;
    }

    public function setStatus(OfferStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getRejectionReason(): ?RejectionReason
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?RejectionReason $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    #[ORM\PrePersist]
    public function updateTimestamps(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }
}
