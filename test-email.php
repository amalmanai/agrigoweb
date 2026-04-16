<?php
require_once __DIR__ . '/vendor/autoload_runtime.php';

use App\Kernel;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    
    /** @var MailerInterface $mailer */
    $mailer = $kernel->getContainer()->get('mailer.mailer');
    
    $email = (new Email())
        ->from('amalmanai658@gmail.com')
        ->to('amalmanai658@gmail.com')
        ->subject('Test SMTP Agrigo')
        ->text('Si vous recevez cet e-mail, la configuration SMTP est correcte.');

    try {
        $mailer->send($email);
        echo "E-mail de test envoyé avec succès !\n";
    } catch (\Exception $e) {
        echo "Erreur lors de l'envoi : " . $e->getMessage() . "\n";
    }
    
    return 0;
};
