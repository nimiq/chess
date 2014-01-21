<?php

/**
 * @author nimiq
 * @copyright 2007
 */

require_once 'funzioni_fns.php';

//Avvio una nuova sessione
session_start();

//Controllo se nella sessione (nuova o richiamata) è settatato o no il campo userid
if ( !isset($_SESSION['userid']) ) {
    //Cancello tutti i dati memorizzati in sessione e rimando al login
    session_unset();
    session_destroy();
    include "login.html";
    exit;

}
else {
    $userid = $_SESSION['userid'];

    //Cancello tutti i dati memorizzati in sessione
    session_unset();

    //Rimetto in sessione l'userid
    $_SESSION['userid'] = $userid;


    //Se nel post c'è id_avversario allora devo solo creare una nuova partita tra $userid e $_POST['id_avversario']
    if (isset($_POST['id_avversario'])) {

		creaNuovaPartita($userid, $_POST['id_avversario']);

    } else { //Se nel post non c'è id_avversario allora devo stampare una pagina in cui gli faccio sciegliere l'avversario

        //Ora devo creare una nuova pagina in cui farò selezionare l'avversario
        //Creo la pagina HTML
        $page = new HTML_Page2();
        $page->setTitle('Nuova partita');


        $page->addScriptDeclaration('
				function selezionaAvversario(avversario){
						document.formavversario.id_avversario.value = avversario;
						document.formavversario.submit(); // S  C  A  T  T  A  !!!
				};
				');


        //Aggiungo alla pagina HTML una frase
        $page->addBodyContent("<b>$userid</b> seleziona un avversario:");


        //Mi collego al db mysql
        $conn = collegaDb();
        if (!$conn) {//Significa che non sono riuscito a collegarmi al db
            echo "<p>Problemi nella connessione al db</p>";
            exit;
        }

        aggiungiTabellaAvversari($page, $userid);

        //Aggiungo un link alla lista partite
        $page->addBodyContent("<a href='partite.php'>[Lista partite]</a><br>");


        //Aggiungo la form
        $page->addBodyContent("<form action='nuovapartita.php' method='post' name='formavversario' id='formavversario'>");
        $page->addBodyContent("<input name='id_avversario' type='hidden' value=''>");
        $page->addBodyContent("</form>");

        $page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');
        $page->display();
    }

}



















function creaNuovaPartita($userid, $id_avversario) {
    //Mi collego al db mysql
    $conn = collegaDb();
    if (!$conn) {//Significa che non sono riuscito a collegarmi al db
    	echo "<p>Problemi nella connessione al db</p>";
        exit;
    }
    
	//Preparo la query
	$now = date("Y-m-d H:i:s"); //per stampare anche la Timezone: d-m-Y H:i:s T	
	$query = 	"INSERT INTO partite(data_inizio, white, black, stato, fen)
				VALUES('$now', '$userid', '$id_avversario', 'c', 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1')";
	$result = $conn->query($query);

	//Preparo la pagina di risposta
	$page = new HTML_Page2();
	if (!$result) {
		$page->addBodyContent("Problemi nell'inserimento della nuova partita");
	} else {
		//Scrivo le nuove informazioni sulla partita creata
		$page->addBodyContent("Creata la nuova partita tra:<br>$userid (bianco) - $id_avversario (nero)<br>");		
        //Aggiungo un link alla lista partite
        $page->addBodyContent("<a href='partite.php'>[Lista partite]</a><br>");

	}
	
	$page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');
    $page->display();
	
	

}



















function aggiungiTabellaAvversari($page, $userid) {
    //Mi collego al db mysql
    $conn = collegaDb();
    if (!$conn) {//Significa che non sono riuscito a collegarmi al db
    	echo "<p>Problemi nella connessione al db</p>";
        exit;
    }

    //Cerco sul db tutti gli utenti che non siano $userid
    $query = 	"SELECT userid
				FROM utenti
				WHERE userid <> '$userid'";
				
    $result = $conn->query($query);

	//Creo una tabella in cui visualizzare gli utenti
    $table = new HTML_Table("style='border-collapse: collapse;' cellspacing='0'  cellpadding='0'");
    $table->setAutoGrow(true);
    $col_bordo = "#800000"; //è il colore rosso mattone scuro che me piace assai

    
	$table->setRowAttributes(0, "align='center' valign='center' bgcolor='$col_bordo' style='color: white;'", true);
    $table->setCellContents(0, 0, "<b>Utenti</b>");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(0, 0, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi
   

	//Imposto un ciclo che stampi un radio per ogni partita selezionata 
	$j = 1;
	
	while ( list($userid) = $result->fetch_row() ) {
	    $table->setRowAttributes($j, array('style' => "border: 1px solid $col_bordo"));
	    $table->setCellContents($j, 0, "$userid");//Parametri: N.Riga, N.Colonna, contenuto
		$table->updateCellAttributes($j, 0, array('onclick' => "selezionaAvversario('$userid')"));	    
		$j += 1;
	}

	//Infine aggiungo la tabella alla pagina
    $page->addBodyContent($table->toHTML());	
	
}


?>