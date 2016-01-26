<?php
session_start();

//  Vérifie si les variables passées en POST sont définies
if ( isset($_POST['pseudo']) && isset($_POST['message']) ) {
	// Vérifie que les variables passées en POST ne sont pas vides et ont une taille inférieure ou égale à 255 caractères
	if ( $_POST['pseudo'] != '' && $_POST['message'] != '' && strlen($_POST['pseudo']) <= 255 && strlen($_POST['message'] <= 255) ) {
		$_SESSION['pseudo'] = $_POST['pseudo'];

		try {
			// Connexion à la base de données
			include_once('bdd.php');
		}
		catch(Exception $e) {
		        die('Erreur : ' . $e->getMessage());
		}
		// Préparation de la requête
		$req = $bdd->prepare('INSERT INTO minichat (pseudo, message, dateheure) VALUES(?, ?, ?)');
		//Exécution de la requête
		$req->execute( array( $_POST['pseudo'], $_POST['message'], time() ) );
	}
}

// Redirection vers le chat
header('Location: minichat.php');
?>