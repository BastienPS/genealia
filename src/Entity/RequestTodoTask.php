<?php

namespace App\Entity;

use App\Repository\RequestTodoTaskRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tâche d'un snapshot de todolist appliqué à une ResearchRequest (phase 2).
 * Copie indépendante d'une TodoTask du modèle : `done` est réinitialisé à false
 * à l'application, et les mutations (Fait/Suppr/réordonner) sont propres à la
 * demande. Miroir de TodoTask, seule la relation parente change.
 */
#[ORM\Entity(repositoryClass: RequestTodoTaskRepository::class)]
class RequestTodoTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private ?string $label = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $done = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RequestTodoList $requestTodoList = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function setDone(bool $done): static
    {
        $this->done = $done;
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

    public function getRequestTodoList(): ?RequestTodoList
    {
        return $this->requestTodoList;
    }

    public function setRequestTodoList(?RequestTodoList $requestTodoList): static
    {
        $this->requestTodoList = $requestTodoList;
        return $this;
    }
}