<?php

/**
 * @author nimiq
 * @copyright 2007
 */

require_once 'funzioni_fns.php';

//Avvio una nuova sessione
session_start();

//Controllo se nella sessione (nuova o richiamata) è settatato o no il campo userid
if (!isset($_SESSION['userid']) || !isset($_SESSION['id_partita']) || !isset($_SESSION['colore']) || !isset($_SESSION['avversario'])) {
    //Cancello tutti i dati memorizzati in sessione e rimando al login
    session_unset();
    session_destroy();
    include "login.html";
    exit;

}
else {
    $userid = $_SESSION['userid'];
    $id_partita = $_SESSION['id_partita'];
    $colore = $_SESSION['colore'];
    $avversario = $_SESSION['avversario'];

    if (isset($_POST['conferma'])) {
		$conferma = $_POST['conferma'];
		if (strcasecmp($conferma, "true")==0) {
			//echo "mo cancello";

			//Mi collego al DB per eseguire gli aggiornamenti
			$conn = collegaDb();
			if (!$conn) {//Significa che non sono riuscito a collegarmi al db
 		       echo "<p>Problemi nella connessione al db</p>";
	    		exit;
			}

			//Devo scegliere la lettere del colore dell'avversario
			if (strcasecmp($colore, 'w')==0) {
				$coloreAvversario = 'B';
			} else {
				$coloreAvversario = 'W';
			}

			//Imposto come vincente della partita l'avversario (o meglio il colore dell'avversario)
			$query = 	"UPDATE partite
					     SET stato='$coloreAvversario" . "A'" .
						"WHERE id='$id_partita'";
			$result = $conn->query($query);
			if (!$result) {
				throw new Exception("Problemi nell'aggiornamento dello stato della partita");
			}
			
			//Stampo la pagina di conferma
	        $page = new HTML_Page2();
    	    $page->setTitle('Arresa');
    	    $page->addBodyContent("Sei scarsotto e un muchacho codardo!!<br>");    	    
        	//Aggiungo un link alla lista partite
    	    $page->addBodyContent("<a href='partite.php'>[Lista partite]</a>");
			$page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');
	        $page->display();			
		}
    }
    else {

        //Ora devo creare una nuova pagina in cui chiedo la conferma dell'arresa
        //Creo la pagina HTML
        $page = new HTML_Page2();
        $page->setTitle('Arresa');
		
		$sessionId = session_id();
        $page->addScriptDeclaration("
			function conferma(valore){
					if (valore) {
						document.formarresa.conferma.value = valore;
						document.formarresa.submit(); // S  C  A  T  T  A  !!!
					} else {
						document.location.href = 'partite.php?PHPSESSID=$sessionId';						
					}
			};
			");

        //Aggiungo alla pagina HTML una frase
        $page->addBodyContent("<b>$userid</b>: ti arrendi concedendo partita vinta a <b>$avversario</b>?<br>");

        //Aggiungo un link alla lista partite
        $page->addBodyContent("<a onclick='conferma(true);'>[Si]</a>");
        $page->addBodyContent("<a onclick='conferma(false);'> [No]</a><br>");

        //Aggiungo la form
        $page->addBodyContent("<form action='arresa.php' method='post' name='formarresa' id='formarresa'>");
        $page->addBodyContent("<input name='conferma' type='hidden' value=''>");
        $page->addBodyContent("</form>");
		
		$page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');        
        $page->display();
    }


}


?>