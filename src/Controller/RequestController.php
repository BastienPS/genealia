<?php

namespace App\Controller;

use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
use App\Entity\User;
use App\Form\ResearchRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/request')]
class RequestController extends AbstractController
{
    #[Route('/new', name: 'app_request_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = new ResearchRequest();

        // Pre-fill the client snapshot from the authenticated account (editable).
        $user = $this->getUser();
        if ($user instanceof User) {
            $researchRequest->setClientName($user->getDisplayName());
            $researchRequest->setClientEmail($user->getEmail());
        }

        $form = $this->createForm(ResearchRequestType::class, $researchRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Tie the request to the logged-in client (server-side, not form-spoofable).
            if ($user instanceof User) {
                $researchRequest->setClient($user);
            }

            $entityManager->persist($researchRequest);
            $entityManager->flush();

            $this->handleClientDocuments($form->get('documents')->getData(), $researchRequest, $entityManager);
            $entityManager->flush();

            // PRG : redirige (302) après création. Rendu direct (200) = rejeté par
            // Turbo Drive (« Form responses must redirect ») + resoumission sur refresh.
            return $this->redirectToRoute('app_request_success');
        }

        // 422 sur erreur de validation : Turbo Drive réaffiche alors le formulaire
        // (avec les erreurs Symfony) au lieu de bloquer sur une réponse non-redirigée.
        $status = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        return $this->render('request/new.html.twig', [
            'requestForm' => $form->createView(),
        ], new Response('', $status));
    }

    #[Route('/success', name: 'app_request_success')]
    public function success(): Response
    {
        return $this->render('request/success.html.twig');
    }

    /**
     * Déplace les fichiers déposés par le client vers var/uploads/research/{id}/
     * et crée une entité ResearchDocument (category = client) pour chacun.
     *
     * @param UploadedFile[] $documents
     */
    private function handleClientDocuments(array $documents, ResearchRequest $researchRequest, EntityManagerInterface $entityManager): void
    {
        $uploadDir = $this->getUploadDir($researchRequest->getId());

        foreach ($documents as $uploadedFile) {
            $safeName = $this->buildSafeFilename($uploadedFile);
            $uploadedFile->move($uploadDir, $safeName);

            $document = new ResearchDocument();
            $document->setResearchRequest($researchRequest);
            $document->setFileName($uploadedFile->getClientOriginalName());
            $document->setFilePath('research/' . $researchRequest->getId() . '/' . $safeName);
            $document->setCategory('client');

            $entityManager->persist($document);
        }
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
        $original = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $uploadedFile->guessExtension() ?: 'bin';

        return uniqid('doc_', true) . '.' . $extension;
    }
}