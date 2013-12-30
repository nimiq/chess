<?php

/**
 * @author nimiq
 * @copyright 2007
 */

require_once 'funzioni_fns.php';

//Avvio una nuova sessione
session_start();

$page = new HTML_Page2();
$page->setTitle('Nuova partita');

$page->addBodyContent("<B>Fen: </B>" . $_SESSION['fen']);
$page->addBodyContent("<BR><a href='disegna.php'>&lt Indietro</a>");

$page->setBodyAttributes('style="font-size: 10pt;" topmargin="3" leftmargin="2" rightmargin="0" bottommargin="0"');
$page->display();

?>