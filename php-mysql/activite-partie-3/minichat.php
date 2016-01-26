<?php session_start(); ?>
<!DOCTYPE html>
<?php $pseudo = isset($_SESSION['pseudo']) ? $_SESSION['pseudo'] : ''; ?>
<html>
    <head>
        <meta charset="utf-8" />
        <title>TP - Minichat</title>
        <link rel="stylesheet" href="style.css" />
    </head>
    <body>
        <p><a href="minichat.php">Rafraîchir la page</a></p>

        <form action="minichat_post.php" method="post">
            <p>
                <label for="pseudo">Pseudo</label> : <input type="text" name="pseudo" id="pseudo" required value="<?php echo $pseudo; ?>" /><br />
                <label for="message">Message</label> :  <input type="text" name="message" id="message" required /><br />
                <input type="submit" value="Envoyer" />
    	   </p>
        </form>

        <?php

        try {
            // Connexion à la base de données
			include_once('bdd.php');
        }
        catch(Exception $e) {
                die('Erreur : '.$e->getMessage());
        }

        $reponse = $bdd->query('SELECT pseudo, message, dateheure FROM minichat ORDER BY dateheure DESC');

        // Si la requête retourne des résultats alors on affiche les commentaires sinon on affiche un message indiquant qu'il n'y a pas de commmentaire.
        if ($reponse->rowCount() > 0) {
            echo '<div id="commentaires">';

            while ($donnees = $reponse->fetch()) {
                // Formatage du timestamp en date et heure lisible (format français)
                $date = date( 'd/m/Y', $donnees['dateheure'] );
                $heure = date( 'H:i:s', $donnees['dateheure'] );

            	echo '<p><span class="timestamp">Posté le ' . $date . ' à ' . $heure . '</span><strong>' . htmlspecialchars($donnees['pseudo']) . '</strong> : ' . htmlspecialchars($donnees['message']) . '</p>';
            }
        } else {
            echo '<p class="vide">Aucun commentaire</p>';
        }
        echo '</div>';

        // Fermeture de la connexion à la base de données
        $reponse->closeCursor();
        ?>
    </body>
</html>