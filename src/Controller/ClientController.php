<?php

namespace App\Controller;

use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
use App\Entity\User;
use App\Repository\ResearchRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
    public function space(ResearchRequestRepository $repository): Response
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
     * Renvoie le client connecté s'il s'agit d'un App\Entity\User (compte OAuth),
     * ou null pour l'admin en mémoire (Symfony\Component\Security\Core\User\User).
     */
    private function getClientUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}