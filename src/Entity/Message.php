<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un message d'une conversation admin ↔ client.
 *
 * `isFromAdmin` indique le côté émetteur : true = l'admin a écrit,
 * false = le client a écrit. Cela détermine l'alignement de la bulle dans
 * le fil et qui a des « non-lus » :
 *  - non-lus côté client  = isFromAdmin=true  AND readAt IS NULL
 *  - non-lus côté admin   = isFromAdmin=false AND readAt IS NULL (toutes conversations)
 *
 * `authorLabel` est un instantané affiché (« Généalia » pour l'admin,
 * displayName du client) : l'admin en mémoire n'est pas une entité Doctrine,
 * on stocke donc son libellé au moment de l'envoi.
 */
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\Column]
    private bool $isFromAdmin = false;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $authorLabel = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function isFromAdmin(): bool
    {
        return $this->isFromAdmin;
    }

    public function setIsFromAdmin(bool $isFromAdmin): static
    {
        $this->isFromAdmin = $isFromAdmin;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getAuthorLabel(): ?string
    {
        return $this->authorLabel;
    }

    public function setAuthorLabel(?string $authorLabel): static
    {
        $this->authorLabel = $authorLabel;
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

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    /**
     * Marque le message comme lu par son destinataire.
     */
    public function markRead(): static
    {
        if ($this->readAt === null) {
            $this->readAt = new \DateTimeImmutable();
        }
        return $this;
    }
}