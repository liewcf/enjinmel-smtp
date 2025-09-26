<?php
if ( ! defined( 'ENGINEMAIL_SMTP_KEY' ) ) {
    define( 'ENGINEMAIL_SMTP_KEY', 'unit-test-key' );
}

if ( ! defined( 'ENGINEMAIL_SMTP_IV' ) ) {
    define( 'ENGINEMAIL_SMTP_IV', 'unit-test-iv' );
}

require_once dirname( __FILE__ ) . '/../../includes/class-encryption.php';

class Test_Encryption extends WP_UnitTestCase {

    public function test_encrypt_decrypt() {
        $plain_text = 'this is a test password';
        $encrypted = EngineMail_SMTP_Encryption::encrypt( $plain_text );
        $decrypted = EngineMail_SMTP_Encryption::decrypt( $encrypted );

        $this->assertNotEquals( $plain_text, $encrypted );
        $this->assertEquals( $plain_text, $decrypted );
    }
}
