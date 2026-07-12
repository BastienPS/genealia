<?php

namespace App\Entity;

use App\Repository\RequestTodoListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Snapshot de todolist appliqué à une ResearchRequest (phase 2). Une seule par
 * demande (OneToOne unique) ; (ré)appliquer remplace le snapshot courant. Les
 * tâches (RequestTodoTask) sont des copies indépendantes du modèle TodoList,
 * ordonnées par `position`. Le `name` est figé à l'instant de l'application.
 */
#[ORM\Entity(repositoryClass: RequestTodoListRepository::class)]
class RequestTodoList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(inversedBy: 'requestTodoList')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?ResearchRequest $request = null;

    /**
     * @var Collection<int, RequestTodoTask>
     */
    #[ORM\OneToMany(mappedBy: 'requestTodoList', targetEntity: RequestTodoTask::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getRequest(): ?ResearchRequest
    {
        return $this->request;
    }

    public function setRequest(?ResearchRequest $request): static
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Collection<int, RequestTodoTask>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(RequestTodoTask $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setRequestTodoList($this);
        }
        return $this;
    }

    public function removeTask(RequestTodoTask $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getRequestTodoList() === $this) {
                $task->setRequestTodoList(null);
            }
        }
        return $this;
    }
}