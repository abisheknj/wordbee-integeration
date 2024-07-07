<?php

function encrypt_token( $token ) {
    // Replace 'your_encryption_key' with a secure encryption key
    $encryption_key = 'a5e50f8048d715f8d0b22e0477f714fb';
    // Encrypt the token using AES-256-CBC encryption
    $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
    $encrypted_token = openssl_encrypt( $token, 'aes-128-cbc', $encryption_key, 0, $iv );

    // Encode the encrypted token and IV as base64
    $encrypted_data = base64_encode( $encrypted_token . '::' . $iv );

    return $encrypted_data;
}


// Function to decrypt the token
function decrypt_token( $encrypted_data ) {
    // Replace 'your_encryption_key' with the same encryption key used for encryption
    $encryption_key = 'a5e50f8048d715f8d0b22e0477f714fb';
    // Decode the encrypted data from base64
    $decoded_data = base64_decode( $encrypted_data );

    // Check if the decoded data contains the separator '::'
    if (strpos($decoded_data, '::') !== false) {
        // Extract the encrypted token and IV
        list( $encrypted_token, $iv ) = explode( '::', $decoded_data );

        // Decrypt the token using AES-128-CBC decryption (matching the encryption cipher)
        $decrypted_token = openssl_decrypt( $encrypted_token, 'aes-128-cbc', $encryption_key, 0, $iv );

        return $decrypted_token;
    } else {
        // Handle the case where the separator '::' is not found in the decoded data
        return false;
    }
}


