<?php
  
/**
 * @author nimiq
 * @copyright 2007
 */

/* $_SERVER["HTTP_HOST"] serve per vedere su quale server è eseguita la pagina php, vale:
 *  localhost - se è eseguito il locale sulla mia macchina di sviluppo
 *  nimiq.homeip.net - se è eseguito sul mio pc di sviluppo raggiunto dall'indirizzo nimiq.homeip.net
 *  nimiq.netsons.org - se è eseguito su netsons
 *  www.nimiq.netsons.org - se è eseguito su netsons e raggiunto scrivendo il wwww
 */
 
 
 
/* Per Joomla ho dovuto modificare la gestione delle sessioni abilitando i cookie
   Posso cmq sistemare tutto cambiando a codice i seguenti parametri
    session.use_cookies = 1
    session.use_only_cookies = 0
    session.use_trans_sid = 0
   Oppure in modo + intelligente potrei convertire tutto usando i cookies
*/



$netsons = stripos($_SERVER["HTTP_HOST"], "netsons"); //Cerca (case insensitive) la stringa netsons nella variab $_SERVER["HTTP_HOST"]
if ( $netsons !== false ) {//Se l'HTTP_HOST è la macchina di produzione su netson
	//Imposto il path del PEAR sul server Netsons.org
	ini_set("include_path", "/var/www/netsons.org/nimiq/PEAR/PEAR");
}

//Impongo l'uso di sessioni in url e non in cookie
ini_set("session.use_cookies", "0");
ini_set("session.use_only_cookies", "0");
ini_set("session.gc_maxlifetime", "18000");
ini_set("session.use_trans_sid", "1");
	

require_once 'Games/Chess/Standard.php';
require_once 'HTML/Page2.php';
require_once 'HTML/Table.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Renderer/Tableless.php';





function collegaDb() {
	if ( (strcasecmp($_SERVER["HTTP_HOST"], "localhost")==0)    OR
	     ((stripos($_SERVER["HTTP_HOST"], "homeip")) !== false) ) { //Se l'HTTP_HOST è localhost oppure contiene homeip sono sulla mia macchina di sviluppo
		$conn = @new mysqli("localhost", "xzy", "xzy", "xzy");//parametri: server, id, pw, db
	} else { //Altrimenti sono in produzione
		$conn = @new mysqli("mysql5.netsons.org", "xzy", "xzy", "xzy");//parametri: server, id, pw, db		
	}
	
	if (mysqli_connect_errno()) {
		return false;
	} else {
		return $conn;
	}
}





/* Questa funzione si occupa di disegnare la scacchiera, controlla lo stato della partita e il turno
 * In questa pagina ci sono due aree messaggi (implementate con 2 div)
 *   areamsgalto: serve per i messaggi di: mossa non valida, partita finita per vittoria o pareggio, richiesta di conferma
 *   areamsgbasso: serve per tutto il resto: pedine mangiate, link vari
 * Quindi i parametri:
 *   $messaggiobasso='' viene scritto in areamsgbasso
 *   $messaggioalto='' viene scritto in areamsgalto
 */

