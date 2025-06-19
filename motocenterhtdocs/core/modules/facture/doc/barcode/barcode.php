<?php

include "vendor/autoload.php"; // Incluimos la libreria

$barcode = new \Com\Tecnick\Barcode\Barcode();

$bobj = $barcode->getBarcodeObj(
	"C39", 			// Tipo de Barcode o Qr
	"7896543211", 	// Datos
	-2, 			// Width
	-100, 			// Height
	'black', 		// Color del codigo
	array(0, 0, 0, 0)	// Padding
);

$imageData = $bobj->getPngData(); // Obtenemos el resultado en formato PNG
    
file_put_contents('barcode.png', $imageData); // Guardamos el resultado

?>

<br><br><br>
<center>
<img src="barcode.png">
</center>