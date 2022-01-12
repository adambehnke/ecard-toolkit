<?php
    namespace eCard;

    class Simple_Crypt {
        private static $key = 'bCa3$fhmalkIpA_dsDmd5#eCvJ6B';
        private static $salt = 'iPoAdf3_1482GhHJ#nyPmrZal6!vTbVBkLPa2gbD3f1klOpaAfuN3#bkm_dN';
        private static $encoding_replacements = array(
            '+' => 'p',
            '/' => 's',
            '=' => 'e',
        );

        public static function encrypt($string, $url_encode, $random_key = true) {
            $result = '';
            $salted_string = $string . self::$salt;

            if ($random_key) {
                $key = bin2hex(random_bytes(128));
            } else {
                $key = self::$key;
            }
            
            for ($i = 0; $i < strlen($salted_string); $i++) {
                $char = substr($salted_string, $i, 1);
                $keychar = substr($key, ($i % strlen($key))-1, 1);
                $char = chr(ord($char) + ord($keychar));
                $result .= $char;
            }

            if ($url_encode) {
                return self::url_string_parse(base64_encode($result));
            }
            
            return base64_encode($result);
        }

        /*
        public static function decrypt($encoded_string, $url_is_encoded) {
            $result = '';

            if ($url_is_encoded) {
                $decoded_string = self::url_string_parse($encoded_string, 'decrypt');
            }

            $salted_string = base64_decode($decoded_string);
            
            for ($i = 0; $i < strlen($salted_string); $i++) {
                $char = substr($salted_string, $i, 1);
                $keychar = substr(self::$key, ($i % strlen(self::$key))-1, 1);
                $char = chr(ord($char) - ord($keychar));
                $result .= $char;
            }

            $result = str_replace(self::$salt, '', $result);
            
            return $result;
        }
        */

        private static function url_string_parse($string, $action = 'encrypt') {
            foreach (self::$encoding_replacements as $replacement_key => $replacement_value) {
                if ($action === 'encrypt') {
                    $string = str_replace($replacement_key, $replacement_value, $string);
                } elseif ($action === 'decrypt') {
                    $string = str_replace($replacement_value, $replacement_key, $string);
                }                
            }

            return $string;
        }

        /*
        public function stringEncrypt($plainText, $cryptKey = '7R7zX2Urc7qvjhkr') {

            $length   = 8;
            $cstrong  = true;
            $cipher   = 'aes-128-ctr';
          
            if (in_array($cipher, openssl_get_cipher_methods())) {
                $ivlen = openssl_cipher_iv_length($cipher);
                $iv = openssl_random_pseudo_bytes($ivlen);
                $ciphertext_raw = openssl_encrypt(
                    $plainText, $cipher, $cryptKey, OPENSSL_RAW_DATA, $iv
                );
                $hmac = hash_hmac('sha256', $ciphertext_raw . $iv, $cryptKey, $as_binary=true);
                $encodedText = base64_encode( $hmac.$ciphertext_raw );
            }
          
            return $encodedText;
          }
          
          public function stringDecrypt($encodedText, $cryptKey = '7R7zX2Urc7qvjhkr') {          
            $c      = base64_decode($encodedText);
            $cipher = 'aes-128-ctr';
          
            if (in_array($cipher, openssl_get_cipher_methods())) {
                $ivlen = openssl_cipher_iv_length($cipher);
                $iv = substr($c, 0, $ivlen);
                $hmac = substr($c, $ivlen, $sha2len=32);
                $ivlenSha2len = $ivlen+$sha2len;
                $ciphertext_raw = substr($c, $ivlen+$sha2len);
                $plainText = openssl_decrypt(
                $ciphertext_raw, $cipher, $cryptKey, $options=OPENSSL_RAW_DATA, $iv);
            }
          
            return $plainText;
          }
          */
          
    }