function disegnaScacchiera($messaggiobasso='', $messaggioalto='', $chiedoConferma=false) {
	
	//TODO: devo fare controlli sul fatto che effetivamente sti dati ci siano in sex?????
	//in realtà se sono qui significa che ci sono...	
	$userid = $_SESSION['userid']; //es. nimiq
	$colore = $_SESSION['colore'];	//es. b
	$avversario = $_SESSION['avversario']; //es. angelo
	$fen = $_SESSION['fen'];
	$stato_partita = $_SESSION['stato_partita'];
	$ultima_mossa_da = $_SESSION['ultima_mossa_da'];
	$ultima_mossa_a = $_SESSION['ultima_mossa_a'];
	
	//Se c'è la prossima mossa in sessione la tiro fuori
	if ( isset($_SESSION['prossima_mossa_da']) &&
	     isset($_SESSION['prossima_mossa_a']) ) {
		$prossima_mossa_da = $_SESSION['prossima_mossa_da'];
		$prossima_mossa_a = $_SESSION['prossima_mossa_a'];
	} else {
		$prossima_mossa_da = '';
		$prossima_mossa_a = '';
	}

  
    //Creo una nuova partita
    $match = new Games_Chess_Standard;
  
    //Inizializzo la partita con lo snapshot fen che mi è stato passato
    $match->resetGame($fen);
  
    /* Controllo lo stato della partita dal $fen e da $stato_partita:
    *  - se è finita x entrambi ($fen e $stato_partita): disegno la scacchiera finale non cliccabile con scritto il bianco/nero vince/pari
    *  - se è in corso x entrambi continuo
    *  - se $fen e $stato_partita sono diversi e per $fen è in corso ma per $stato_partita è chiusa anomala: disegno la scacchiera finale non cliccabile
    *  - se $fen e $stato_partita sono diversi ma non è il caso precedente: assumo come situazione vera quella di $fen e sistemo le cose sul db
    */
    //La funzione gameOver() restituisce: W se ha vinto il bianco | B se ha vinto il nero | D se è pari | false se è in corso
	$stato_da_fen = $match->gameOver();
    if ( strcasecmp($stato_partita, "WA")==0 || strcasecmp($stato_partita, "BA")==0) { //Se ha vinto qualcuno per arresa
		if ( strcasecmp($stato_partita, "WA")==0 && strcasecmp($colore, "W")==0) {
			$messaggioalto = "$userid (bianco) ha vinto per arresa!!";
		} else if ( strcasecmp($stato_partita, "WA")==0 && strcasecmp($colore, "B")==0) {
			$messaggioalto = "$avversario (bianco) ha vinto per arresa!!";
		} else if ( strcasecmp($stato_partita, "BA")==0 && strcasecmp($colore, "W")==0) {
			$messaggioalto = "$avversario (nero) ha vinto per arresa!!";
		} else if ( strcasecmp($stato_partita, "BA")==0 && strcasecmp($colore, "B")==0) {
			$messaggioalto = "$userid (nero) ha vinto per arresa!!";
		}
	} else {
	    //TODO: DEVO CONTROLLARE E TAGLIARE FUORI TUTTI I CASI STRANI
	    if ( strcasecmp($stato_da_fen, "w")==0 || strcasecmp($stato_da_fen, "b")==0) { //Se ha vinto qualcuno
	    	//Trasformo il colore vincente (w o b) in lettere
			if (strcasecmp($stato_da_fen, "w")==0)
		    	$winner_colore = 'bianco';
	    	else
			    $winner_colore = 'nero';
		
			//COntrollo chi tra $userid e $avversario ha vinto
			if (strcasecmp($stato_da_fen, $colore)==0)
		    	$winner_nome = $userid;
		    else
			    $winner_nome = $avversario;
	    
			$messaggioalto = "SCACCO MATTO!!<br>$winner_nome ($winner_colore) ta set trop fort!";
	    } else if ( strcasecmp($stato_da_fen, "d")==0 ) {//Se è un pareggio
			$messaggioalto = "PAREGGIO!! A sii du sumaru!!";		
		}//Se passo l'if significa che la partita è in corso
	}
  
    //Controllo a chi tocca
    if (!$stato_da_fen) {//Se stato_da_fen è falso allora la partita è in corso
		//La funzione toMove restituisce: W se tocca al bianco | B se tocca al nero
    	$turno_di_da_fen = $match->toMove();
	    if (strcasecmp($turno_di_da_fen, $colore) == 0) //Se è il proprio turno
    	    $turno_proprio = true;
	    else //Se invece NON è il proprio turno
    	    $turno_proprio = false;
  	} else {//Se stato_da_fen non vale falso (ma vale w o b o d allora la partita è finita e non è il turno di nessuno)
		$turno_proprio = false;
	}
	
	//
	
    //Ottengo l'array della partita
    $caselle = $match->toArray();
  
    //Creo la pagina HTML
    $page = new HTML_Page2();
    
    //Imposto il titolo con i nomi in stile: [bianco] - [nero]
    if ( strcasecmp($colore, "w")==0 ) //se l'utente è il bianco scrivo prima il suo nome
		$titolo = "$userid - $avversario";
	else  //se invece l'utente è il nero scrivo prima il nome dell'avversario
		$titolo = "$avversario - $userid";	
    
    $page->setTitle($titolo);
  
    //Se è il proprio turno e non è la pagina di conferma alora aggiungo alla pagina HTML lo script che gestisce le mosse
    if ($turno_proprio && !$chiedoConferma && (strcasecmp($stato_partita, "c")==0) ) {
        $page->addScriptDeclaration('
				var celle_selez = new Array();
  
				function coloratd(elemento, colore_scacchiera){
					//Espirmo il colore dell evidenziazione nei due modi possibili perchè il browser lo puo interpretare diversamente (infatto così è per firefox e opera)
					//NB: non c è bisogno di esprimere gli altri colori nei 2 modi perchè l if è solo su qs campo
					colore_evidenziato1 = "#ffff00";
					colore_evidenziato2 = "rgb(255, 255, 0)";
				
					if ( (elemento.style.backgroundColor != colore_evidenziato1) &&
						 (elemento.style.backgroundColor != colore_evidenziato2) ) { //Se l elemento selez NON è evidenziato
						elemento.style.backgroundColor = colore_evidenziato1;
						celle_selez.push(elemento.id);
					} else {
							elemento.style.backgroundColor = colore_scacchiera;
							celle_selez = new Array();
					}

					/////areamsgalto = document.getElementById("areamsgalto");
					/////areamsgalto.innerHTML += elemento.id;
					if (celle_selez.length==2) {
						document.formmossa.prossima_mossa_da.value = celle_selez.shift();
						document.formmossa.prossima_mossa_a.value = celle_selez.shift();
						document.formmossa.submit(); // S  C  A  T  T  A  !!!
						////areamsgalto.innerHTML = "Scatta: " + document.formmossa.prossima_mossa_da.value + document.formmossa.prossima_mossa_a.value;// S  C  A  T  T  A  !!!
						celle_selez = new Array();
					}
				};
		');
    }
    
    if ($chiedoConferma) {
        $page->addScriptDeclaration('
				function conferma(ok){
					if (ok) {
						document.formmossa.conferma.value = "ok";
					} else {
						document.formmossa.conferma.value = "nok";
					}
					document.formmossa.submit(); // S  C  A  T  T  A  !!!
				};
		');

	}
  
    //Aggiungo l'area in cui in alto stampare l'eventuale messaggio di errore, le richieste di conferma, le vittorie
    $page->addBodyContent("<div id='areamsgalto' name='areamsgalto'>$messaggioalto</div>");
  
  
  	//Definisco i colori
  	$col_chiaro = "#FFCE9E"; //marrone chiaro
	$col_scuro = "#D18B47"; //marrone scuro
	$col_ultima_mossa = "#f4724c"; //mattone
	$col_selezione = "#ffff00"; //giallo -- NB: è specificato anche nel javascript qui sopra
  
    //Creo una nuova tabella in cui  metto l'array della partita
    $table = new HTML_Table("style='border-collapse: collapse' cellspacing='0'  cellpadding='0'; text-align: center");
    $table->setAutoGrow(true);
  
  
    //Imposto un ciclo for che estrare dall'array delle caselle una riga alla volta (cioè 8 elementi alla volta)
    for ($offset = 0, $nRiga = 0; $offset < 64; $offset += 8, $nRiga++) {//Per ognuna delle 8 righe
        //Dell'array caselle estraggo solo una riga alla volta (composta da 8 elementi))
        $riga = array_slice($caselle, $offset, 8);
        $table->setRowAttributes($nRiga, "id='riga$nRiga' align='center' valign='center'", true);
        //print_r($riga, TRUE); //darebbe qs risultati:
        //Es. Array ( [a7] => p [b7] => p [c7] => p [d7] => p [e7] => p [f7] => [g7] => [h7] => p )
        //Es. Array ( [a6] => [b6] => [c6] => [d6] => [e6] => [f6] => p [g6] => [h6] => )
  
        //Stampo ognuno degli 8 elementi della riga estratta
        if (($nRiga % 2) == 0)
            $rigaPari = true;
        else
            $rigaPari = false;
        $nColonna = 0;
        foreach ($riga as $casella => $pezzo) {//Per onguno delgi 8 elementi di una riga
            if (($nColonna % 2) == 0)
                $colonnaPari = true;
            else
                $colonnaPari = false;
  
		
            if (($colonnaPari && $rigaPari) || (!$colonnaPari && !$rigaPari))
                $colore = $col_chiaro;
            else
                $colore = $col_scuro;
  
            //Se la casella è una delle due interessate dall'ultima mossa la coloro di #f4724c (arancio)
            if (strcasecmp($casella, $ultima_mossa_da)==0 || strcasecmp($casella, $ultima_mossa_a)==0) {
                $colore = $col_ultima_mossa;
            }
  
  			if ($chiedoConferma) {
	            //Se la casella è una delle due interessate dalla prossima mossa la coloro di #f4724c (giallo)
    	        if (strcasecmp($casella, $prossima_mossa_da)==0 || strcasecmp($casella, $prossima_mossa_a)==0) {
        	        $colore = $col_selezione;
            	}
            }

  			/* Per ogni pezzo devo prelevare l'immagine corrispondente
  			 * Le immagini png sono memorizzate in file con la seguente nomenclatura:
  			 *  [colore]_[iniziale pezzo].png  -- tutto a lettere minuscole
  			 *  es. regina nera:   b_q.png
  			 *  es. pedina bianca: w_p.png
  			 * La variabile $pezzo memorizza i pezzi secondo le convenzioni standard, cioè le inziali dei pezzi in
  			 *  maiuscolo per il bianco e in minuscolo per il nero
  			 *  es. regina nera:   q
  			 *  es. pedina bianca: P
  			 */
  			if ($pezzo == '') {
				$percorso_png = "";
			} else if ($pezzo == strtoupper($pezzo)) { //Se il pezzo è maiuscolo, allora appartiene al bianco
				$nomeminuscolo = strtolower($pezzo); //lo converto il minuscolo (anche se lo è già) xkè su file system il file è memorizzato in minuscolo
				$percorso_png = "<img border='0' src='img/w_$nomeminuscolo.png'>";
			} else if ($pezzo == strtolower($pezzo)) { //Altrimenti il pezzo è minuscolo, allora appartiene al nero
				$nomeminuscolo = strtolower($pezzo); //lo converto il minuscolo (anche se lo è già) xkè su file system il file è memorizzato in minuscolo
				$percorso_png = "<img border='0' src='img/b_$nomeminuscolo.png'>";
			}
  			
  			//Nelle singole celle metto le immagini dei pezzi
			$dimensioneCella = 19;
            $table->setCellContents($nRiga, $nColonna, $percorso_png);//Parametri: N.Riga, N.Colonna, contenuto
			
			//Ora imposto gli attributi di ogni cella (colore e link)
			//Costruisco l'array degli attributi della cella'
			$array_attributi_cella = array('id' => "$casella", 'height' => "$dimensioneCella", 'width' => "$dimensioneCella", 'bgcolor' => "$colore", 'style' => "border: 1px solid $col_scuro");
		    //Se è il mio turno aggiungo un onclick
			if ($turno_proprio)
				$array_attributi_cella['onclick'] = 'coloratd(this,\'' . "$colore" . '\');';
			//Ora imposto gli attributi
			$table->setCellAttributes($nRiga, $nColonna, $array_attributi_cella);//Parametri: N.Riga, N.Colonna, attributi

            $nColonna += 1;
        }
    }
  
    //Aggiungo alla pagina HTML la tabella appena creata
    $page->addBodyContent($table->toHTML());
  

	//Aggiungo un link alla pagina delle partite
    $messaggiobasso = $messaggiobasso . "<a href='partite.php'>[Lista partite]</a><BR>";
    $messaggiobasso = $messaggiobasso . "<a href='fen.php'>[Fen]</a><BR>";
	if ( $turno_proprio && strcasecmp($stato_partita, "c")==0 )
	    $messaggiobasso = $messaggiobasso . "<a href='arresa.php'>[Arresa]</a><BR>";

	//Aggiungo l'area messaggi in basso
    $page->addBodyContent("<div id='areamsgbasso' name='areamsgbasso'>$messaggiobasso</div>");

	//Aggiungo l'area messaggi per i pezzi presi
	$msgListaPezziMangiati = $_SESSION['msgListaPezziMangiati'];
	$largdiv = $dimensioneCella * 8 + 9;
    $page->addBodyContent("<div id='areamsgpezzipresi' name='areamsgpezzipresi' style='width:$largdiv"."px; background-color:#CCCCCC'>$msgListaPezziMangiati</div>");


    //Creo una nuova form
    $form = new HTML_QuickForm('formmossa', 'post');
    $renderer = $form->defaultRenderer();
    $renderer->setFormTemplate('<form{attributes}>{content}</form>');
    $renderer->setElementTemplate('{element}<br>');
  
    $form->addElement('hidden', 'prossima_mossa_da');
    $form->addElement('hidden', 'prossima_mossa_a');
   
	if($chiedoConferma) {
	    $form->addElement('hidden', 'conferma');
	    $page->addBodyContent("<div id='areconf' name='areaconf'>Confermi?
		                       <strong onclick='conferma(true);'>SI</strong>
		                       <strong onclick='conferma(false);'>NO</strong>
							   </div>", HTML_PAGE2_PREPEND);
	}
  
    $form->accept($renderer);
    $page->addBodyContent($renderer->toHTML());
  
  
    //Restituisco la pagina creata
    return $page;
  
}




















function eseguiMossa() {
	//TODO: devo fare controlli sul fatto che effetivamente sti dati ci siano in sex?????
	//in realtà se sono qui significa che ci sono...
	$userid = $_SESSION['userid'];
	$id_partita = $_SESSION['id_partita'];
	$colore = $_SESSION['colore'];	
	$avversario = $_SESSION['avversario'];
	$fen = $_SESSION['fen'];
	$stato_partita = $_SESSION['stato_partita'];
	$ultima_mossa_da = $_SESSION['ultima_mossa_da'];
	$ultima_mossa_a = $_SESSION['ultima_mossa_a'];
	$prossima_mossa_da = $_SESSION['prossima_mossa_da'];
	$prossima_mossa_a = $_SESSION['prossima_mossa_a'];
	
	/* $_SESSION['conferma'] è l'unico che potrebbe non esserci in sessione, o meglio potrebbe:
	 *   - non esserci
	 *   - esserci e valere 'ok'
	 *   - esserci e valere 'nok'
	 */
	if (isset($_SESSION['conferma']))
		$conferma = $_SESSION['conferma'];
	else
		$conferma = '';

	if (strcasecmp($conferma, "nok")==0 ) { //Se conferma=nok l'utente richiede l'annullo della mossa

		//L'utente ha annullato quindi tolgo dalla sessione la prossima mossa, la conferma e il pezzo mangiato
		unset($_SESSION['prossima_mossa_da']);
		unset($_SESSION['prossima_mossa_a']);
		unset($_SESSION['conferma']);
		unset($_SESSION['pezzomangiato']);
		
		//Ripristo lo stato FEN precedente
		if ( !isset($_SESSION['fen_vecchio']) ) {
			//TODO: Se non trovo il fen_vecchio in sex vado a prenderlo dal db
		} else {
			$_SESSION['fen'] = $_SESSION['fen_vecchio'];
		}
	    //Disegno la scacchiera e restituisco la pagina creata
		$page = disegnaScacchiera();
    	return $page;
		
		
	} else if (strcasecmp($conferma, "ok")==0 ) { //Se conferma=ok l'utente sta confermando della mossa

		//Aggiorno stato sul db
		try {
			aggiornaDb();
		} catch (Exception $exc) {
			$page = new HTML_Page2();
			$page->addBodyContent($exc->getMessage());
		    return $page;
		}

	    //Disegno la scacchiera e restituisco la pagina creata
		$page = disegnaScacchiera();
    	return $page;
	
	} else { //Altrimenti conferma non c'è in sessione - devo valutare la validita della mossa e chiedere la conferma
	
    	//Creo una nuova partita di tipo standard
	    $match = new Games_Chess_Standard;

    	//Inizializzo lo snapshot della partita (notazione FEN)
	    $match->resetGame($fen);
	    ////////echo "Posizioni " . ($nMosse = 0) . ": " . $match->renderFen() . "<br>";

		//TODO: in caso di promozione chiedere all'utente che promozione vuole (ora impongo una regina)
	    //Controllo se la mossa indicata implica una promozione (cioè una pedina ha raggiunto la linea di fondo avversaria)
	    if ($match->isPromoteMove($prossima_mossa_da, $prossima_mossa_a))
    	    $sceltaPromozione = 'q';//scelgo la promozione - maiuscolo o minuscolo è uguale, capisce lui di chi è il pezzo (Regina=Q, Torre=R, Alfiere=B, Cavallo=N)
	    else
    	    $sceltaPromozione = null;//se la mossa non prevede una promozione imposto $sceltaPromozione a null

	    
	    //Controllo se la mossa porta alla cattura di qualche pezzo
	    $caselle = $match->toArray(); //ottengo l'array della partita
		$pezzomangiato = $caselle[$prossima_mossa_a]; //se la casella $prossima_mossa_a contiene un pezzo lo estraggo
		$_SESSION['pezzomangiato'] = $pezzomangiato; //Ora metto in sessione il pezzo mangiato


		//Eseguo la mossa e ne catturo l'eventuale errore
	    $err = $match->moveSquare($prossima_mossa_da, $prossima_mossa_a, $sceltaPromozione);
    
	    if ($match->isError($err)) { //Se la mossa è NOK (è ILLEGALE)
			
			//La mossa non è valida quindi tolgo dalla sessione la prossima mossa e il pezzo mangiato
			unset($_SESSION['prossima_mossa_da']);
			unset($_SESSION['prossima_mossa_a']);
			unset($_SESSION['pezzomangiato']);

			//Disegno la scacchiera con il messaggio di errore
			$page = disegnaScacchiera('', $err->getMessage());
		    //Restituisco la pagina creata
		    return $page;
    		
	    } else { //Se la mossa è OK (è LEGALE)
	
	   	    if ($match->inCheckmate() || $match->inDraw()) {//Se è SCACCO MATTO oppure è PAREGGIO non chiedo conferma ma aggiorno subito la situazione
				//Accetto subito la mossa (anche senza conferma) e la sposto come ultima mossa
				$_SESSION['fen'] = $match->renderFen();
				$_SESSION['stato_partita'] = $match->gameOver();

				//Aggiorno stato sul db
				try {
					aggiornaDb();
				} catch (Exception $exc) {
					$page = new HTML_Page2();
					$page->addBodyContent($exc->getMessage());
				    return $page;
				}

				//Disegno la scacchiera e restituisco la pagina creata
				$page = disegnaScacchiera();
		    	return $page;

	        } else {//Altrimenti è una normale mossa consentita che non ha portato la partita alla conclusione
				//se è una mossa normalissima
				$_SESSION['fen_vecchio'] = $_SESSION['fen'];
				$_SESSION['fen'] = $match->renderFen();
				
				//Disegno la scacchiera con la richiesta di conferma
				$page = disegnaScacchiera('', '', true); //con true gli dico di chiedere conferma
			    //Restituisco la pagina creata
		    	return $page;
			}
    	}
	}


}







/* TODO: dopo la conferma della mossa viene mostrata la scacchiera senza i 2 messaggi informativi pezzomangiato e msgListaPezziMangiati
 * Se voglio implementare qs basta che:
 *  - implementi  pezzomangiato come variabile di sessione e non come parametro della funzione disegnaScacchiera
 *  - dopo la conferma della mossa, se la mossa ha portato alla cattura di qualche pezzo, devo solo aggiornare le 2 variabili in sessione pezzomangiato e msgListaPezziMangiati
 */

















function aggiornaDb() {
	$id_partita = $_SESSION['id_partita'];
	$fen = $_SESSION['fen'];
	$prossima_mossa_da = $_SESSION['prossima_mossa_da'];
	$prossima_mossa_a = $_SESSION['prossima_mossa_a'];
	$stato_partita = $_SESSION['stato_partita'];
	$pezzomangiato = $_SESSION['pezzomangiato'];


	//Mi collego al DB per eseguire gli aggiornamenti
	$conn = collegaDb();
	if (!$conn) {//Significa che non sono riuscito a collegarmi al db
        echo "<p>Problemi nella connessione al db</p>";
	    exit;
	}
	

	/* Inserisco la nuova mossa
	 * Esempio query:
	 * INSERT INTO mosse(id_partita, data, mossa_da, mossa_a)
	 * VALUES('2', '2007-07-26 22:07:07', 'b8', 'a6')
	 * TODO: controllare che la data sia sulla mia timezone CEST
	 */
	$now = date("Y-m-d H:i:s"); //per stampare anche la Timezone: d-m-Y H:i:s T
	$query = 	"INSERT INTO mosse(id_partita, data, mossa_da, mossa_a, pezzomangiato)
				VALUES('$id_partita', '$now', '$prossima_mossa_da', '$prossima_mossa_a', '$pezzomangiato')";
	$result = $conn->query($query);
	if (!$result) {
		throw new Exception("Problemi nell'inserimento della nuova mossa");
	}

	    
	/* Aggiorno il fen della partita
	 * Esempio query:
	 * UPDATE partite
	 * SET fen='rnbqkbnr/ppp1pppp/8/3p4/2P5/8/PP1PPPPP/RNBQKBNR w KQkq d6 1 2'
	 * WHERE id='2'
	 */
	//Controllo lo stato della partita, xkè se è conclusa devo aggiornare anche il campo stato
	if ( strcasecmp($stato_partita, "c")!=0 ) { //Se lo stato della partita è diverso da in corso, quindi è cambiato
		$query_stato = ", stato='$stato_partita'";
	} else {
		$query_stato = "";
	}
	
	$query = 	"UPDATE partite
				SET fen='$fen'$query_stato
				WHERE id='$id_partita'";
	$result = $conn->query($query);
	if (!$result) {
		throw new Exception("Problemi nell'aggiornamento dello stato della partita");
	}





	//Ho aggiornato il DB con la mossa corretta quindi salvo in sessione la prossima mossa come ultima mossa e elimino la prossima mossa
	$_SESSION['ultima_mossa_da'] = $prossima_mossa_da;
	$_SESSION['ultima_mossa_a'] = $prossima_mossa_a;
	unset($_SESSION['prossima_mossa_da']);
	unset($_SESSION['prossima_mossa_a']);
	unset($_SESSION['pezzomangiato']);
	$_SESSION['msgListaPezziMangiati'] = ''; //Se la elimino, dopo la conferma viene stampata ancora qs pagina ma la variabile non c'è +
	
	
}

















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
			$restituisci = $restituisci . "[" . $conteggioPedineMangiatePerPezzo[$val] . "] " . nomePezzo($val, false) . ", ";
		}
	}
	
	$restituisci = substr($restituisci, 0, count($restituisci)-3); //Taglio via la virgola e lo spazio finali
	return $restituisci;
	
}





/* Questa funz converta la lettera identif del pezzo nel nome in bergamasco
 * E' case insensitive
 */
function nomePezzo($pezzo, $articolo) {
	if ( strcasecmp($pezzo, "p")==0 )
		return $articolo ? "ol pedu" : "pedu";
	else if ( strcasecmp($pezzo, "r")==0 )
		return $articolo ? "la tor" : "tor";
	else if ( strcasecmp($pezzo, "b")==0 )
		return $articolo ? "l'alfer" : "alfer";
	else if ( strcasecmp($pezzo, "n")==0 )
		return $articolo ? "ol caal" : "caal";
	else if ( strcasecmp($pezzo, "q")==0 )
		return $articolo ? "la figheta" : "figheta";
	else if ( strcasecmp($pezzo, "k")==0 )
		return $articolo ? "ol re" : "re";
	else
		return "Bho";
}


  
?>