<?php
/*
Plugin Name: Poll (OpenClassrooms)
Plugin URI: https://openclassrooms.com/courses/propulsez-votre-site-avec-wordpress/
Description: Plugin "Poll" pour le cours "Propulsez votre site avec WordPress" d'OpenClassroom.
Version: 1.0
 */
 include_once plugin_dir_path( __FILE__ ).'/pollwidget.php';

class Poll_Plugin
{
    public function __construct() {
        add_action('widgets_init', function(){register_widget('Poll_Widget');});

        register_activation_hook(__FILE__, array('Poll_Plugin', 'install'));
        register_uninstall_hook(__FILE__, array('Poll_Plugin', 'uninstall'));

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('wp_loaded', array($this, 'save_vote'));
    }

    // Action exécutées lors de l'installation du plugin
    static public function install() {
        global $wpdb;

        // Création des tables nécessaires au plugin
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'poll_options (id INT AUTO_INCREMENT PRIMARY KEY, label VARCHAR(255) NOT NULL);');
        $wpdb->query('CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'poll_results (option_id INT NOT NULL, total INT NOT NULL);');
    }

    // Action exécutées lors de la désinstallation du plugin
    static public function uninstall() {
        global $wpdb;

        // Suppression des tables installées par le plugin lors de son activation
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'poll_options;');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'poll_results;');

        // Suppression de la question stockée dans la table "options"
        delete_option('poll_question');
    }

    public function add_admin_menu() {
        $title = __('Sondage');
        // Déclaration du sous-menu dans l'espace d'administration et enregistrement de son identifiant
        $hook = add_submenu_page('options-general.php', $title, $title, 'manage_options', 'poll_admin', array($this, 'menu_html'));
        // Branchement de l'action réinitialisant le sondage
        add_action('load-' . $hook, array($this, 'reset_action'));
        // Branchement de l'action sauvegardant les données du formulaire
        add_action('load-' . $hook, array($this, 'save_action'));
    }

    // Contenu de la page d'administration du plugin
    public function menu_html() {
        global $wpdb;

        echo '<h1>' . get_admin_page_title() . '</h1>';
        ?>
        <form method="post" action="">
        <?php
            settings_fields('poll_settings');
            // Section comprenant les éléments du formulaire
            do_settings_sections('poll_settings');

            // Récupération des propositions de réponse en BDD
            $elements = $wpdb->get_results('SELECT id, label FROM ' . $wpdb->prefix . 'poll_options ORDER BY label;');

            if ($elements) {
                foreach ($elements as $element) {
                    echo '<input type="text" name="' . $element->id . '" value="' . $element->label . '" /><br />';
                }
            }

            submit_button(); // Bouton d'envoi du formulaire
        ?>
        </form>

        <form method="post" action="">
		    <input type="hidden" name="poll_reset" value="1"/>
		    <?php submit_button(__('Réinitialiser les options et les résultats')); ?>
		</form>
	    <?php
    }

    // Déclaration des sections et champs du formulaire
    public function register_settings() {
        global $wpdb;

        // Texte traduisible
        $subtitle = __('Paramètres du sondage');
        $question = __('Question');
        $newAnswer = __('Ajouter une nouvelle réponse');
        $answers = __('Réponses');

        // Création de la section
        add_settings_section('poll_section', $subtitle, array($this, 'section_html'), 'poll_settings');

        // Création des champs
        add_settings_field('poll_question', $question, array($this, 'poll_question_html'), 'poll_settings', 'poll_section');
        add_settings_field('poll_nouvelle_reponse', $newAnswer, array($this, 'poll_nouvelle_reponse_html'), 'poll_settings', 'poll_section');
        add_settings_field('poll_reponses', $answers, array($this, 'section_html'), 'poll_settings', 'poll_section');

        register_setting('poll_settings', 'poll_question');
        register_setting('poll_settings', 'poll_nouvelle_reponse');
        register_setting('poll_settings', 'poll_reponses');
    }

    // Rendu HTML de la section et des champs
    public function section_html() {}
    public function poll_question_html() { echo '<input type="text" name="poll_question" value="' . stripslashes(get_option('poll_question')) . '" size="50" required />'; }
    public function poll_nouvelle_reponse_html() { echo '<input type="text" name="poll_nouvelle_reponse" value="" />'; }
    public function poll_option_html($args) { echo '<input type="text" name="' . $args['id'] . '" value="' . $args['label'] . '" /><br />'; }

    // Actions exécutées lors du clic sur le bouton de réinitialisation du sondage
    public function reset_action() {
        global $wpdb;

        if ( isset($_POST['poll_reset']) ) {
            // Suppression de la question
            delete_option('poll_question');

            // Suppression de tous les enregistrements des tables "poll_options" et "poll_results"
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'poll_options;');
            $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'poll_results;');

            // Destruction du cookie pour que l'utilisateur puisse voter pour le nouveau sondage
            if ( isset($_COOKIE['vote']) ) {
                unset($_COOKIE['vote']);
            }
        }
    }

    // Sauvegarde de la saisie d'une nouvelle réponse au sondage et mise à jour des réponses existantes
    public function save_action() {
        global $wpdb;

        if ( isset($_POST['poll_question']) && $_POST['poll_question'] != '') {
            // Création / Mise à jour de la question
            update_option('poll_question', $_POST['poll_question']);

            foreach ($_POST as $key=>$value) {
                // Mise à jour de toutes les réponses déjà existantes
                if ($key != 'poll_question' && $key != 'poll_nouvelle_reponse' && $value != '') {
                    $wpdb->update(
                        $wpdb->prefix . 'poll_options',
                        array('label' => $value),
                        array( 'id' => $key ),
                        array('%s'),
                        array('%d')
                    );
                // Insertion de la nouvelle proposition de réponse
                } elseif ($key == 'poll_nouvelle_reponse' && $value != '') {
                    $wpdb->insert(
                        $wpdb->prefix . 'poll_options',
                        array(
                            'id' => '',
                            'label' => $value
                        ),
                        array('%d','%s')
                    );
                    // Récupération de l'identifiant généré par la BDD
                    $id = $wpdb->get_var( $wpdb->prepare(
                        'SELECT id FROM ' . $wpdb->prefix . 'poll_options WHERE label = %s',
                        $value
                    ));

                    // Initialisation du compteur à 0 pour la nouvelle proposition de réponse au sondage
                    $wpdb->insert(
                        $wpdb->prefix . 'poll_results',
                        array(
                            'option_id' => $id,
                            'total' => 0
                        ),
                        array('%d','%d')
                    );
                }
            }
        }
    }

    // Sauvegarde du vote de l'utilisateur
    public function save_vote() {
        global $wpdb;

        if ( isset($_POST['sondage']) && $_POST['sondage'] != '' && !isset($_COOKIE['vote']) ) {
            // Création d'un cookie pour garder une trace que l'utilisateur a déjà voté
            setcookie('vote', 'yes', time() + 365*24*3600);

            $votes = $wpdb->get_var( $wpdb->prepare(
                'SELECT total FROM ' . $wpdb->prefix . 'poll_results WHERE option_id = %d',
                $_POST['sondage']
            ));

            // Incrémente le compteur de vote de 1
            $votes++;

            // Enregistrement de la nouvelle valeur en BDD
            $wpdb->update(
                $wpdb->prefix . 'poll_results',
                array( 'total' => $votes ),
                array('option_id' => $_POST['sondage']),
                array('%d'),
                array('%d')
            );

            // Redirection pour forcer la mise à jour du formulaire
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    // Retourne un tableau contenant les propositions de réponse du sondage
    static public function get_poll_options() {
        global $wpdb;
        // Récupération des propositions de réponse en BDD
        $options = $wpdb->get_results('SELECT id, label FROM ' . $wpdb->prefix . 'poll_options ORDER BY label;');

        if (!empty($options)) {
            $listOptions = array();

            foreach ($options as $option) {
                $listOptions[$option->id] = $option->label;
            }
        } else {
            $listOptions = false;
        }

        return $listOptions;
    }

    // Retourne le nombre de votes pour chaque proposition de réponse au sondage
    static public function get_votes() {
        global $wpdb;

        $votes = $wpdb->get_results('
            SELECT label, total
            FROM ' . $wpdb->prefix . 'poll_options, ' . $wpdb->prefix . 'poll_results
            WHERE ' . $wpdb->prefix . 'poll_options.id = ' . $wpdb->prefix . 'poll_results.option_id
            ORDER BY total DESC
            LIMIT 0 , 30;');

        if (!empty($votes)) {
            $listVotes = array();

            foreach ($votes as $vote) {
                $listVotes[$vote->label] = $vote->total;
            }

        } else {
            $listVotes = false;
        }

        return $listVotes;
    }
}

new Poll_Plugin();