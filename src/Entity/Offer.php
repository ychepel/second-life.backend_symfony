<?php

namespace App\Entity;

use App\Entity\Interface\EntityWithImage;
use App\Enum\OfferStatus;
use App\Repository\OfferRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
#[ORM\Table(name: 'offer')]
class Offer implements EntityWithImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'integer')]
    private ?int $auctionDurationDays = null;

    #[ORM\Column(type: 'decimal', precision: 7, scale: 2, nullable: true)]
    private ?float $startPrice = null;

    #[ORM\Column(type: 'decimal', precision: 7, scale: 2, nullable: true)]
    private ?float $winBid = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isFree = false;

    #[ORM\Column(type: "offer_status_enum", nullable: false)]
    private OfferStatus $status = OfferStatus::DRAFT;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Bid::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Bid $winnerBid = null;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Location $location = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $auctionFinishedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    private array $images = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getAuctionDurationDays(): ?int
    {
        return $this->auctionDurationDays;
    }

    public function setAuctionDurationDays(int $auctionDurationDays): self
    {
        $this->auctionDurationDays = $auctionDurationDays;
        return $this;
    }

    public function getStartPrice(): ?float
    {
        return $this->startPrice;
    }

    public function setStartPrice(?float $startPrice): self
    {
        $this->startPrice = $startPrice;
        return $this;
    }

    public function getWinBid(): ?float
    {
        return $this->winBid;
    }

    public function setWinBid(?float $winBid): self
    {
        $this->winBid = $winBid;
        return $this;
    }

    public function isFree(): bool
    {
        return $this->isFree;
    }

    public function setIsFree(bool $isFree): self
    {
        $this->isFree = $isFree;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getWinnerBid(): ?Bid
    {
        return $this->winnerBid;
    }

    public function setWinnerBid(?Bid $winnerBid): self
    {
        $this->winnerBid = $winnerBid;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getAuctionFinishedAt(): ?\DateTime
    {
        return $this->auctionFinishedAt;
    }

    public function setAuctionFinishedAt(?\DateTime $auctionFinishedAt): self
    {
        $this->auctionFinishedAt = $auctionFinishedAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): array
    {
        return $this->images = $images;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTime();

        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
    }
}
