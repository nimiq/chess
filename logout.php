<?php

/**
 * @author nimiq
 * @copyright 2007
 */

require_once 'funzioni_fns.php';

session_start();

//Distruggo tutte le var in sessione
session_unset();

//Distruggo la sessione
session_destroy();

//Rimando al login
include "login.html";
exit;

?>