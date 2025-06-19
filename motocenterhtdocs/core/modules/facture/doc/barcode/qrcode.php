

<?php

include "vendor/autoload.php"; // Incluimos la libreria

$barcode = new \Com\Tecnick\Barcode\Barcode();

$bobj = $barcode->getBarcodeObj(
    'QRCODE,H',                     // Tipo de Barcode o Qr
    'http://evilnapsis.com',          // Datos
    -5,                             // Width 
    -5,                             // Height
    'black',                        // Color del codigo
    array(-2, -2, -2, -2)           // Padding
    )->setBackgroundColor('white'); // Color de fondo

$imageData = $bobj->getPngData(); // Obtenemos el resultado en formato PNG
    
file_put_contents('qrcode.png', $imageData); // Guardamos el resultado


?>
<img src="qrcode.png">


