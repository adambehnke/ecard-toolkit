<?php
    namespace eCard;

    class Order_Token {
        private $order_id;
        private $order_token;

        public function __construct(int $order_id) {
            $this->order_id = $order_id;

            if ($this->order_id !== 0) {
                $this->order_token = $this->generate();
            }
        }
        
        public function get() {
            return $this->order_token;
        }

        public function get_order_id() {
            return $this->order_id;
        }

        private function generate() {
            return Simple_Crypt::encrypt($this->order_id, true);
        }
    }