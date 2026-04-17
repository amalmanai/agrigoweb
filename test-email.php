<?php
require_once __DIR__ . '/vendor/autoload_runtime.php';

use App\Kernel;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    
    /** @var \Symfony\Component\Mailer\Transport\Transports $transports */
    $transports = $kernel->getContainer()->get('mailer.transports');
    
    $transport = $transports->get('default');
    
    $email = (new Email())
        ->from('amalmanai658@gmail.com')
        ->to('amalmanai658@gmail.com')
        ->subject('Test SMTP Agrigo')
        ->text('Si vous recevez cet e-mail, la configuration SMTP est correcte.');

    try {
        $transport->send($email);
        echo "E-mail de test envoyé avec succès !\n";
    } catch (\Exception $e) {
        echo "Erreur lors de l'envoi : " . $e->getMessage() . "\n";
    }
    
    return 0;
};
