<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
use App\Entity\TodoList;
use App\Entity\User;
use App\Form\DocumentUploadType;
use App\Form\MessageType;
use App\Repository\AncestorRepository;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/requests', name: 'app_admin_requests')]
    public function listRequests(Request $request, EntityManagerInterface $entityManager): Response
    {
        $archived = $request->query->getBoolean('archived');
        $repository = $entityManager->getRepository(ResearchRequest::class);

        return $this->render('admin/requests.html.twig', [
            'requests' => $repository->findAllForAdmin($archived),
            'archived' => $archived,
        ]);
    }

    #[Route('/clients', name: 'app_admin_clients')]
    public function listClients(UserRepository $userRepository): Response
    {
        return $this->render('admin/clients.html.twig', [
            'clients' => $userRepository->findClients(),
        ]);
    }

    /**
     * Ancêtres créés par un client donné. 404 si le client n'existe pas.
     * Vue lecture seule côté admin (le CRUD reste côté client dans /espace).
     */
    #[Route('/clients/{clientId}/ancetres', name: 'app_admin_client_ancestors', requirements: ['clientId' => '\d+'], methods: ['GET'])]
    public function clientAncestors(int $clientId, EntityManagerInterface $entityManager, AncestorRepository $ancestorRepository): Response
    {
        $client = $entityManager->getRepository(User::class)->find($clientId);
        if ($client === null) {
            throw $this->createNotFoundException('Client introuvable');
        }

        return $this->render('admin/client_ancestors.html.twig', [
            'client' => $client,
            'ancestors' => $ancestorRepository->findByClient($client),
        ]);
    }

    #[Route('/requests/{id}/update-status', name: 'app_admin_request_update_status', methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $repository = $entityManager->getRepository(ResearchRequest::class);
        $researchRequest = $repository->find($id);

        if (!$researchRequest) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $newStatus = $request->request->get('status');
        if (in_array($newStatus, ['pending', 'in_progress', 'completed', 'cancelled'])) {
            $researchRequest->setStatus($newStatus);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_requests');
    }

    /**
     * L'admin demande la suppression d'une demande client-linked : lève le
     * drapeau deletion_requested (le statut reste inchangé), le client verra
     * une bannière dans son espace et confirmera/refusera. CSRF 'admin-request-deletion' ~ id. PRG → show.
     */
    #[Route('/requests/{id}/demander-suppression', name: 'app_admin_request_request_deletion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestDeletion(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('admin-request-deletion' . $researchRequest->getId(), $token)) {
            $researchRequest->setDeletionRequested(true);
            $entityManager->flush();
            $this->addFlash('success', 'Suppression demandée au client. En attente de confirmation.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
    }

    /**
     * L'admin annule sa demande de suppression avant action du client :
     * baisse le drapeau. CSRF 'admin-cancel-deletion' ~ id. PRG → show.
     */
    #[Route('/requests/{id}/annuler-demande-suppression', name: 'app_admin_request_cancel_deletion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancelDeletion(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('admin-cancel-deletion' . $researchRequest->getId(), $token)) {
            $researchRequest->setDeletionRequested(false);
            $entityManager->flush();
            $this->addFlash('success', 'Demande de suppression annulée.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
    }

    /**
     * Archivage direct par l'admin (soft) — principalement pour les demandes
     * orphelines (client = null, personne à qui demander confirmation).
     * Mémorise le statut courant dans previousStatus pour restauration.
     * CSRF 'admin-archive' ~ id. PRG → show.
     */
    #[Route('/requests/{id}/archiver', name: 'app_admin_request_archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archive(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('admin-archive' . $researchRequest->getId(), $token)) {
            $researchRequest->setPreviousStatus($researchRequest->getStatus());
            $researchRequest->setStatus('archived');
            $researchRequest->setDeletionRequested(false);
            $entityManager->flush();
            $this->addFlash('success', 'Demande archivée.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
    }

    /**
     * Restaure une demande archivée : remet le statut mémorisé (ou 'pending'
     * par défaut) et vide previousStatus. CSRF 'admin-restore' ~ id. PRG → show.
     */
    #[Route('/requests/{id}/restaurer', name: 'app_admin_request_restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restore(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('admin-restore' . $researchRequest->getId(), $token)) {
            $researchRequest->setStatus($researchRequest->getPreviousStatus() ?? 'pending');
            $researchRequest->setPreviousStatus(null);
            $entityManager->flush();
            $this->addFlash('success', 'Demande restaurée.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()]);
    }

    #[Route('/requests/{id}', name: 'app_admin_request_show')]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);

        if (!$researchRequest) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $uploadForm = $this->createForm(DocumentUploadType::class);

        // Todolists-modèles disponibles pour application (sidebar « Todolist »).
        $todoLists = $entityManager->getRepository(TodoList::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/request_show.html.twig', [
            'request' => $researchRequest,
            'uploadForm' => $uploadForm->createView(),
            'todo_lists' => $todoLists,
        ]);
    }

    #[Route('/requests/{id}/documents', name: 'app_admin_request_upload_document', methods: ['POST'])]
    public function uploadDocument(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);

        if (!$researchRequest) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $form = $this->createForm(DocumentUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            $uploadDir = $this->getUploadDir($researchRequest->getId());
            $safeName = $this->buildSafeFilename($uploadedFile);
            $uploadedFile->move($uploadDir, $safeName);

            $document = new ResearchDocument();
            $document->setResearchRequest($researchRequest);
            $document->setFileName($uploadedFile->getClientOriginalName());
            $document->setFilePath('research/' . $researchRequest->getId() . '/' . $safeName);
            $document->setDescription($form->get('description')->getData());
            $document->setCategory('research');

            $entityManager->persist($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document ajouté.');
        }

        return $this->redirectToRoute('app_admin_request_show', ['id' => $id]);
    }

    #[Route('/requests/{id}/documents/{documentId}', name: 'app_admin_request_download_document')]
    public function downloadDocument(int $id, int $documentId, EntityManagerInterface $entityManager): Response
    {
        $document = $entityManager->getRepository(ResearchDocument::class)->find($documentId);

        if (!$document || $document->getResearchRequest()?->getId() !== $id) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        $absolutePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $document->getFilePath();

        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Fichier introuvable sur le disque.');
        }

        return new BinaryFileResponse($absolutePath, headers: [
            'Content-Disposition' => 'inline; filename="' . $document->getFileName() . '"',
        ]);
    }

    #[Route('/documents/{id}/delete', name: 'app_admin_document_delete', methods: ['POST'])]
    public function deleteDocument(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $document = $entityManager->getRepository(ResearchDocument::class)->find($id);

        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        $researchRequest = $document->getResearchRequest();

        if ($this->isCsrfTokenValid('delete' . $document->getId(), (string) $request->request->get('_token'))) {
            $absolutePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $document->getFilePath();
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }

            $entityManager->remove($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprimé.');
        }

        return $researchRequest
            ? $this->redirectToRoute('app_admin_request_show', ['id' => $researchRequest->getId()])
            : $this->redirectToRoute('app_admin_requests');
    }

    /**
     * Liste des conversations de messagerie (une par descendant), triées par
     * dernière activité. Affiche un aperçu du dernier message et un badge de
     * messages non lus par conversation.
     */
    #[Route('/messages', name: 'app_admin_messages', methods: ['GET'])]
    public function listMessages(
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository
    ): Response {
        $conversations = $conversationRepository->findAllOrderByLastActivity();
        $unreadIds = $messageRepository->findUnreadConversationIdsForAdmin();

        $lastMessages = [];
        foreach ($conversations as $conversation) {
            $last = $messageRepository->findLastForConversation($conversation);
            if ($last !== null) {
                $lastMessages[$conversation->getId()] = $last;
            }
        }

        return $this->render('admin/messages.html.twig', [
            'conversations' => $conversations,
            'lastMessages' => $lastMessages,
            'unreadIds' => $unreadIds,
        ]);
    }

    /**
     * Ouvre une conversation : marque comme lus les messages du client, puis
     * affiche le fil + le formulaire de réponse admin.
     */
    #[Route('/messages/{id}', name: 'app_admin_message_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showMessage(int $id, ConversationRepository $conversationRepository, MessageRepository $messageRepository): Response
    {
        $conversation = $conversationRepository->find($id);
        if ($conversation === null) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        $messageRepository->markClientToAdminRead($conversation);

        return $this->renderMessageShowPage($conversation, $messageRepository, $this->createForm(MessageType::class, null, ['label' => 'Répondre au descendant']));
    }

    /**
     * Réponse de l'admin dans une conversation. PRG sur succès ; réaffichage
     * avec le form lié si invalide.
     */
    #[Route('/messages/{id}', name: 'app_admin_message_reply', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function replyMessage(
        int $id,
        Request $request,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $conversation = $conversationRepository->find($id);
        if ($conversation === null) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        $form = $this->createForm(MessageType::class, null, ['label' => 'Répondre au descendant']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conversation->touch();

            $message = new Message();
            $message->setConversation($conversation);
            $message->setIsFromAdmin(true);
            $message->setAuthorLabel('Généalia');
            $message->setContent((string) $form->get('content')->getData());

            $entityManager->persist($message);
            $entityManager->flush();

            // L'admin consulte le fil en répondant : marque lus les messages client.
            $messageRepository->markClientToAdminRead($conversation);

            // Notification e-mail au client (non-fatale : un échec SMTP ne doit
            // pas empêcher l'envoi du message dans le fil).
            $mailSent = $this->notifyClientOfAdminMessage($conversation, $message, $mailer);
            $this->addFlash(
                $mailSent ? 'success' : 'warning',
                $mailSent
                    ? 'Message envoyé au descendant.'
                    : 'Message enregistré, mais la notification e-mail n\'a pas pu être envoyée.'
            );

            return $this->redirectToRoute('app_admin_message_show', ['id' => $conversation->getId()]);
        }

        // 422 (route POST-only) : Turbo Drive réaffiche le formulaire avec les
        // erreurs Symfony au lieu de bloquer sur une réponse non-redirigée.
        return $this->renderMessageShowPage($conversation, $messageRepository, $form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Envoie au client un courriel le prévenant qu'un message admin l'attend
     * dans son fil. Retourne false si l'envoi échoue (transport indisponible,
     * adresse invalide…) — l'appelant garde le comportement non-fatal.
     */
    private function notifyClientOfAdminMessage(Conversation $conversation, Message $message, MailerInterface $mailer): bool
    {
        $client = $conversation->getClient();
        if ($client === null || $client->getEmail() === null || $client->getEmail() === '') {
            return false;
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->getParameter('mailer_from'), 'Généalia'))
            ->to($client->getEmail())
            ->subject('Vous avez reçu un message de votre généalogiste')
            ->htmlTemplate('email/admin_message_notification.html.twig')
            ->textTemplate('email/admin_message_notification.txt.twig')
            ->context([
                'client_name' => $client->getDisplayName() ?: $client->getEmail(),
                'message_content' => $message->getContent(),
                'thread_url' => $this->generateUrl('app_client_messages', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }

    /**
     * Point d'entrée pour démarrer/ouvrir une conversation depuis un dossier :
     * résout le client lié à la demande, crée la conversation si nécessaire,
     * puis redirige vers la page de conversation. 404 si le client n'existe pas
     * (une demande anonyme sans compte client ne peut pas être messagée).
     */
    #[Route('/messages/for-client/{clientId}', name: 'app_admin_message_for_client', requirements: ['clientId' => '\d+'], methods: ['GET'])]
    public function messageForClient(int $clientId, EntityManagerInterface $entityManager, ConversationRepository $conversationRepository): Response
    {
        $client = $entityManager->getRepository(User::class)->find($clientId);
        if ($client === null) {
            throw $this->createNotFoundException('Client introuvable');
        }

        $conversation = $conversationRepository->findOrCreateForClient($client);

        return $this->redirectToRoute('app_admin_message_show', ['id' => $conversation->getId()]);
    }

    /**
     * Construit la vue de la page de conversation côté admin (fil + formulaire).
     */
    private function renderMessageShowPage(Conversation $conversation, MessageRepository $messageRepository, FormInterface $form, int $status = Response::HTTP_OK): Response
    {
        return $this->render('admin/message_show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messageRepository->findThread($conversation),
            'form' => $form->createView(),
        ], new Response('', $status));
    }

    private function getUploadDir(int $requestId): string
    {
        $dir = $this->getParameter('kernel.project_dir') . '/var/uploads/research/' . $requestId;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function buildSafeFilename(UploadedFile $uploadedFile): string
    {
        $extension = $uploadedFile->guessExtension() ?: 'bin';

        return uniqid('doc_', true) . '.' . $extension;
    }
}