<?php

/**
 * @author nimiq
 * @copyright 2007
 */

require_once 'funzioni_fns.php';


/* L'utente può giungere qui da 3 vie:
*  (1) Arriva qui da un link interno al sito:
*       In sessione è presente il suo userid e forse anche altra robaccia
*       Devo svuotare tutte le variabili che ho memorizzato in sessione tranne l'userid
*  (2) Arriva qui dalla form di login
*       In sessione non è presente l'userid, ma nel POST ci sono userid e pwd
*       Devo verificare sul db l'userid e la pwd e, se OK, mettere il suo userid in sessione
*  (3) Arriva qui digitando direttamente l'url di questa pagina
*	     a) Se in sessione non ha l'userid stampo la pagina di login
*       b) Se in sessione ha l'userid devo svuotare tutte le variabili che ho memorizzato in sessione tranne l'userid
*          Il caso b) non dovrebbe mai verificarsi perchè:
*            - adotto un meccanismo di gestione delle sessioni non tramite cookie ma tramite url
*           - quindi se l'utente digita direttamente l'indirizzo di qs pagina la sua sessione viene persa e ricado al punto a)
*/

//Avvio una nuova sessione o richiamo quella già esistente
session_start();

//Controllo se nella sessione (nuova o richiamata) è settatato o no il campo userid
if (isset($_SESSION['userid'])) {//CASO 1: Se il campo userid in sessione è già settato allora l'utente ha già fatto il login
    //Devo svuotare tutte le variabili che ho memorizzato in sessione tranne l'userid

    //Memorizzo l'userid
    $userid = $_SESSION['userid'];

    //Cancello tutti i dati memorizzati in sessione
    session_unset();

    //Rimetto in sessione l'userid
    $_SESSION['userid'] = $userid;

    //Ora devo stampare tutte le partite di questo utente.............................................
    //echo "Benvenuto, $userid!! Le tue partite sono..";
    $conn = collegaDb();
	if (!$conn) {//Significa che non sono riuscito a collegarmi al db
        echo "<p>Problemi nella connessione al db</p>";
	    exit;
	}
	stampaPaginaListaPartite($conn);


}
else {//CASO 2 o 3
    //Devo controllare se nel POST ci sono userid e password che mi ha inviato la form di login
    if ( (isset($_POST['userid']) && isset($_POST['pwd'])) || //CASO 2: Se c'è il campo userid nel POST allora l'utente è giunto qui dalla form di login
	     (isset($_GET['userid']) && isset($_GET['pwd']))   ) { 

		if ( isset($_POST['userid']) ) {
        	$userid = strtolower($_POST['userid']);
	        $pwd = $_POST['pwd'];
        } else {
        	$userid = strtolower($_GET['userid']);
	        $pwd = $_GET['pwd'];
		}

		//TODO: spostare id e pw su un file esterno
        //Mi collego al db mysql
		$conn = collegaDb();
		if (!$conn) {//Significa che non sono riuscito a collegarmi al db
            echo "<p>Problemi nella connessione al db</p>";
		    exit;
		}
		
		
		//TODO: fare l'hash della password
        //Cerco sul db l'utente e la password
        $query = "SELECT userid, pwd FROM utenti WHERE userid='$userid' AND pwd='$pwd'";
        $result = $conn->query($query);

		if ($result->num_rows == 1) {//Se nei risultati del db trovo una corrispondenza
                //Metto il campo userid in sessione e stampo un messaggio di benvenuto
                $_SESSION['userid'] = $userid;//mysql_result($result, 0, "userid");
				$result->free();//svuoto i risultati
                //Ora devo stampare tutte le partite di questo utente.............................................
                //echo "Benvenuto, $userid!! Le tue partite sono..";
                stampaPaginaListaPartite();

        } else {//Se invece nei risultati del db NON trovo alcna corrispondenza, allora significa che nome utente e password sono errate
            //Stampo il msg di errore e rimando l'utente al login
            echo "<p>Nome utente o password errati!!</p>";
            echo "<a href='index.php'>Ripeti il login</a>";
            $result->free();//svuoto i risultati
		}

    } else {//CASO 3: Se NON c'è il campo userid nel POST allora l'utente è appena arrivato qui digitando l'indirizzo web e devo presentargli la form di login
	    session_unset();
    	session_destroy();
	    include "login.html";
    	exit;	
    }
}







