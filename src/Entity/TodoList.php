<?php

namespace App\Entity;

use App\Repository\TodoListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Todolist configurable par l'administrateur. Modèle réutilisable destiné,
 * en phase 2, à être appliqué (snapshot) aux ResearchRequest des clients.
 * Les tâches (TodoTask) sont ordonnées par `position`.
 */
#[ORM\Entity(repositoryClass: TodoListRepository::class)]
class TodoList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, TodoTask>
     */
    #[ORM\OneToMany(mappedBy: 'todoList', targetEntity: TodoTask::class, orphanRemoval: true)]
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

    /**
     * @return Collection<int, TodoTask>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(TodoTask $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setTodoList($this);
        }
        return $this;
    }

    public function removeTask(TodoTask $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getTodoList() === $this) {
                $task->setTodoList(null);
            }
        }
        return $this;
    }
}