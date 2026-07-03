<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
use App\Entity\User;
use App\Form\MessageType;
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
        $conversation = $conversationRepository->findOrCreateForClient($client);

        return $this->renderMessagesPage($conversation, $messageRepository, $form);
    }

    /**
     * Construit la vue de la page messagerie côté client (fil + formulaire).
     */
    private function renderMessagesPage(Conversation $conversation, MessageRepository $messageRepository, FormInterface $form): Response
    {
        return $this->render('client/messages.html.twig', [
            'conversation' => $conversation,
            'messages' => $messageRepository->findThread($conversation),
            'form' => $form->createView(),
        ]);
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