<?php

namespace App\Controller;

use App\Entity\ResearchDocument;
use App\Entity\ResearchRequest;
use App\Form\DocumentUploadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/requests', name: 'app_admin_requests')]
    public function listRequests(EntityManagerInterface $entityManager): Response
    {
        $repository = $entityManager->getRepository(ResearchRequest::class);

        return $this->render('admin/requests.html.twig', [
            'requests' => $repository->findBy([], ['createdAt' => 'DESC']),
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

    #[Route('/requests/{id}', name: 'app_admin_request_show')]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $researchRequest = $entityManager->getRepository(ResearchRequest::class)->find($id);

        if (!$researchRequest) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        $uploadForm = $this->createForm(DocumentUploadType::class);

        return $this->render('admin/request_show.html.twig', [
            'request' => $researchRequest,
            'uploadForm' => $uploadForm->createView(),
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