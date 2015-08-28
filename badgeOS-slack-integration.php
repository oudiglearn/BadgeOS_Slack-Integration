<?php
    /**
     * Plugin Name: BadgeOS Slack Integration
     * Description: Send notifications of new BadgeOS awards to a Slack channel.
     * Author: John Stewart
     * Author URI: http://johnastewart.org
     * Version: 0.1.0
     * Plugin URI:
     * License: GNU GPLv2+
     */
    /**
     * Copyright (c) 2015 John Stewart (email : stewart.ja@gmail.com)
     *
     * This program is free software; you can redistribute it and/or modify
     * it under the terms of the GNU General Public License, version 2 or, at
     * your discretion, any later version, as published by the Free
     * Software Foundation.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program; if not, write to the Free Software
     * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
     */
    
    add_action( 'badgeos_award_achievement',  'badgeOS_slack_integration', 20, 2 );
    function badgeOS_slack_integration(  $user_id, $achievement_id ) {
        $url = get_option( 'badgeOS_slack_webhook', false );
        if ( $url ) {
            $user = get_user_by( 'id', $user_id );
            $name = $user->user_firstname;
            
            $post = get_post( $achievement_id );
            $achievement = $post->post_title;
            $congratulations = $post->post_content;
            
            $awardtext = $name . ' has been awarded the ' . $achievement . ' badge';
            
            $link = get_permalink( $achievement_id );
            $link = htmlspecialchars( $link );
            
            
            $excerpt = wp_trim_words( $congratulations );
            if ( 500 < strlen( $excerpt ) ) {
                $excerpt = substr( $excerpt, 0, 500 );
            }
            
            $payload = array(
                             'text'        => __( $awardtext, 'badgeOS-slack' ),
                             'attachments' => array(
                                                    'fallback' => $link,
                                                    'color'    => '#ff000',
                                                    'fields'   => array(
                                                                        'title' => $link,
                                                                        'value' => $link,
                                                                        'text'  => $excerpt,
                                                                        )
                                                    ),
                             );
            $output  = 'payload=' . json_encode( $payload );
            
            $response = wp_remote_post( $url, array(
                                                    'body' => $output,
                                                    ) );
            
            /**
             * Runs after the data is sent.
             *
             * @param array $response Response from server.
             *
             * @since 0.1.0
             */
            do_action( 'badgeOS_slack_integration_post_send', $response );
            
        }
        
    }
    
    /**
     * Load admin class if admin
     *
     * @since 0.1.0
     */
    if ( is_admin() ) {
        new badgeOS_slack_integration_admin();
    }
    
    class badgeOS_slack_integration_admin {
        
        private $option_name = 'badgeOS_slack_webhook';
        private $nonce_name = '_badgeOS_slack_nonce';
        private $nonce_action = '_badgeOS_slack_nonce_action';
        
        function __construct() {
            add_action( 'admin_menu', array( $this, 'menu' ) );
        }
        
        /**
         * Add the menu
         *
         * @since 0.1.0
         */
        function menu() {
            add_options_page(
                             __( 'badgeOS Slack Integration', 'badgeOS-slack' ),
                             __( 'badgeOS Slack Integration', 'badgeOS-slack' ),
                             'manage_options',
                             'badgeOS_slack',
                             array( $this, 'page' )
                             );
        }
        
        /**
         * Render admin page and handle saving.
         *
         * @since 0.1.0
         *
         * @return string the admin page.
         */
        function page() {
            echo $this->instructions();
            echo $this->form();
            if ( isset( $_POST ) && isset( $_POST[ $this->nonce_name ] ) && wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) {
                if ( isset( $_POST[ 'slack-hook' ] )) {
                    $option = esc_url_raw( $_POST[ 'slack-hook' ] );
                    $option = filter_var( $option, FILTER_VALIDATE_URL );
                    if ( $option ) {
                        update_option( $this->option_name, $option );
                        if ( isset( $_POST[ '_wp_http_referer' ] ) && $_POST[ '_wp_http_referer' ] ) {
                            $location = $_POST['_wp_http_referer'];
                            die( '<script type="text/javascript">'
                                . 'document.location = "' . str_replace( '&amp;', '&', esc_js( $location ) ) . '";'
                                . '</script>' );
                        }
                    }
                }
                
            }
            
        }
        
        /**
         * Admin form
         *
         * @since 0.1.0
         *
         * @return string The form.
         */
        function form() {
            $out[] = '<form id="badgeOS_slack_integration" method="POST" action="options-general.php?page=badgeOS_slack">';
            $out[] = wp_nonce_field( $this->nonce_action, $this->nonce_name, true, false );
            $url = get_option( $this->option_name, '' );
            $out[] = '<input id="slack-hook" name="slack-hook"type="text" value="'.esc_url( $url ).'"></input>';
            $out[] = '<input type="submit" class="button-primary">';
            $out[] = '</form>';
            
            return implode( $out );
            
        }
        
        /**
         * Show instructions.
         *
         * @since 0.1.0
         *
         * @return string The instructions.
         */
        function instructions() {
            $header = '<h3>' . __( 'Instructions:', 'badgeOS-slack' ) .'</h3>';
            $instructions = array(
                                  __( 'Go To https://<your-team-name>.slack.com/services/new/incoming-webhook', 'badgeOS-slack' ),
                                  __( ' Create a new webhook', 'badgeOS-slack' ),
                                  __( 'Set a channel to receive the notifications', 'badgeOS-slack' ),
                                  __( 'Copy the URL for the webhook	', 'badgeOS-slack' ),
                                  __( 'Past the URL into the field below and click submit', 'badgeOS-slack' ),
                                  );
            
            return $header. "<ol><li>" .implode( "</li><li>", $instructions ) . "</li></ol>";
            
        }
    
    }