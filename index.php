<?php

/**
 * @author nimiq
 * @copyright 2007
 */


require_once 'funzioni_fns.php';


//Avvio una nuova sessione o richiamo quella già esistente
session_start();

//Controllo se nella sessione (nuova o richiamata) è settatato o no il campo userid
if (isset($_SESSION['userid'])) {//Se il campo userid in sessione è già settato allora l'utente ha già fatto il login    {
    /* L'utente c'è e l'ho già riconosciuto (cioè ha fatto il login), ma ha cliccato su qualche link che l'ha ricondotto qui
    * Questo caso non dovrebbe mai verificarsi perchè:
    *  - adotto un meccanismo di gestione delle sessioni non tramite cookie ma tramite url
    *  - quindi se l'utente digita direttamente l'indirizzo di qs pagina la sua sessione viene persa e creata una nuova
    *  - in tutto il sito non prevedo un link per il ritorno qui
    * In ogni caso l'operazione da fare è svuotare tutte le variabili che ho memorizzato in sessione tranne l'userid
    */

    //Memorizzo l'userid
    $userid = $_SESSION['userid'];

    //Cancello tutti i dati memorizzati in sessione
    session_unset();

    //Rimetto in sessione l'userid
    $_SESSION['userid'] = $userid;

    //Stampo la risposta
    echo "<p>Ciao $userid!!</p>";
    echo "<a href='partite.php'>Vai alla lista delle tue partite</a>";


} else {
	/* Se il campo userid in sessione non è settato ci sono 2 possibilita
    *  //////////////////- o l'utente ha raggiunto questa pagina dalla form di login: devo quindi estrarre dal POST le variabili userid e pwd
    *  - o l'utente è appena arrivato qui digitando l'indirizzo web e devo presentargli la form di login
    */
    /*if (isset($_POST['userid']) && isset($_POST['pwd'])) //Se c'è il campo userid nel POST allora l'utente è giunto qui dalla form di login
    {
    $userid = $_POST['userid'];
    $pwd = $_POST['pwd'];
    
    //Mi collego al db mysql
    $conn = new mysqli("localhost", "nimiq", "wordpass", "chess");

    //Cerco sul db l'utente e la password
    $query = "SELECT userid, pwd FROM utenti WHERE userid='$userid' AND pwd='$pwd'";
    $result = $conn->query($query);
    
    if ($result->num_rows == 0) //Se invece nei risultati del db NON trovo alcna corrispondenza, allora significa che nome utente e password sono errate
    {
    echo "Nome utente o password errati";	
    } else if ($result->num_rows > 0)//Se invece nei risultati del db trovo una corrispondenza
    {
    //Metto il campo userid in sessione e stampo un messaggio di benvenuto
    $_SESSION['userid'] = $userid; //mysql_result($result, 0, "userid");
    echo "Benvenuto, $userid!!";
    }
    
    } else { //Se NON c'è il campo userid nel POST allora l'utente è appena arrivato qui digitando l'indirizzo web e devo presentargli la form di login
    */
    session_unset();
    session_destroy();
    include "login.html";
    exit;	
    //}

}

//echo "<br><a href='index.php'>Home</a>";
//echo "<HR>I dati che hai in sessione " . session_id() . " sono: " . session_encode();


?>