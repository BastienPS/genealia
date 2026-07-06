<?php

namespace App\Controller;

use App\Form\ContactType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page de contact publique : le visiteur rédige un message via le formulaire,
 * l'app construit un e-mail envoyé à l'éditeur (admin@genealia.fr) avec
 * l'adresse du visiteur en Reply-To. Le From est l'adresse autorisée par le
 * SMTP IONOS (admin@genealia.fr) — on ne peut pas envoyer « depuis » l'adresse
 * du visiteur, d'où le Reply-To.
 */
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getOrigin()?->getName().' : '.$error->getMessage();
                }
                $logger->error('Contact form invalid', ['errors' => implode(' | ', $errors)]);
            } else {
                $data = $form->getData();
                $name = trim((string) ($data['name'] ?? ''));
                $senderEmail = trim((string) ($data['email'] ?? ''));
                $message = trim((string) ($data['message'] ?? ''));

                $recipient = (string) $this->getParameter('mailer_from');

                $email = (new Email())
                    ->from(new Address($recipient, 'Généalia (formulaire de contact)'))
                    ->to($recipient)
                    ->replyTo(new Address($senderEmail, $name))
                    ->subject('Nouveau message de contact — ' . $name)
                    ->text($this->buildTextBody($name, $senderEmail, $message))
                    ->html($this->buildHtmlBody($name, $senderEmail, $message));

                try {
                    $mailer->send($email);
                    $logger->error('Contact mail envoyé', ['from' => $recipient, 'to' => $recipient, 'reply_to' => $senderEmail]);
                    $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons par e-mail.');
                } catch (TransportExceptionInterface $e) {
                    $logger->error('Contact mail échec', ['exception' => $e->getMessage()]);
                    $this->addFlash('error', "Une erreur technique est survenue lors de l'envoi. Merci de réessayer ou de nous écrire directement à admin@genealia.fr.");
                }

                return $this->redirectToRoute('app_contact');
            }
        }

        return $this->render('contact/index.html.twig', [
            'contact_form' => $form->createView(),
        ]);
    }

    private function buildTextBody(string $name, string $email, string $message): string
    {
        return "Nouveau message reçu depuis le formulaire de contact de genealia.fr.\n\n"
            . "De : {$name} <{$email}>\n"
            . "------------------------------------------------------------\n"
            . "{$message}\n";
    }

    private function buildHtmlBody(string $name, string $email, string $message): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $name = $esc($name);
        $email = $esc($email);
        $message = $esc($message);

        return "<p>Nouveau message reçu depuis le formulaire de contact de genealia.fr.</p>"
            . "<p><strong>De :</strong> {$name} &lt;<a href=\"mailto:{$email}\">{$email}</a>&gt;</p>"
            . "<hr>"
            . "<pre style=\"white-space:pre-wrap;font-family:inherit\">{$message}</pre>";
    }
}