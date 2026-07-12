<?php

namespace App\Controller;

use App\Entity\Ancestor;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
use App\Entity\User;
use App\Form\AncestorType;
use App\Form\MessageType;
use App\Repository\AncestorRepository;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\ResearchRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/espace')]
class ClientController extends AbstractController
{
    /**
     * Tableau de bord « Espace des descendants » : liste les demandes du client
     * connecté. Lecture seule.
     */
    #[Route('', name: 'app_client_space')]
    public function space(ResearchRequestRepository $repository, MessageRepository $messageRepository): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            // L'admin en mémoire (form_login) n'est pas un App\Entity\User :
            // on le renvoie vers son propre espace plutôt que de provoquer
            // une erreur Doctrine sur findByClient().
            return $this->redirectToRoute('app_admin_requests');
        }

        return $this->render('client/space.html.twig', [
            'requests' => $repository->findByClient($client),
            'messageUnreadCount' => $messageRepository->findUnreadCountForClient($client),
        ]);
    }

    /**
     * Détail d'une demande du client. L'appartenance est garantie par
     * findOneByClientAndId() — une demande d'autrui renvoie null → 404.
     */
    #[Route('/requests/{id}', name: 'app_client_request_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ResearchRequestRepository $repository): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $request = $repository->findOneByClientAndId($client, $id);
        if ($request === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        return $this->render('client/request_show.html.twig', [
            'request' => $request,
        ]);
    }

    /**
     * Téléchargement d'un document appartenant au client. On vérifie que le
     * document est rattaché à une demande du client (pas seulement à la
     * demande indiquée dans l'URL) — protection IDOR côté téléchargement.
     */
    #[Route('/requests/{id}/documents/{documentId}', name: 'app_client_download_document', requirements: ['id' => '\d+', 'documentId' => '\d+'])]
    public function download(int $id, int $documentId, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $document = $entityManager->getRepository(ResearchDocument::class)->find($documentId);

        if ($document === null
            || $document->getResearchRequest()?->getId() !== $id
            || $document->getResearchRequest()?->getClient()?->getId() !== $client->getId()
        ) {
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

    /**
     * Suppression directe par le client de sa propre demande (soft : archivage).
     * Autorisé à tout moment. IDOR via findOneByClientAndId (null → 404, y
     * compris si déjà archivée). CSRF 'client-delete' ~ id. PRG → espace.
     */
    #[Route('/requests/{id}/supprimer', name: 'app_client_request_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestDelete(int $id, Request $request, ResearchRequestRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $researchRequest = $repository->findOneByClientAndId($client, $id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('client-delete' . $researchRequest->getId(), $token)) {
            $researchRequest->setPreviousStatus($researchRequest->getStatus());
            $researchRequest->setStatus('archived');
            $researchRequest->setDeletionRequested(false);
            $entityManager->flush();
            $this->addFlash('success', 'Demande archivée.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide, suppression annulée.');
        }

        return $this->redirectToRoute('app_client_space');
    }

    /**
     * Le client confirme la suppression demandée par l'admin : archive la
     * demande. IDOR + CSRF 'client-confirm-deletion' ~ id. PRG → espace.
     */
    #[Route('/requests/{id}/confirmer-suppression', name: 'app_client_request_confirm_deletion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestConfirmDeletion(int $id, Request $request, ResearchRequestRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $researchRequest = $repository->findOneByClientAndId($client, $id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('client-confirm-deletion' . $researchRequest->getId(), $token)) {
            $researchRequest->setPreviousStatus($researchRequest->getStatus());
            $researchRequest->setStatus('archived');
            $researchRequest->setDeletionRequested(false);
            $entityManager->flush();
            $this->addFlash('success', 'Demande supprimée.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_client_space');
    }

    /**
     * Le client refuse la suppression demandée par l'admin : baisse le
     * drapeau, le statut reste inchangé. IDOR + CSRF 'client-refuse-deletion' ~ id.
     * PRG → détail (la demande reste visible).
     */
    #[Route('/requests/{id}/refuser-suppression', name: 'app_client_request_refuse_deletion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestRefuseDeletion(int $id, Request $request, ResearchRequestRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $researchRequest = $repository->findOneByClientAndId($client, $id);
        if ($researchRequest === null) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('client-refuse-deletion' . $researchRequest->getId(), $token)) {
            $researchRequest->setDeletionRequested(false);
            $entityManager->flush();
            $this->addFlash('success', 'Suppression refusée — votre demande est conservée.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_client_request_show', ['id' => $researchRequest->getId()]);
    }

    /**
     * Messagerie : fil unique entre le descendant et l'admin. La conversation
     * est résolue depuis l'utilisateur connecté (aucun id dans l'URL) — un
     * client ne peut physiquement pas atteindre le fil d'un autre descendant.
     * L'ouverture marque comme lus les messages reçus de l'admin.
     */
    #[Route('/messages', name: 'app_client_messages', methods: ['GET'])]
    public function messages(ConversationRepository $conversationRepository, MessageRepository $messageRepository): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            // L'admin en mémoire n'a pas de fil client : on le renvoie vers
            // son espace, comme pour le tableau de bord.
            return $this->redirectToRoute('app_admin_messages');
        }

        $conversation = $conversationRepository->findOrCreateForClient($client);
        $messageRepository->markAdminToClientRead($conversation);

        return $this->renderMessagesPage($conversation, $messageRepository, $this->createForm(MessageType::class));
    }

    /**
     * Envoi d'un message par le client. PRG : en cas de succès on redirige
     * vers la page de lecture ; en cas de formulaire invalide on réaffiche la
     * page avec le formulaire lié (erreurs affichées).
     */
    #[Route('/messages', name: 'app_client_message_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        ConversationRepository $conversationRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(MessageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conversation = $conversationRepository->findOrCreateForClient($client);
            $conversation->touch();

            $message = new Message();
            $message->setConversation($conversation);
            $message->setIsFromAdmin(false);
            $message->setAuthorLabel($client->getDisplayName());
            $message->setContent((string) $form->get('content')->getData());

            $entityManager->persist($message);
            $entityManager->flush();

            // Le client consulte le fil en répondant : marque lus les messages admin.
            $messageRepository->markAdminToClientRead($conversation);

            $this->addFlash('success', 'Message envoyé.');

            return $this->redirectToRoute('app_client_messages');
        }

        // Formulaire invalide (ex. vide) : on réaffiche la page avec le form lié.
        // 422 (route POST-only) pour que Turbo Drive réaffiche le formulaire avec
        // les erreurs Symfony au lieu de bloquer sur une réponse non-redirigée.
        $conversation = $conversationRepository->findOrCreateForClient($client);

        return $this->renderMessagesPage($conversation, $messageRepository, $form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * « Mes ancêtres » : liste des ancêtres déclarés par le client, triés par
     * nom de famille. Lecture seule ; la création/modification se fait via les
     * routes dédiées ci-dessous.
     */
    #[Route('/ancetres', name: 'app_client_ancestors')]
    public function ancestors(AncestorRepository $ancestorRepository): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            // L'admin en mémoire n'est pas un client : on le renvoie vers son
            // propre espace, comme pour le tableau de bord.
            return $this->redirectToRoute('app_admin_requests');
        }

        return $this->render('client/ancestors.html.twig', [
            'ancestors' => $ancestorRepository->findByClient($client),
        ]);
    }

    /**
     * Création d'un ancêtre par le client. Le rattachement au compte se fait
     * côté serveur (setClient) — l'utilisateur ne peut pas forcer un autre
     * propriétaire. PRG en cas de succès.
     */
    #[Route('/ancetres/nouveau', name: 'app_client_ancestor_new', methods: ['GET', 'POST'])]
    public function ancestorNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $ancestor = new Ancestor();
        $form = $this->createForm(AncestorType::class, $ancestor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ancestor->setClient($client);
            $entityManager->persist($ancestor);
            $entityManager->flush();

            $this->addFlash('success', 'Ancêtre ajouté à votre espace.');

            return $this->redirectToRoute('app_client_ancestors');
        }

        // 422 sur erreur de validation : Turbo Drive réaffiche alors le formulaire
        // (avec les erreurs Symfony) au lieu de bloquer sur une réponse non-redirigée.
        $status = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        return $this->render('client/ancestor_form.html.twig', [
            'form' => $form->createView(),
            'edit_mode' => false,
        ], new Response('', $status));
    }

    /**
     * Modification d'un ancêtre du client. L'appartenance est garantie par
     * findOneByClientAndId() — un ancêtre d'autrui renvoie null → 404 (IDOR).
     */
    #[Route('/ancetres/{id}/modifier', name: 'app_client_ancestor_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function ancestorEdit(int $id, Request $request, AncestorRepository $ancestorRepository, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $ancestor = $ancestorRepository->findOneByClientAndId($client, $id);
        if ($ancestor === null) {
            throw $this->createNotFoundException('Ancêtre non trouvé');
        }

        $form = $this->createForm(AncestorType::class, $ancestor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Ancêtre mis à jour.');

            return $this->redirectToRoute('app_client_ancestors');
        }

        // 422 sur erreur de validation : Turbo Drive réaffiche alors le formulaire
        // (avec les erreurs Symfony) au lieu de bloquer sur une réponse non-redirigée.
        $status = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        return $this->render('client/ancestor_form.html.twig', [
            'form' => $form->createView(),
            'edit_mode' => true,
            'ancestor' => $ancestor,
        ], new Response('', $status));
    }

    /**
     * Suppression d'un ancêtre du client. Vérification CSRF + IDOR ; la
     * route n'accepte que POST.
     */
    #[Route('/ancetres/{id}/supprimer', name: 'app_client_ancestor_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ancestorDelete(int $id, Request $request, AncestorRepository $ancestorRepository, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getClientUser();
        if ($client === null) {
            throw $this->createAccessDeniedException();
        }

        $ancestor = $ancestorRepository->findOneByClientAndId($client, $id);
        if ($ancestor === null) {
            throw $this->createNotFoundException('Ancêtre non trouvé');
        }

        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $ancestor->getId(), $token)) {
            $entityManager->remove($ancestor);
            $entityManager->flush();
            $this->addFlash('success', 'Ancêtre supprimé.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide, suppression annulée.');
        }

        return $this->redirectToRoute('app_client_ancestors');
    }

    /**
     * Construit la vue de la page messagerie côté client (fil + formulaire).
     */
    private function renderMessagesPage(Conversation $conversation, MessageRepository $messageRepository, FormInterface $form, int $status = Response::HTTP_OK): Response
    {
        return $this->render('client/messages.html.twig', [
            'conversation' => $conversation,
            'messages' => $messageRepository->findThread($conversation),
            'form' => $form->createView(),
        ], new Response('', $status));
    }

    /**
     * Renvoie le client connecté s'il s'agit d'un App\Entity\User (compte OAuth),
     * ou null pour l'admin en mémoire (Symfony\Component\Security\Core\User\User).
     */
    private function getClientUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}