<?php

/**
 * @author nimiq
 * @copyright 2007
 */


require_once 'funzioni_fns.php';


/* La logica di questa pagina è parecchio complessa e la devo disegnare
 * Ora l'ho disegnata su carta
 */



//Avvio una nuova sessione o richiamo quella già esistente
session_start();

//Controllo se nella sessione (nuova o richiamata) è settatato o no il campo userid
if (!isset($_SESSION['userid'])) {//Se NON c'è il campo userid in sessione, rimando al login

    session_unset();
    session_destroy();
    include "login.html";
    exit;	

} else { //Se invece il campo userid c'è già in sessione

	$userid = $_SESSION['userid'];
	
	/* Se NON c'è il campo id_partita in sessione, potrebbero essere 2 casi:
	 *  - se nel POST c'è id_partita, l'utente è appena arrivato dalla form di partite.php
	 *  - se nel POST NON c'è id_partita, lo rimando a partite.php
	 */
	if (!isset($_SESSION['id_partita'])) {
		
	    if (!isset($_POST['id_partita'])) { //Se nel POST NON c'è id_partita, lo rimando a partite.php
			include "partite.php";
			exit;
		} else { //Se invece nel POST c'è id_partita, l'utente è appena arrivato dalla form di partite.php
			$id_partita = $_POST['id_partita'];

    	    //Mi collego al db mysql
	        $conn = collegaDb();
			if (!$conn) {//Significa che non sono riuscito a collegarmi al db
    	        echo "<p>Problemi nella connessione al db</p>";
			    exit;
			}
	        

		    //Cerco sul db tutte le informazioni sulla partita $id_partita e l'ultima mossa
		    /* Se volessi fare un'unica query che tira fuori tutti i dati della partita sarebbe la seguente.
		     * Il problema è che se la partita è nuova di sberla non ha associato alcuna mossa, quindi la query
		     *   non ritorna alcuna riga come risultato
		     *
			 * $query = "SELECT white, black, stato, fen, mossa_da, mossa_a
			 *			FROM partite
			 *			INNER JOIN mosse ON partite.id = mosse.id_partita
			 *			WHERE partite.id = '$id_partita'
			 *			AND mosse.id = (
			 *				SELECT max(id)
			 *				FROM mosse
			 *				WHERE id_partita = '$id_partita' )";
			 *
			 * La soluzione migliore è quindi quella di eseguire 2 query: una che estrare i dati della partita e
			 *  una che estrae i dati dell'ultima mossa:
			 */
			
			//TODO: catturare eventuali problemi nel recupero dei dati da DB, cioè se il risultato della query sono 2 partite o ...
			//I dati della partita $id_partita:
			$query = 	"SELECT white, black, stato, fen
						FROM partite
						WHERE id = '$id_partita'";
			$result = $conn->query($query);
			//Estraggo tutti i dati cher mi interessano
			list($white, $black, $stato_partita, $fen) = $result->fetch_row();
			

			//L'ultima mossa della partita $id_partita (NB: potrebbe non esserci alcuna mossa)
			$query = 	"SELECT mossa_da, mossa_a, pezzomangiato
						FROM mosse
						WHERE id_partita = '$id_partita'
						AND id = (
							SELECT max(id)
							FROM mosse
							WHERE id_partita = '$id_partita' )";
			$result = $conn->query($query);
			if ($result->num_rows == 0) {//Se la query non ha trovato nessun risultato significa che la partita è nuova di sberla e non ha ancora associato alcuna mossa
				//Valorizzo le ultime mosse e il msg di cattura a ''
				$ultima_mossa_da = '';			
				$ultima_mossa_a = '';
				$pezzomangiato = '';			
			} else {//Se invece a questa partita sonoi legate delle mosse, le estraggo
				//Estraggo tutti i dati che mi interessano
				list($ultima_mossa_da, $ultima_mossa_a, $pezzomangiato) = $result->fetch_row();
			}


			//Devo capire che colore è l'utente attuale e chi è il suo avversario
			if (strcasecmp($white, $userid)==0) {
				$colore = 'w';
				$avversario = $black;
			} else {
				$colore = 'b';
				$avversario = $white;
			}
			
			//Ora metto tutti i dati estratti in sessione
			$_SESSION['id_partita'] = $id_partita;
			$_SESSION['fen'] = $fen;
			$_SESSION['ultima_mossa_da'] = $ultima_mossa_da;
			$_SESSION['ultima_mossa_a'] = $ultima_mossa_a;
			$_SESSION['avversario'] = $avversario;
			$_SESSION['stato_partita'] = $stato_partita;
			$_SESSION['colore'] = $colore;
			
			//Ora devo estrarre tutti i pezzi mangiati, preparare il messaggio con i pezzi mangiati e metterlo in sessione
			//SELECT pezzomangiato
			//FROM mosse
			//WHERE id_partita = '13'
			//AND pezzomangiato <> ''	
			$query = 	"SELECT pezzomangiato
						FROM mosse
						WHERE id_partita = '$id_partita'
						AND pezzomangiato <> '';";
			$result = $conn->query($query);
			if ($result->num_rows == 0) {//Se la query non ha trovato nessun risultato significa che non c'è ancora nessun pezzo mangiato
				$msgListaPezziMangiati = '';
			} else {//Se invece ci sono dei pezzi mangiati preparo il messaggio
				$useridHaPerso = "<b>$userid</b> ha perso: ";
				$avversarioHaPerso = "<b>$avversario</b> ha perso: ";

				while ( list($singoloPezzoMangiato) = $result->fetch_row() )
					$listaPezziMangiati[] = $singoloPezzoMangiato;
				if (strcasecmp($colore, 'w')==0) {
					$useridHaPerso .= stampaPezziMangiatiNumerati($listaPezziMangiati, "w");
					$avversarioHaPerso .= stampaPezziMangiatiNumerati($listaPezziMangiati, "b");
				} else {
					$useridHaPerso .= stampaPezziMangiatiNumerati($listaPezziMangiati, "b");
					$avversarioHaPerso .= stampaPezziMangiatiNumerati($listaPezziMangiati, "w");
				}
				
				//$j1 = 0;
				//$j2 = 0;
				/*while ( list($singoloPezzoMangiato) = $result->fetch_row() ) {
					//Se il pezzo è maiuscolo e l'utente è il bianco o il pezzo è minuscolo e l'utente è il nero, aggiungo il pezzo alla lista dei persi dall'utente
					if ( ($singoloPezzoMangiato==strtoupper($singoloPezzoMangiato))  && (strcasecmp($colore, "W")==0) ||
					     ($singoloPezzoMangiato==strtolower($singoloPezzoMangiato))  && (strcasecmp($colore, "B")==0) ) {
						$arrayPezziPersiDaUserid[] = $singoloPezzoMangiato; //es.Array ( [0] => P [1] => P [2] => Q )
						/*if ($j1>0) $useridHaPerso .= ", "; //se non è il primo elemento che aggiungo, metto ", "
						$useridHaPerso .= nomePezzo($singoloPezzoMangiato) . " ";
						$j1 += 1;*/
				/*	} else {//Altrimenti aggiungo il pezzo alla lista dei persi dall'avversario
						$arrayPezziPersiDaAvversario[] = $singoloPezzoMangiato;//es. Array ( [0] => p [1] => p [2] => q )
						/*if ($j2>0) $avversarioHaPerso .= ", "; //se non è il primo elemento che aggiungo, metto ", "
						$avversarioHaPerso .= nomePezzo($singoloPezzoMangiato) . " ";
						$j2 += 1;*/
					/*}
				}
				$useridHaPerso = print_r($arrayPezziPersiDaUserid);
				$avversarioHaPerso = print_r($arrayPezziPersiDaAvversario);*/
				$msgListaPezziMangiati = "$useridHaPerso<br>$avversarioHaPerso";
			}
			$_SESSION['msgListaPezziMangiati'] = $msgListaPezziMangiati;


		

			if ( $pezzomangiato != '') {//Se il $pezzomangiato non è vuoto preparo la stringa da scrivere
				$pezzomangiato = nomePezzo($pezzomangiato, true); //ora $pezzomangiato contiene '' oppure qualcosa tipo 'ol pedu!'
				$pezzomangiato = "To maiat $pezzomangiato!<BR>";
			}
			//Disegno la scacchiera passandogli la stringa preparata col $pezzomangiato come parametro
			$page = disegnaScacchiera($pezzomangiato);
			//$page->display();
		}


		
	} else { //Se invece il campo id_partita c'è già in sessione allora significa che ho appena fatto una mossa e devo controllarnle la validità
		
		//Estraggo l'id_partita (che c'è x forza dato che è la condizione dell'else qui sopra)
		$id_partita = $_SESSION['id_partita'];
		
		if ( !isset($_SESSION['fen']) || //Se in sessione manca qualche dato essenziale lo rimando alla selezione della partita
			 !isset($_SESSION['ultima_mossa_da']) ||
			 !isset($_SESSION['ultima_mossa_a']) ||
			 !isset($_SESSION['avversario']) ||
			 !isset($_SESSION['stato_partita']) ||
		     !isset($_SESSION['colore']) ) {
				include "partite.php";
				exit;

		} else { //Se ho tutti i dati essenziali controllo se ho anche la prossima mossa nel POST o già in SESSIONE

			if ( isset($_POST['prossima_mossa_da']) && //Se la prossima mossa c'è nel POST la metto in sessione
				 isset($_POST['prossima_mossa_a']) ) {
				$_SESSION['prossima_mossa_da'] = $_POST['prossima_mossa_da'];
				$_SESSION['prossima_mossa_a'] = $_POST['prossima_mossa_a'];
			}

			if ( !isset($_SESSION['prossima_mossa_da']) || //Se non ho la prossima mossa gli disegno la scacchiera
				 !isset($_SESSION['prossima_mossa_a']) ) {
				//Disegno la scacchiera passandogli un messaggio vuoto come parametro
				$page = disegnaScacchiera();
				//$page->display();					

			} else { //Se ho la prossima mossa, sono pronto a cercare nel POST il messaggio di conferma/annullo della mossa
				
			    if (isset($_POST['conferma'])) //Se nel POST c'è il messaggio di conferma/annullo della mossa lo metto in sessione
					$_SESSION['conferma'] = $_POST['conferma'];

				/* Esguo la mossa
				 * La funzione eseguiMossa() controlla se la mossa non è confermata, è confermata o è annullata
				 *  - non confermata: ne valuta la validità e nerichiede la conferma
				 *  - confermata: la esegue e aggiorna il DB
				 *  - annullata: la annulla
				 */
				$page = eseguiMossa();
				//$page->display();				
			}
		}
	}
}
$page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');
$page->display();


//echo "<HR>I dati che hai in sessione " . session_id() . " sono: " . session_encode();
?>