<?php
if ( ! defined( 'ENJINMEL_SMTP_KEY' ) ) {
    define( 'ENJINMEL_SMTP_KEY', 'unit-test-key' );
}

if ( ! defined( 'ENJINMEL_SMTP_IV' ) ) {
    define( 'ENJINMEL_SMTP_IV', 'unit-test-iv' );
}

require_once dirname( __FILE__ ) . '/../../includes/class-enjinmel-smtp-encryption.php';

class Test_Encryption extends WP_UnitTestCase {

    public function test_encrypt_decrypt() {
        $plain_text = 'this is a test password';
        $encrypted = EnjinMel_SMTP_Encryption::encrypt( $plain_text );
        $decrypted = EnjinMel_SMTP_Encryption::decrypt( $encrypted );

        $this->assertNotEquals( $plain_text, $encrypted );
        $this->assertEquals( $plain_text, $decrypted );
    }
}
