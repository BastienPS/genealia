<?php

namespace App\Controller;

use App\Entity\ResearchRequest;
use App\Entity\RequestTodoList;
use App\Entity\RequestTodoTask;
use App\Entity\TodoList;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Todolists appliquées (en snapshot) aux demandes clients (phase 2).
 *
 * `apply` copie un modèle TodoList vers un RequestTodoList indépendant lié à la
 * demande (done réinitialisé) ; (ré)appliquer remplace le snapshot courant (une
 * seule todolist par demande). Les mutations de tâches (toggle/delete/reorder)
 * sont consommées en AJAX par le contrôleur Stimulus `todolist` réutilisé —
 * mêmes réponses que TodoListController (JSON / 204) mais avec les préfixes
 * CSRF `rt-*` et les entités snapshot.
 */
#[Route('/admin/requests')]
class RequestTodoController extends AbstractController
{
    /**
     * Applique (ou remplace) la todolist-modèle choisie sur la demande :
     * crée un snapshot RequestTodoList + une copie RequestTodoTask par tâche
     * du modèle (label + position recopiés, done = false). PRG vers la fiche.
     */
    #[Route('/{requestId}/todolist/apply', name: 'app_admin_request_todo_apply', methods: ['POST'])]
    public function apply(int $requestId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($requestId);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande introuvable');
        }

        if (!$this->isCsrfTokenValid('rt-apply' . $researchRequest->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
        }

        $template = $entityManager->getRepository(TodoList::class)->find((int) $request->request->get('todoListId'));
        if ($template === null) {
            $this->addFlash('error', 'Todolist introuvable.');
            return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
        }

        // Remplace le snapshot éventuel (une seule todolist par demande).
        $existing = $researchRequest->getRequestTodoList();
        if ($existing !== null) {
            $entityManager->remove($existing);
            $entityManager->flush();
        }

        $snapshot = new RequestTodoList();
        $snapshot->setRequest($researchRequest);
        $snapshot->setName($template->getName());

        foreach ($template->getTasks() as $task) {
            $copy = new RequestTodoTask();
            $copy->setLabel($task->getLabel());
            $copy->setPosition($task->getPosition());
            $copy->setRequestTodoList($snapshot);
            $entityManager->persist($copy);
        }

        $entityManager->persist($snapshot);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Todolist « %s » appliquée à la demande.', $template->getName()));
        return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
    }

    /**
     * Retire la todolist snapshot de la demande (orphanRemoval cascade-supprime
     * les tâches). PRG vers la fiche.
     */
    #[Route('/{requestId}/todolist/clear', name: 'app_admin_request_todo_clear', methods: ['POST'])]
    public function clear(int $requestId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($requestId);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande introuvable');
        }

        if ($this->isCsrfTokenValid('rt-clear' . $researchRequest->getId(), (string) $request->request->get('_token'))) {
            $snapshot = $researchRequest->getRequestTodoList();
            if ($snapshot !== null) {
                $entityManager->remove($snapshot);
                $entityManager->flush();
                $this->addFlash('success', 'Todolist retirée de la demande.');
            }
        }

        return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
    }

    /**
     * Bascule l'état « fait » d'une tâche snapshot. Renvoie JSON {done: bool}.
     */
    #[Route('/todolist/tasks/{id}/toggle', name: 'app_admin_request_todotask_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleTask(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = $entityManager->getRepository(RequestTodoTask::class)->find($id);
        if ($task === null) {
            throw $this->createNotFoundException('Tâche introuvable');
        }

        if (!$this->isCsrfTokenValid('rt-toggle' . $task->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF invalide'], Response::HTTP_FORBIDDEN);
        }

        $task->setDone(!$task->isDone());
        $entityManager->flush();

        return new JsonResponse(['done' => $task->isDone()]);
    }

    /**
     * Supprime une tâche snapshot. Renvoie 204 — le JS retire la ligne.
     */
    #[Route('/todolist/tasks/{id}/delete', name: 'app_admin_request_todotask_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteTask(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(RequestTodoTask::class)->find($id);
        if ($task === null) {
            throw $this->createNotFoundException('Tâche introuvable');
        }

        if (!$this->isCsrfTokenValid('rt-delete' . $task->getId(), (string) $request->request->get('_token'))) {
            return new Response('CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Réordonne les tâches snapshot d'une liste. Body : tasks[]=id dans le
     * nouvel ordre. Met à jour `position` (0-based) et renvoie 204.
     */
    #[Route('/todolist/{id}/reorder', name: 'app_admin_request_todo_reorder', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reorder(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $list = $entityManager->getRepository(RequestTodoList::class)->find($id);
        if ($list === null) {
            throw $this->createNotFoundException('Todolist introuvable');
        }

        if (!$this->isCsrfTokenValid('rt-reorder' . $list->getId(), (string) $request->request->get('_token'))) {
            return new Response('CSRF invalide', Response::HTTP_FORBIDDEN);
        }

        /** @var int[] $order */
        $order = array_filter(array_map('intval', (array) $request->request->all('tasks')), fn ($v) => $v > 0);

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