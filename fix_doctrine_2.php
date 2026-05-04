<?php
$content = file_get_contents('src/Entity/User.php');
$content = preg_replace('/(#\[ORM\\\\Column.*loginToken.*\n)\s*(private \?string \$loginToken)/m', "$1    #[Ignore]\n    $2", $content);
// Remove setters
$content = preg_replace('/public function setUpdatedAt\(.*?\{.*?\}/s', '', $content);
$content = preg_replace('/public function setResetExpiresAt\(.*?\{.*?\}/s', '', $content);
file_put_contents('src/Entity/User.php', $content);

$content = file_get_contents('src/Entity/Culture.php');
$content = preg_replace('/public function setUpdatedAt\(.*?\{.*?\}/s', '', $content);
file_put_contents('src/Entity/Culture.php', $content);

$content = file_get_contents('src/Entity/AlerteRisque.php');
$content = preg_replace('/public function setDateAlerte\(.*?\{.*?\}/s', '', $content);
file_put_contents('src/Entity/AlerteRisque.php', $content);

$content = file_get_contents('src/Repository/RecolteRepository.php');
$content = str_replace('setMaxResults(100)', 'setMaxResults(50)', $content);
file_put_contents('src/Repository/RecolteRepository.php', $content);

$content = file_get_contents('src/Repository/VenteRepository.php');
$content = str_replace("->orderBy('v.idVente', 'DESC');", "->orderBy('v.idVente', 'DESC')->setMaxResults(50);", $content);
file_put_contents('src/Repository/VenteRepository.php', $content);

$content = file_get_contents('src/Entity/MarketplaceOrder.php');
$content = str_replace("#[ORM\Column]", "#[ORM\Column(nullable: true)]", $content);
file_put_contents('src/Entity/MarketplaceOrder.php', $content);

$content = file_get_contents('src/Entity/SystemeIrrigation.php');
$content = str_replace("#[ORM\Column(type: Types::DATETIME_MUTABLE)]", "#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]", $content);
file_put_contents('src/Entity/SystemeIrrigation.php', $content);

echo "Fixed 2.";
