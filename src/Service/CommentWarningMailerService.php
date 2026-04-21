<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Produit;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Envoie un e-mail d'avertissement à l'agriculteur lors de son 2e commentaire (tous produits confondus).
 */
final class CommentWarningMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendSecondCommentWarning(User $user, Produit $produit): void
    {
        $to = trim((string) $user->getEmailUser());
        if ($to === '') {
            return;
        }

        $prenom = $user->getPrenomUser() ?? '';
        $nomProduit = $produit->getNomProduit() ?? '';

        $subject = 'Avertissement — second commentaire sur AgriGo';

        $html = sprintf(
            '<html><body>
                <h1>Avertissement</h1>
                <p>Bonjour %s,</p>
                <p>Vous venez de publier votre <strong>deuxième commentaire</strong> sur la plateforme (produit : <strong>%s</strong>).</p>
                <p>Ceci est un message automatique d\'information. Merci de respecter les règles de courtoisie et le règlement des commentaires.</p>
                <p>Cordialement,<br>L\'équipe AgriGo</p>
            </body></html>',
            htmlspecialchars($prenom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($nomProduit, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        $email = (new Email())
            ->from('amalmanai658@gmail.com')
            ->to($to)
            ->subject($subject)
            ->html($html);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->warning('Envoi e-mail avertissement 2e commentaire échoué: '.$e->getMessage(), [
                'user' => $user->getIdUser(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Avertissement lors d'une 2e (ou plus) tentative de commentaire avec mots interdits.
     */
    public function sendBadWordViolationWarning(User $user): void
    {
        $to = trim((string) $user->getEmailUser());
        if ($to === '') {
            return;
        }

        $prenom = $user->getPrenomUser() ?? '';

        $subject = 'Avertissement — commentaire avec termes non autorisés (AgriGo)';

        $html = sprintf(
            '<html><body>
                <h1>Avertissement</h1>
                <p>Bonjour %s,</p>
                <p>Vous avez à nouveau tenté de publier un commentaire contenant des <strong>termes non autorisés</strong> sur la plateforme AgriGo.</p>
                <p>Cette tentative correspond à un niveau <strong>2/3</strong>.</p>
                <p><strong>Attention :</strong> à la prochaine tentative (3/3), votre compte sera automatiquement bloqué.</p>
                <p>Ce message est envoyé à votre <strong>e-mail personnel</strong> après récidive. Merci de respecter le règlement des commentaires.</p>
                <p>Cordialement,<br>L\'équipe AgriGo</p>
            </body></html>',
            htmlspecialchars($prenom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        $email = (new Email())
            ->from('amalmanai658@gmail.com')
            ->to($to)
            ->subject($subject)
            ->html($html);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->warning('Envoi e-mail avertissement bad words échoué: '.$e->getMessage(), [
                'user' => $user->getIdUser(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Compte bloqué après 3 violations (mots interdits).
     */
    public function sendAccountBlockedDueToBadWords(User $user): void
    {
        $to = trim((string) $user->getEmailUser());
        if ($to === '') {
            return;
        }

        $prenom = $user->getPrenomUser() ?? '';

        $subject = 'Compte bloqué — non-respect des règles de commentaires (AgriGo)';

        $html = sprintf(
            '<html><body>
                <h1>Compte bloqué</h1>
                <p>Bonjour %s,</p>
                <p>Votre compte a été <strong>désactivé</strong> après plusieurs tentatives de publication de commentaires contenant des termes non autorisés.</p>
                <p>Pour toute réclamation, contactez l\'administration de la plateforme.</p>
                <p>Cordialement,<br>L\'équipe AgriGo</p>
            </body></html>',
            htmlspecialchars($prenom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );

        $email = (new Email())
            ->from('amalmanai658@gmail.com')
            ->to($to)
            ->subject($subject)
            ->html($html);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->warning('Envoi e-mail blocage compte bad words échoué: '.$e->getMessage(), [
                'user' => $user->getIdUser(),
                'exception' => $e,
            ]);
        }
    }
}
