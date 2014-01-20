<?php

/**
 * @author nimiq
 * @copyright 2007
 */

$a[] = "P";
$a[] = "P";
$a[] = "Q";
$a[] = "Q";
$a[] = "Q";
$a[] = "Q";
$a[] = "Q";
$a[] = "Q";
$a[] = "N";
$a[] = "B";
$a[] = "K";
$a[] = "K";


$ss = stampaPezziMangiatiNumerati($a, "w");
echo "aaa: ";
echo $ss;

echo "<br>";

$vero = true;
echo $vero ? "si" : "no";


/* Qs funzione restituisce una lista dei pezzi mangiati con le loro quantita: es. [2] pedu, [1] figheta
 *   $listaPezziMangiati è la lista dei pezzi mangiati: es. Array ( [0] => P [1] => P [2] => Q ) 
 *   $colore è il colore: es. w  o  b
 */
function stampaPezziMangiatiNumerati($listaPezziMangiati, $colore){
	//Costruisco un array con i conteggi di tutti i pezzi
	//Es. Array ( [P] => 2 [Q] => 1 ), cioè sono state mangiate al bianco 2 pedine e una regina
	$conteggioPedineMangiatePerPezzo = array_count_values($listaPezziMangiati);

	//Costruisco un array con tutti i pezzi possibili
	$arrayPezzi = array("P", "R", "B", "N", "Q", "K");
	
	//Converto l'array in minuscolo(black) o maiuscolo(white) come specificato in $case
	if (strcasecmp($colore, "W")==0) {
    	foreach($arrayPezzi as $key => $val)
			$arrayPezzi[$key] = strtoupper($val);
	} else if (strcasecmp($colore, "B")==0) {
    	foreach($arrayPezzi as $key => $val)
			$arrayPezzi[$key] = strtolower($val);
	} else {
		return '';
	}

	$restituisci = '';
   	foreach($arrayPezzi as $key => $val) {
		if ( array_key_exists($val, $conteggioPedineMangiatePerPezzo) ) {
			$restituisci = $restituisci . "[" . $conteggioPedineMangiatePerPezzo[$val] . "] " . nomePezzo($val) . ", ";
		}
	}
	
	$restituisci = substr($restituisci, 0, count($restituisci)-3); //Taglio via la virgola e lo spazio finali
	return $restituisci;
	
}





/* Questa funz converta la lettera identif del pezzo nel nome in bergamasco
 * E' case insensitive
 */
function nomePezzo($pezzo) {
	if ( strcasecmp($pezzo, "p")==0 )
		return "pedu";
	else if ( strcasecmp($pezzo, "r")==0 )
		return "tor";
	else if ( strcasecmp($pezzo, "b")==0 )
		return "alfer";
	else if ( strcasecmp($pezzo, "n")==0 )
		return "caal";
	else if ( strcasecmp($pezzo, "q")==0 )
		return "figheta";
	else if ( strcasecmp($pezzo, "k")==0 )
		return "re";
	else
		return "Bho";
}




?>