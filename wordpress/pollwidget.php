<?php

class Poll_Widget extends WP_Widget
{
    public function __construct() {
        parent::__construct('Poll', 'Vote', array('description' => __('Formulaire de vote')));
    }

    // Affichage du widget
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'];
        echo apply_filters('widget_title', $instance['title']);
        echo $args['after_title'];

        echo '<form method="post">';
        echo '<p>' . stripslashes(get_option('poll_question')) . '</p>';

        // Si l'utilisateur n'a jamais voté : on affiche le sondage
        if ( !isset($_COOKIE['vote']) ) {
            $options = Poll_Plugin::get_poll_options();

            if ($options) {
                foreach ($options as $id=>$label) {
                    echo '<input type="radio" name="sondage" value="' . $id . '" />' . $label . '<br />';
                }
        		echo '<input type="submit" value="Envoyer" />';
            } else {
                echo '<p>Aucune réponse à afficher</p>';
            }
        // Si l'utilisateur a déjà voté : on affiche les résultats des votes
        } else {
            $votes = Poll_Plugin::get_votes();

            if ($votes) {
                echo '<p>Résultats :</p>';

                foreach ($votes as $label=>$total) {
                    echo '<p>' . $label . ' : ' . $total . ' vote(s)</p>';
                }

            } else {
                echo '<p>Aucune réponse à afficher</p>';
            }
        }

        echo '</form>';
        echo $args['after_widget'];
    }

    // Formulaire permettant de modifier le titre du widget depuis l'administration
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Sondage';
        ?>
        <p>
            <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo  $title; ?>" />
        </p>
        <?php
    }
}
