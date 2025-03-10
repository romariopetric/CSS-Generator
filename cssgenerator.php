<?php

$options = getopt("r:s:i:", ["recursive", "output-image:", "output-style:"]);

$recursive = isset($options['r']) || isset($options['recursive']);
$outputImage = $options['i'] ?? $options['output-image'] ?? 'sprite';
$outputStyle = $options['s'] ?? $options['output-style'] ?? 'styles.css';

if (!str_ends_with($outputImage, '.png')) {
    $outputImage .= '.png';
}
if (!str_ends_with($outputStyle, '.css')) {
    $outputStyle .= '.css';
}

if ($recursive) {
    $dossier = 'assets_folder/img';  
} else {
    $dossier = 'assets_folder'; 
}
if (!is_dir($dossier)) {
    echo "Erreur : Le dossier '$dossier' n'existe pas.\n";
    exit(1);
}
function lireFichiersDansDossier($chemin, $recursive) {
    $fichiers = [];
    $elements = scandir($chemin);

    foreach ($elements as $element) {
        if ($element === '.' || $element === '..') {
            continue;
        }

        $cheminComplet = $chemin . DIRECTORY_SEPARATOR . $element;

        if (is_dir($cheminComplet) && $recursive) {
            $fichiers = array_merge($fichiers, lireFichiersDansDossier($cheminComplet, $recursive));
        } elseif (is_file($cheminComplet)) {
            $fichiers[] = $cheminComplet;
        }
    }

    return $fichiers;
}

$fichiers = lireFichiersDansDossier($dossier, $recursive);

$pngFiles = array_filter($fichiers, function ($fichier) {
    return strtolower(pathinfo($fichier, PATHINFO_EXTENSION)) === 'png';
});

if (empty($pngFiles)) {
    echo "Erreur : Aucun fichier PNG trouvé dans le dossier '$dossier'.\n";
    exit(1);
}

$spriteWidth = 0;
$spriteHeight = 0;
$images = [];
$largeurFixe = 100;

$sprite = imagecreatetruecolor(1, 1); 
imagesavealpha($sprite, true);
$transparent = imagecolorallocatealpha($sprite, 0, 0, 0, 127); 

foreach ($pngFiles as $png) {
    list($width, $height) = getimagesize($png);

    $newHeight = intval(($height / $width) * $largeurFixe);

    $image = imagecreatefrompng($png);
    $resizedImage = imagescale($image, $largeurFixe, $newHeight);

    $spriteWidth += $largeurFixe;
    $spriteHeight = max($spriteHeight, $newHeight);

    imagedestroy($image);
}

$sprite = imagecreatetruecolor($spriteWidth, $spriteHeight); 
imagesavealpha($sprite, true);
imagefill($sprite, 0, 0, $transparent); 

$x = 0;

foreach ($pngFiles as $png) {
    $image = imagecreatefrompng($png);
    list($width, $height) = getimagesize($png);
    $newHeight = intval(($height / $width) * $largeurFixe);
    $resizedImage = imagescale($image, $largeurFixe, $newHeight);

    imagecopy($sprite, $resizedImage, $x, 0, 0, 0, imagesx($resizedImage), imagesy($resizedImage));
    $x += imagesx($resizedImage);

    imagedestroy($resizedImage); 
}

imagepng($sprite, $outputImage);
imagedestroy($sprite);

echo "Le sprite a été créé et sauvegardé sous le nom '$outputImage'.\n";


$x = 0;

foreach ($pngFiles as $png) {
    $cssContent .= ".sprite-" . pathinfo($png, PATHINFO_FILENAME) . " {\n";
    $cssContent .= "  background-image: url('$outputImage');\n";
    $cssContent .= "  background-position: -" . $x . "px 0;\n";
    $cssContent .= "  width: {$largeurFixe}px;\n";

    list($width, $height) = getimagesize($png);
    $newHeight = intval(($height / $width) * $largeurFixe);
    $cssContent .= "  height: {$newHeight}px;\n";
    $cssContent .= "}\n\n";

    $x += $largeurFixe;
}

file_put_contents($outputStyle, $cssContent);


?>













