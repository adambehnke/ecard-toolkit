<?php
    namespace eCard;

    class Order_Token_Manager {
        public function __construct() {
            global $wpdb;
            
            $this->table = $wpdb->prefix . 'orders';
        }

        public function persist(Order_Token $token) {
            global $wpdb;

            $query_result = $wpdb->update( 
                $this->table, 
                array( 
                    'token' => $token->get(),   // string
                ), 
                array( 'post_id' => $token->get_order_id() ), 
                array( 
                    '%s', 
                ), 
                array( '%d' ) 
            );

            return $query_result;
        }

        public function get_from_db(int $order_id) {
            global $wpdb;

            $query = "SELECT token FROM $this->table WHERE post_id = %d";
            $row = $wpdb->get_row($wpdb->prepare($query, $order_id), ARRAY_A);

            if (isset($row['token'])) {
                return $row['token'];
            }

            return '';
        }

        public static function get_order_id_from_db(string $token_string) {
            global $wpdb;

            $table = $wpdb->prefix . 'orders';

            $query = "SELECT post_id FROM $table WHERE token = %s";
            $row = $wpdb->get_row($wpdb->prepare($query, $token_string), ARRAY_A);

            if (isset($row['post_id'])) {
                return $row['post_id'];
            }

            return '';
        }
    }