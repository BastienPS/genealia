<?php

namespace App\Controller;

use App\Entity\TodoList;
use App\Entity\TodoTask;
use App\Repository\TodoListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Configurateur de todolists administratives (modèles réutilisables).
 * Toutes les routes sont sous /admin, donc protégées par ROLE_ADMIN via
 * l'access_control `^/admin` de security.yaml.
 *
 * Les mutations de tâches (ajout, toggle fait, suppression, réordonnancement)
 * sont consommées en AJAX par le contrôleur Stimulus `todolist` ; elles
 * renvoient du JSON ou un fragment HTML plutôt qu'une redirection PRG.
 */
#[Route('/admin/todolists')]
class TodoListController extends AbstractController
{
    #[Route('', name: 'app_admin_todolists')]
    public function index(TodoListRepository $todoListRepository): Response
    {
        return $this->render('admin/todolists.html.twig', [
            'todo_lists' => $todoListRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_admin_todolist_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $name = trim((string) $request->request->get('name'));
        if ($name !== '') {
            $list = new TodoList();
            $list->setName($name);
            $entityManager->persist($list);
            $entityManager->flush();
            $this->addFlash('success', 'Todolist créée.');
            return $this->redirectToRoute('app_admin_todolist_show', ['id' => $list->getId()]);
        }

        $this->addFlash('error', 'Le nom de la todolist est requis.');
        return $this->redirectToRoute('app_admin_todolists');
    }

    #[Route('/{id}', name: 'app_admin_todolist_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $list = $entityManager->getRepository(TodoList::class)->find($id);
        if ($list === null) {
            throw $this->createNotFoundException('Todolist introuvable');
        }

        return $this->render('admin/todolist_show.html.twig', ['list' => $list]);
    }

    #[Route('/{id}/rename', name: 'app_admin_todolist_rename', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rename(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $list = $entityManager->getRepository(TodoList::class)->find($id);
        if ($list === null) {
            throw $this->createNotFoundException('Todolist introuvable');
        }

        if ($this->isCsrfTokenValid('rename' . $list->getId(), (string) $request->request->get('_token'))) {
            $name = trim((string) $request->request->get('name'));
            if ($name !== '') {
                $list->setName($name);
                $entityManager->flush();
                $this->addFlash('success', 'Todolist renommée.');
            }
        }

        return $this->redirectToRoute('app_admin_todolist_show', ['id' => $list->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_admin_todolist_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $list = $entityManager->getRepository(TodoList::class)->find($id);
        if ($list === null) {
            throw $this->createNotFoundException('Todolist introuvable');
        }

        if ($this->isCsrfTokenValid('delete' . $list->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($list); // orphanRemoval cascade-supprime les tâches
            $entityManager->flush();
            $this->addFlash('success', 'Todolist supprimée.');
        }

        return $this->redirectToRoute('app_admin_todolists');
    }

    /**
     * Ajoute une tâche à une liste. Renvoie le fragment HTML de la ligne
     * (consommé en AJAX par le contrôleur Stimulus) ou une 400 si le label
     * est vide. Le CSRF se valide côté client (double-submit via _token).
     */
    #[Route('/{id}/tasks', name: 'app_admin_todotask_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function newTask(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $list = $entityManager->getRepository(TodoList::class)->find($id);
        if ($list === null) {
            throw $this->createNotFoundException('Todolist introuvable');
        }

        if (!$this->isCsrfTokenValid('todotask-new' . $list->getId(), (string) $request->request->get('_token'))) {
            return new Response('CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        $label = trim((string) $request->request->get('label'));
        if ($label === '') {
            return new Response('Label requis', Response::HTTP_BAD_REQUEST);
        }

        $task = new TodoTask();
        $task->setLabel($label);
        $task->setPosition($list->getTasks()->count());
        $task->setTodoList($list);
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->render('admin/_todo_task_row.html.twig', [
            'task' => $task,
            'list' => $list,
        ], new Response('', Response::HTTP_OK));
    }

    /**
     * Bascule l'état « fait » d'une tâche. Renvoie JSON {done: bool}.
     */
    #[Route('/tasks/{id}/toggle', name: 'app_admin_todotask_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleTask(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = $entityManager->getRepository(TodoTask::class)->find($id);
        if ($task === null) {
            throw $this->createNotFoundException('Tâche introuvable');
        }

        if (!$this->isCsrfTokenValid('toggle' . $task->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $task->setDone(!$task->isDone());
        $entityManager->flush();

        return new JsonResponse(['done' => $task->isDone()]);
    }

    /**
     * Supprime une tâche. Renvoie 204 (pas de corps) — le JS retire la ligne.
     */
    #[Route('/tasks/{id}/delete', name: 'app_admin_todotask_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteTask(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(TodoTask::class)->find($id);
        if ($task === null) {
            throw $this->createNotFoundException('Tâche introuvable');
        }

        if (!$this->isCsrfTokenValid('delete' . $task->getId(), (string) $request->request->get('_token'))) {
            return new Response('CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Réordonne les tâches d'une liste. Body : tasks[]=id dans le nouvel
     * ordre. Met à jour `position` (0-based) et renvoie 204.
     */
    #[Route('/{id}/reorder', name: 'app_admin_todolist_reorder', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reorder(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $list = $entityManager->getRepository(TodoList::class)->find($id);
        if ($list === null) {
            throw $this->createNotFoundException('Todolist introuvable');
        }

        if (!$this->isCsrfTokenValid('reorder' . $list->getId(), (string) $request->request->get('_token'))) {
            return new Response('CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        /** @var int[] $order */
        $order = array_filter(array_map('intval', (array) $request->request->all('tasks')), fn ($v) => $v > 0);
        $repo = $entityManager->getRepository(TodoTask::class);

        // Index les tâches de la liste par id pour ne pas recharger une par une.
        $tasks = [];
        foreach ($list->getTasks() as $task) {
            $tasks[$task->getId()] = $task;
        }

        foreach ($order as $position => $taskId) {
            if (isset($tasks[$taskId])) {
                $tasks[$taskId]->setPosition($position);
            }
        }
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}