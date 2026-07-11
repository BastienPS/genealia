<?php

namespace App\Entity;

use App\Repository\ResearchRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResearchRequestRepository::class)]
class ResearchRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $clientName = null;

    #[ORM\Column(length: 255)]
    private ?string $clientEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $ancestorFirstName = null;

    #[ORM\Column(length: 255)]
    private ?string $ancestorLastName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $estimatedBirthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $estimatedBirthPlace = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $estimatedBirthCountry = null; // code ISO 2 lettres (FR, IT, …)

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $estimatedDeathDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $estimatedDeathPlace = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $estimatedDeathCountry = null; // code ISO 2 lettres (FR, IT, …)

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $researchGoals = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInfo = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending'; // pending, in_progress, completed, cancelled

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $client = null;

    #[ORM\OneToMany(mappedBy: 'researchRequest', targetEntity: ResearchDocument::class, orphanRemoval: true)]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): static
    {
        $this->clientName = $clientName;
        return $this;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(string $clientEmail): static
    {
        $this->clientEmail = $clientEmail;
        return $this;
    }

    public function getAncestorFirstName(): ?string
    {
        return $this->ancestorFirstName;
    }

    public function setAncestorFirstName(string $ancestorFirstName): static
    {
        $this->ancestorFirstName = $ancestorFirstName;
        return $this;
    }

    public function getAncestorLastName(): ?string
    {
        return $this->ancestorLastName;
    }

    public function setAncestorLastName(string $ancestorLastName): static
    {
        $this->ancestorLastName = $ancestorLastName;
        return $this;
    }

    public function getEstimatedBirthDate(): ?string
    {
        return $this->estimatedBirthDate;
    }

    public function setEstimatedBirthDate(?string $estimatedBirthDate): static
    {
        $this->estimatedBirthDate = $estimatedBirthDate;
        return $this;
    }

    public function getEstimatedBirthPlace(): ?string
    {
        return $this->estimatedBirthPlace;
    }

    public function setEstimatedBirthPlace(?string $estimatedBirthPlace): static
    {
        $this->estimatedBirthPlace = $estimatedBirthPlace;
        return $this;
    }

    public function getEstimatedBirthCountry(): ?string
    {
        return $this->estimatedBirthCountry;
    }

    public function setEstimatedBirthCountry(?string $estimatedBirthCountry): static
    {
        $this->estimatedBirthCountry = $estimatedBirthCountry;
        return $this;
    }

    public function getEstimatedDeathDate(): ?string
    {
        return $this->estimatedDeathDate;
    }

    public function setEstimatedDeathDate(?string $estimatedDeathDate): static
    {
        $this->estimatedDeathDate = $estimatedDeathDate;
        return $this;
    }

    public function getEstimatedDeathPlace(): ?string
    {
        return $this->estimatedDeathPlace;
    }

    public function setEstimatedDeathPlace(?string $estimatedDeathPlace): static
    {
        $this->estimatedDeathPlace = $estimatedDeathPlace;
        return $this;
    }

    public function getEstimatedDeathCountry(): ?string
    {
        return $this->estimatedDeathCountry;
    }

    public function setEstimatedDeathCountry(?string $estimatedDeathCountry): static
    {
        $this->estimatedDeathCountry = $estimatedDeathCountry;
        return $this;
    }

    public function getResearchGoals(): ?string
    {
        return $this->researchGoals;
    }

    public function setResearchGoals(?string $researchGoals): static
    {
        $this->researchGoals = $researchGoals;
        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): static
    {
        $this->additionalInfo = $additionalInfo;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, ResearchDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function addDocument(ResearchDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setResearchRequest($this);
        }
        return $this;
    }

    public function removeDocument(ResearchDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already null)
            if ($document->getResearchRequest() === $this) {
                $document->setResearchRequest(null);
            }
        }
        return $this;
    }
}
