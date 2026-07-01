<?php

namespace App\Controller;

use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
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
        $form = $this->createForm(ResearchRequestType::class, $researchRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($researchRequest);
            $entityManager->flush();

            $this->handleClientDocuments($form->get('documents')->getData(), $researchRequest, $entityManager);
            $entityManager->flush();

            return $this->render('request/success.html.twig');
        }

        return $this->render('request/new.html.twig', [
            'requestForm' => $form->createView(),
        ]);
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