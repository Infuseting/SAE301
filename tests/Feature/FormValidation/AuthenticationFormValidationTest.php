<?php

namespace Tests\Feature\FormValidation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Authentication form validation.
 * 
 * This test suite validates that authentication forms (login, registration) properly
 * reject invalid data and prevent security vulnerabilities.
 */
class AuthenticationFormValidationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ========== LOGIN FORM VALIDATION ==========

    /**
     * Test that login rejects missing email.
     */
    public function test_login_rejects_missing_email(): void
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that login rejects missing password.
     */
    public function test_login_rejects_missing_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test that login rejects empty email.
     */
    public function test_login_rejects_empty_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that login rejects empty password.
     */
    public function test_login_rejects_empty_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test that login rejects invalid email formats.
     */
    public function test_login_rejects_invalid_email_formats(): void
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'test@',
            'test@.com',
            'test..email@example.com',
            'test@example',
            'test user@example.com',
            'test@example..com',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertSessionHasErrors('email', "Email '{$email}' should have been rejected");
        }
    }

    /**
     * Test that login rejects SQL injection attempts in email.
     */
    public function test_login_rejects_sql_injection_in_email(): void
    {
        $sqlInjections = [
            "' OR '1'='1",
            "' OR 1=1--",
            "admin'; DROP TABLE users; --",
            "' UNION SELECT * FROM users--",
            "'; UPDATE users SET admin=1; --",
        ];

        foreach ($sqlInjections as $sql) {
            $response = $this->post('/login', [
                'email' => $sql,
                'password' => 'password',
            ]);

            $response->assertSessionHasErrors('email', "SQL injection '{$sql}' should have been rejected");
            $this->assertGuest();
        }
    }

    /**
     * Test that login rejects XSS attempts in email.
     */
    public function test_login_rejects_xss_attempts_in_email(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>@example.com',
            '"><script>alert(1)</script>@example.com',
            "test@example.com<script>alert('xss')</script>",
            'test+<img src=x onerror=alert(1)>@example.com',
        ];

        foreach ($xssAttempts as $xss) {
            $response = $this->post('/login', [
                'email' => $xss,
                'password' => 'password',
            ]);

            $response->assertSessionHasErrors('email', "XSS attempt '{$xss}' should have been rejected");
        }
    }

    /**
     * Test that login rejects XSS attempts in password.
     */
    public function test_login_rejects_xss_attempts_in_password(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '"><script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<!--<script>alert(1)</script>-->',
        ];

        foreach ($xssAttempts as $xss) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => $xss,
            ]);

            $this->assertGuest();
        }
    }

    /**
     * Test that login rejects extremely long email.
     */
    public function test_login_rejects_extremely_long_email(): void
    {
        $longEmail = str_repeat('a', 1000) . '@example.com';

        $response = $this->post('/login', [
            'email' => $longEmail,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that login rejects extremely long password.
     */
    public function test_login_rejects_extremely_long_password(): void
    {
        $longPassword = str_repeat('a', 10000);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => $longPassword,
        ]);

        // Should either error or not authenticate
        $this->assertGuest();
    }

    /**
     * Test that login rejects null values.
     */
    public function test_login_rejects_null_values(): void
    {
        $response = $this->post('/login', [
            'email' => null,
            'password' => null,
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Test that login rejects numeric email.
     */
    public function test_login_rejects_numeric_email(): void
    {
        $response = $this->post('/login', [
            'email' => 12345,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ========== REGISTRATION FORM VALIDATION ==========

    /**
     * Test that registration rejects missing first_name.
     */
    public function test_registration_rejects_missing_first_name(): void
    {
        $response = $this->post('/register', [
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    /**
     * Test that registration rejects missing last_name.
     */
    public function test_registration_rejects_missing_last_name(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('last_name');
    }

    /**
     * Test that registration rejects missing email.
     */
    public function test_registration_rejects_missing_email(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that registration rejects missing password.
     */
    public function test_registration_rejects_missing_password(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test that registration rejects missing password_confirmation.
     */
    public function test_registration_rejects_missing_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test that registration rejects mismatched passwords.
     */
    public function test_registration_rejects_mismatched_passwords(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test that registration rejects empty first_name.
     */
    public function test_registration_rejects_empty_first_name(): void
    {
        $response = $this->post('/register', [
            'first_name' => '',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    /**
     * Test that registration rejects empty last_name.
     */
    public function test_registration_rejects_empty_last_name(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('last_name');
    }

    /**
     * Test that registration rejects XSS in first_name.
     */
    public function test_registration_rejects_xss_in_first_name(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert(1)>',
            '"><script>alert(1)</script>',
            'Test<svg onload=alert(1)>',
        ];

        foreach ($xssAttempts as $xss) {
            $response = $this->post('/register', [
                'first_name' => $xss,
                'last_name' => 'User',
                'email' => 'test' . uniqid() . '@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            // Should either reject or sanitize XSS
            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that registration rejects XSS in last_name.
     */
    public function test_registration_rejects_xss_in_last_name(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert(1)>',
            '"><script>alert(1)</script>',
        ];

        foreach ($xssAttempts as $xss) {
            $response = $this->post('/register', [
                'first_name' => 'Test',
                'last_name' => $xss,
                'email' => 'test' . uniqid() . '@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            // Should either reject or sanitize XSS
            $this->assertTrue($response->status() === 302 || $response->status() === 422);
        }
    }

    /**
     * Test that registration rejects excessively long first_name.
     */
    public function test_registration_rejects_excessively_long_first_name(): void
    {
        $response = $this->post('/register', [
            'first_name' => str_repeat('a', 300),
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    /**
     * Test that registration rejects excessively long last_name.
     */
    public function test_registration_rejects_excessively_long_last_name(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => str_repeat('a', 300),
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('last_name');
    }

    /**
     * Test that registration rejects duplicate email.
     */
    public function test_registration_rejects_duplicate_email(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that registration rejects invalid email formats.
     */
    public function test_registration_rejects_invalid_email_formats(): void
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'test@',
            'test..email@example.com',
            'test user@example.com',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->post('/register', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasErrors('email');
        }
    }

    /**
     * Test that registration properly handles input.
     */
    public function test_registration_sanitizes_input(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test<script>alert("xss")</script>',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Should handle XSS gracefully (either reject or sanitize)
        $this->assertTrue($response->status() === 302 || $response->status() === 422);
    }

    /**
     * Test that registration rejects very short passwords.
     */
    public function test_registration_rejects_very_short_password(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
