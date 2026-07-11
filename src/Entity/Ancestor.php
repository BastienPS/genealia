<?php

namespace App\Entity;

use App\Repository\AncestorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ancêtre déclaré par un client dans son « Espace des descendants ».
 * Appartient à un compte utilisateur (App\Entity\User) ; indépendant des
 * ResearchRequest (qui conservent leur propre snapshot ancêtre).
 */
#[ORM\Entity(repositoryClass: AncestorRepository::class)]
class Ancestor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $client = null;

    #[ORM\Column(length: 10)]
    private ?string $gender = null; // homme | femme

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthPlace = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $birthCountry = null; // code ISO 2 lettres (FR, IT, …)

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deathDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deathPlace = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $deathCountry = null; // code ISO 2 lettres (FR, IT, …)

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $marriageDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marriagePlace = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $marriageCountry = null; // code ISO 2 lettres (FR, IT, …)

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * Libellé lisible du sexe (« Homme » / « Femme ») pour l'affichage Twig.
     */
    public function getGenderLabel(): string
    {
        return $this->gender === 'femme' ? 'Femme' : 'Homme';
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getBirthPlace(): ?string
    {
        return $this->birthPlace;
    }

    public function setBirthPlace(?string $birthPlace): static
    {
        $this->birthPlace = $birthPlace;
        return $this;
    }

    public function getBirthCountry(): ?string
    {
        return $this->birthCountry;
    }

    public function setBirthCountry(?string $birthCountry): static
    {
        $this->birthCountry = $birthCountry;
        return $this;
    }

    public function getDeathDate(): ?\DateTimeImmutable
    {
        return $this->deathDate;
    }

    public function setDeathDate(?\DateTimeImmutable $deathDate): static
    {
        $this->deathDate = $deathDate;
        return $this;
    }

    public function getDeathPlace(): ?string
    {
        return $this->deathPlace;
    }

    public function setDeathPlace(?string $deathPlace): static
    {
        $this->deathPlace = $deathPlace;
        return $this;
    }

    public function getDeathCountry(): ?string
    {
        return $this->deathCountry;
    }

    public function setDeathCountry(?string $deathCountry): static
    {
        $this->deathCountry = $deathCountry;
        return $this;
    }

    public function getMarriageDate(): ?\DateTimeImmutable
    {
        return $this->marriageDate;
    }

    public function setMarriageDate(?\DateTimeImmutable $marriageDate): static
    {
        $this->marriageDate = $marriageDate;
        return $this;
    }

    public function getMarriagePlace(): ?string
    {
        return $this->marriagePlace;
    }

    public function setMarriagePlace(?string $marriagePlace): static
    {
        $this->marriagePlace = $marriagePlace;
        return $this;
    }

    public function getMarriageCountry(): ?string
    {
        return $this->marriageCountry;
    }

    public function setMarriageCountry(?string $marriageCountry): static
    {
        $this->marriageCountry = $marriageCountry;
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
}