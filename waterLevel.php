<?php
require __DIR__ . "/vendor/autoload.php";

use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

$message = "";
$wl = -1;
try {
    $wl = getWaterLevel();
} catch (Exception $e) {
    $message = "Probleme d'acquisition des données." . $e->getMessage();
}
$date = date('Y/m/d');

if ($wl <> -1) {
    try {
        $host = 'XXX';
        $dbname = 'XXX';
        $username = 'XXX';
        $password = 'XXX';
        $bdd = new PDO ("mysql:host={$host};dbname={$dbname};", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        $message = "Probleme de connexion. " . $e->getMessage();
    }
}

if (empty($message)) {
    try {
        $infosPDO = $bdd->prepare('SELECT * FROM waterLevels WHERE Date = :date');
        $infosPDO->execute([
                'date' => "$date"]
        );
        $infos = $infosPDO->fetchAll();

        if (empty($infos)) {
            $insert = $bdd->prepare('INSERT INTO waterLevels VALUES(:date,:wl)');
            $insert->execute([
                'date' => $date,
                'wl' => $wl
            ]);
        } else {
            $message = "Probleme de saisie . Valeur deja existante.";
        }
    } catch (PDOException $e) {
        $message = "Probleme SQL. " . $e->getMessage();
    }
}

if (!empty($message))
    mail('theo.jeannes@lmlconsulting.fr', 'Erreur WaterLevels', $message);

function getWaterLevel(): int
{
    $client = new Client(HttpClient::create(['verify_peer' => false, 'verify_host' => false]));
    $crawler = $client->request('GET', 'https://www.elwis.de/DE/dynamisch/gewaesserkunde/wasserstaende/index.php?target=2&pegelId=a6ee8177-107b-47dd-bcfd-30960ccc6e9c');
    return $crawler->filter('tr[onmouseout*=td_1] > td[align=right] > b')->first()->text();
}
//print succes sur la page
?>