function stampaPaginaListaPartite() {

    //Prelevo l'userid dalla sessione (che c'è x forza altrimenti non sarei arrivato fin qui)
    $userid = $_SESSION['userid'];

    //Creo la pagina HTML
    $page = new HTML_Page2();
    $page->setTitle('Chess nimiq');
    
    
    $page->addScriptDeclaration('
				function selezionaPartita(partitaid){
						document.formpartita.id_partita.value = partitaid;
						document.formpartita.submit(); // S  C  A  T  T  A  !!!
				};
				');

    
    

    //Aggiungo alla pagina HTML una frase
    $page->addBodyContent("Benvenuto, <b>$userid</b>!!");


    //Mi collego al db mysql
	$conn = collegaDb();
	if (!$conn) {//Significa che non sono riuscito a collegarmi al db
    	echo "<p>Problemi nella connessione al db</p>";
		exit;
	}

	aggiungiTabellaPartiteInCorso($page, $conn, $userid);

	//Aggiungo un link l'aggiornamento e la creazione di una nuova partita
  	$page->addBodyContent("<a href='partite.php'>[Aggiorna]</a><br>"); //link a se stessa per l'aggiornamento
  	$page->addBodyContent("<a href='nuovapartita.php'>[Nuova partita]</a><br>");

	aggiungiTabellaPartiteConcluse($page, $conn, $userid);

	//Aggiungo un link per il logout: cancella la sessione e rimanda al login
  	$page->addBodyContent("<a href='logout.php'>[Logout]</a>");




	//Aggiungo la form
	$page->addBodyContent("<form action='disegna.php' method='post' name='formpartita' id='formpartita'>");
	$page->addBodyContent("<input name='id_partita' type='hidden' value=''>");
	$page->addBodyContent("</form>");

	$page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');
    $page->display();
}
















function aggiungiTabellaPartiteInCorso($page, $conn, $userid) {
    //Cerco sul db tutte le partite dell'utente $userid ordinate in modo cronolog. INVERSO e ne conto le mosse eseguite
    $query = 	"SELECT partite.id, data_inizio, white, black, fen, COUNT(mosse.id) AS nmosse
				FROM partite
				LEFT JOIN mosse ON partite.id = mosse.id_partita
				WHERE stato = 'c'" . //la query è case insensitive, quindi comprende anche il caso stato = 'C'
			   "AND (white = '$userid' OR black = '$userid')
				GROUP BY partite.id
				ORDER BY partite.id DESC;";
				
    $result = $conn->query($query);

	/* TODO: una possibile ottimizzazione è quella di passare nel POST a disegna.php non solo l'id_partita, ma tutti i 
	 *  dati della partita selezionata, in quanto qs dati (id, data_inizio, white, black, stato) li ho appena prelevati
	 *   con la query qui sopra. Facendo così evito che poi la pagina disegna.php faccia una nuova query x prelevare sti dati
	 */

	//Creo una tabella in cui visualizzare le partite
    $table = new HTML_Table("style='border-collapse: collapse;' cellspacing='0'  cellpadding='0'");
    $table->setAutoGrow(true);
    $col_bordo = "#800000"; //è il colore rosso mattone scuro che me piace assai

    
	$table->setRowAttributes(0, "align='center' valign='center' bgcolor='$col_bordo' style='color: white;'", true);
    $table->setCellContents(0, 0, "<b>Partite in corso</b>");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(0, 0, array('style' => "border: 1px solid $col_bordo", 'colspan' => '3'));//Parametri: N.Riga, N.Colonna, attributi
   
	//La prima riga (1) è quella con le intestazioni
    $table->setRowAttributes(1, "align='center' valign='center' bgcolor='$col_bordo' style='color: white;'", true);

    $table->setCellContents(1, 0, "id");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 0, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

    $table->setCellContents(1, 1, "w - b");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 1, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

    $table->setCellContents(1, 2, "#");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 2, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi


	//Imposto un ciclo che stampi un radio per ogni partita selezionata 
	$j = 2;
	
	while ( list($partita_id, $partita_datai_data, $partita_w, $partita_b, $fen, $nmosse) = $result->fetch_row() ) {
		/* Devo capire se è il turno del bianco o del nero in modo che lo possa scrivere in rosso e grassetto
		 * Per far questo devo prelevare il fen della partita e chiamare il toMove()
		 */
    	//Creo una nuova partita, la inizializzo al fen prelevato e controllo se è conclusa
	    $match = new Games_Chess_Standard;
    	$match->resetGame($fen);

		$turno_di_da_fen = $match->toMove(); //La funzione toMove restituisce: W se tocca al bianco | B se tocca al nero
	    if (strcasecmp($turno_di_da_fen, 'W') == 0) //Tocca al bianco
   	    	$stringa_avversari = "<b><font color='$col_bordo'>$partita_w</font></b> - $partita_b";
   	    else //Tocca al nero
   	    	$stringa_avversari = "$partita_w - <b><font color='$col_bordo'>$partita_b</font></b>";

	    $table->setRowAttributes($j, array('style' => "border: 1px solid $col_bordo"));
	    $table->setCellContents($j, 0, "<center>$partita_id</center>");//Parametri: N.Riga, N.Colonna, contenuto
	    $table->setCellContents($j, 1, $stringa_avversari);//TODO: devo colorare di rosso chi tocca
		$table->updateCellAttributes($j, 1, array('onclick' => "selezionaPartita($partita_id)"));
	    $table->setCellContents($j, 2, "<center>$nmosse</center>");
		//$partita_datai_data

		$j += 1;
	}

	//Infine aggiungo la tabella alla pagina
    $page->addBodyContent($table->toHTML());	
}






function aggiungiTabellaPartiteConcluse($page, $conn, $userid) {
    //Cerco sul db tutte le partite dell'utente $userid ordinate in modo cronolog. INVERSO e ne conto le mosse eseguite
    $query = 	"SELECT partite.id, data_inizio, white, black, fen, stato, COUNT( mosse.id ) AS nmosse
				FROM partite
				LEFT JOIN mosse ON partite.id = mosse.id_partita
				WHERE stato <> 'c'" . //la query è case insensitive, quindi comprende anche il caso stato = 'C'
			   "AND (white = '$userid' OR black = '$userid')
				GROUP BY partite.id
				ORDER BY partite.id DESC;";
				
    $result = $conn->query($query);

	/* TODO: una possibile ottimizzazione è quella di passare nel POST a disegna.php non solo l'id_partita, ma tutti i 
	 *  dati della partita selezionata, in quanto qs dati (id, data_inizio, white, black, stato) li ho appena prelevati
	 *   con la query qui sopra. Facendo così evito che poi la pagina disegna.php faccia una nuova query x prelevare sti dati
	 */

	//Creo una tabella in cui visualizzare le partite
    $table = new HTML_Table("style='border-collapse: collapse;' cellspacing='0'  cellpadding='0'");
    $table->setAutoGrow(true);
    $col_bordo = "#004600"; //è il colore verde marcio che me piace assai

    
	$table->setRowAttributes(0, "align='center' valign='center' bgcolor='$col_bordo' style='color: white;'", true);
    $table->setCellContents(0, 0, "<b>Partite concluse</b>");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(0, 0, array('style' => "border: 1px solid $col_bordo", 'colspan' => '4'));//Parametri: N.Riga, N.Colonna, attributi
   
	//La prima riga (1) è quella con le intestazioni
    $table->setRowAttributes(1, "align='center' valign='center' bgcolor='$col_bordo' style='color: white;'", true);

    $table->setCellContents(1, 0, "id");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 0, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

    $table->setCellContents(1, 1, "w - b");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 1, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

    $table->setCellContents(1, 2, "?");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 2, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

    $table->setCellContents(1, 3, "#");//Parametri: N.Riga, N.Colonna, contenuto
	$table->setCellAttributes(1, 3, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

    ///$table->setCellContents(1, 4, "data");//Parametri: N.Riga, N.Colonna, contenuto
	///$table->setCellAttributes(1, 4, array('style' => "border: 1px solid $col_bordo"));//Parametri: N.Riga, N.Colonna, attributi

	//Imposto un ciclo che stampi un radio per ogni partita selezionata 
	$j = 2;
	
	while ( list($partita_id, $partita_datai_data, $partita_w, $partita_b, $fen, $stato, $nmosse) = $result->fetch_row() ) {
	    if (strcasecmp($stato, 'D') == 0) //Pareggio
		    $stringa_avversari = "$partita_w - $partita_b";
	    else if (strcasecmp(substr($stato, 0, 1), 'W') == 0) //Ha vinto il bianco
   	    	$stringa_avversari = "<b><font color='$col_bordo'>$partita_w</font></b> - $partita_b";
   	    else if (strcasecmp(substr($stato, 0, 1), 'B') == 0) //Ha vinto il nero
   	    	$stringa_avversari = "$partita_w - <b><font color='$col_bordo'>$partita_b</font></b>";
   	    else
   	    	$stringa_avversari = "stato errorato";

	    $table->setRowAttributes($j, array('style' => "border: 1px solid $col_bordo"));
	    $table->setCellContents($j, 0, "<center>$partita_id</center>");//Parametri: N.Riga, N.Colonna, contenuto
	    $table->setCellContents($j, 1, $stringa_avversari);//TODO: devo colorare di rosso chi tocca
		$table->updateCellAttributes($j, 1, array('onclick' => "selezionaPartita($partita_id)"));
	    $table->setCellContents($j, 2, "<center>$stato</center>");
	    $table->setCellContents($j, 3, "<center>$nmosse</center>");
	    ///$table->setCellContents($j, 4, "<center>$partita_datai_data</center>");

		$j += 1;
	}

	//Infine aggiungo la tabella alla pagina
    $page->addBodyContent($table->toHTML());	
}









//echo "<HR>I dati che hai in sessione " . session_id() . " sono: " . session_encode();
?>