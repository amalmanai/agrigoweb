<?php

namespace App\Controller;

use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QrConverterController extends AbstractController
{
    #[Route('/convert-qr-to-png', name: 'app_convert_qr_to_png')]
    public function convertQrToPng(BuilderInterface $qrCodeBuilder): Response
    {
        $qrCodeDirectory = 'C:/Users/Amal/AgriGo/user-qrs';
        
        // Liste des fichiers à convertir
        $filesToConvert = [
            'user_82_parfumz196_gmail.com.svg' => ['id' => '82', 'email' => 'parfumz196@gmail.com'],
            'user_80_hhm375220@gmail.com.svg' => ['id' => '80', 'email' => 'hhm375220@gmail.com'],
            'user_78_jlmariem35@gmail.com.svg' => ['id' => '78', 'email' => 'jlmariem35@gmail.com'],
            'user_69_amira@gmail.com.svg' => ['id' => '69', 'email' => 'amira@gmail.com'],
            'user_72_marwa@gmail.com.svg' => ['id' => '72', 'email' => 'marwa@gmail.com'],
            'user_75_aay25753@gmail.com.svg' => ['id' => '75', 'email' => 'aay25753@gmail.com'],
            'user_88_azza@gmail.com.svg' => ['id' => '88', 'email' => 'azza@gmail.com']
        ];

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($filesToConvert as $svgFile => $userData) {
            $oldSvgPath = $qrCodeDirectory . '/' . $svgFile;
            $newPngPath = $qrCodeDirectory . '/' . str_replace('.svg', '.png', $svgFile);

            // Vérifier si le fichier SVG existe
            if (!file_exists($oldSvgPath)) {
                $results[] = "❌ Fichier SVG non trouvé: " . $svgFile;
                $errorCount++;
                continue;
            }

            try {
                // Générer le nouveau QR code en PNG
                $result = $qrCodeBuilder->build(
                    data: 'AGRIGO-USER:' . $userData['id'] . ':' . $userData['email'],
                    size: 300,
                    margin: 10,
                );

                // Sauvegarder en PNG
                $result->saveToFile($newPngPath);
                
                $results[] = "✅ Converti: " . $svgFile . " → " . str_replace('.svg', '.png', $svgFile);
                $successCount++;
            } catch (\Exception $e) {
                $results[] = "❌ Erreur lors de la conversion de " . $svgFile . ": " . $e->getMessage();
                $errorCount++;
            }
        }

        // Afficher le résumé
        $output = "<h2>Conversion des QR Codes SVG vers PNG</h2>";
        $output .= "<p><strong>Résumé:</strong> $successCount succès, $errorCount erreurs</p>";
        $output .= "<h3>Détails:</h3>";
        $output .= "<ul>";
        foreach ($results as $result) {
            $output .= "<li>" . $result . "</li>";
        }
        $output .= "</ul>";
        $output .= "<p><strong>Tous les fichiers PNG sont sauvegardés dans:</strong> $qrCodeDirectory</p>";

        return new Response($output);
    }
}
