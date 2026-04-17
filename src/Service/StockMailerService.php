<?php

namespace App\Service;

use App\Entity\MouvementStock;
use App\Entity\Produit;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Security;

class StockMailerService
{
    private MailerInterface $mailer;
    private Security $security;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, Security $security, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->security = $security;
        $this->logger = $logger;
    }

    private function getCurrentUserEmail(): ?string
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        
        return $user ? $user->getEmailUser() : null;
    }

    private function getOwnerEmail(Produit $produit): ?string
    {
        $owner = $produit->getOwner();
        return $owner ? $owner->getEmailUser() : null;
    }

    private function getMovementRecipientEmail(MouvementStock $mouvementStock): ?string
    {
        // Requirement: envoyer à l'agriculteur connecté.
        $currentUserEmail = $this->getCurrentUserEmail();
        if ($currentUserEmail) {
            return $currentUserEmail;
        }

        // Fallback si jamais le user courant n'a pas d'email.
        return $mouvementStock->getProduit()?->getOwner()?->getEmailUser();
    }

    public function sendStockMovementNotification(MouvementStock $mouvementStock): void
    {
        $produit = $mouvementStock->getProduit();
        if (!$produit) {
            return; // Pas de produit, pas d'envoi d'email
        }

        $typeMouvement = (string) $mouvementStock->getTypeMouvement();
        if (!preg_match('/^(entree|entrée|sortie)$/i', trim($typeMouvement))) {
            return; // Envoi seulement pour entrée/sortie.
        }

        $destinataire = $this->getMovementRecipientEmail($mouvementStock);
        if (!$destinataire) {
            return; // Aucun destinataire disponible
        }

        $produitName = $produit->getNomProduit();
        $produitId = $produit->getIdProduit();

        $emailMessage = (new Email())
            ->from('amalmanai658@gmail.com')
            ->to($destinataire)
            ->subject(sprintf('Notification de mouvement de stock : %s', $typeMouvement))
            ->html(sprintf(
                "<html><body>
                    <h1>Notification de mouvement de stock</h1>
                    <p>Un mouvement de stock a été enregistré :</p>
                    <ul>
                        <li><strong>Type :</strong> %s</li>
                        <li><strong>Produit :</strong> %s (#%s)</li>
                        <li><strong>Quantité :</strong> %d</li>
                        <li><strong>Date :</strong> %s</li>
                        <li><strong>Motif :</strong> %s</li>
                    </ul>
                </body></html>",
                htmlspecialchars($mouvementStock->getTypeMouvement(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($produitName, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $produitId, ENT_QUOTES, 'UTF-8'),
                $mouvementStock->getQuantite(),
                htmlspecialchars($mouvementStock->getDateMouvement()?->format('Y-m-d') ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($mouvementStock->getMotif(), ENT_QUOTES, 'UTF-8')
            ));

        $this->logger->info('Envoi email mouvement stock', [
            'to' => $destinataire,
            'type_mouvement' => $typeMouvement,
            'produit_id' => $produitId,
        ]);
        $this->mailer->send($emailMessage);
    }

    public function sendStockAlert(Produit $produit): void
    {
        $email = $this->getCurrentUserEmail() ?: $this->getOwnerEmail($produit);
        if (!$email) {
            return; // Ne pas envoyer d'email si le propriétaire n'a pas d'email
        }

        $quantite = $produit->getQuantiteDisponible() ?? 0;
        $niveau = $quantite > 200 ? 'élevé' : 'faible';
        $messageSeuil = $quantite > 200
            ? "Le stock de ce produit dépasse 200 kg."
            : "Le stock de ce produit est descendu sous 20 kg.";

        $emailMessage = (new Email())
            ->from('amalmanai658@gmail.com')
            ->to($email)
            ->subject('Alerte : Stock ' . $niveau . ' pour le produit ' . $produit->getNomProduit())
            ->html(sprintf(
                "<html><body>
                    <h1>Alerte de stock %s</h1>
                    <p>%s</p>
                    <ul>
                        <li><strong>Produit :</strong> %s (#%s)</li>
                        <li><strong>Quantité actuelle :</strong> %d kg</li>
                    </ul>
                    <p>Veuillez vérifier et ajuster si nécessaire.</p>
                </body></html>",
                htmlspecialchars($niveau, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($messageSeuil, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($produit->getNomProduit(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $produit->getIdProduit(), ENT_QUOTES, 'UTF-8'),
                $quantite
            ));

        $this->logger->info('Envoi email alerte stock', [
            'to' => $email,
            'niveau' => $niveau,
            'quantite' => $quantite,
            'produit_id' => $produit->getIdProduit(),
        ]);
        $this->mailer->send($emailMessage);
    }
}
