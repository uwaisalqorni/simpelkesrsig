<?php
require_once __DIR__ . '/TestCase.php';

class AuthTest extends TestCase {

    public function test_login_success_admin() {
        $res = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);

        $this->assertEquals(200, $res['code'], 'Login admin harus mengembalikan status HTTP 200');
        $this->assertTrue($res['body']['success'], 'Response success harus true');
        $this->assertArrayHasKey('token', $res['body']['data'], 'Response harus memuat token JWT');
        $this->assertEquals('admin', $res['body']['data']['user']['role'], 'Role pengguna harus admin');
        $this->assertEquals(1, $res['body']['data']['user']['tenant_id'], 'Tenant ID admin harus 1 (RSIG)');
    }

    public function test_login_success_super_admin() {
        $res = $this->api('POST', '/auth/login', [
            'username' => 'superadmin',
            'password' => 'password123'
        ]);

        $this->assertEquals(200, $res['code'], 'Login superadmin harus mengembalikan status HTTP 200');
        $this->assertTrue($res['body']['success'], 'Response success harus true');
        $this->assertEquals('super_admin', $res['body']['data']['user']['role'], 'Role pengguna harus super_admin');
        $this->assertNull($res['body']['data']['user']['tenant_id'], 'Tenant ID superadmin harus NULL (Global Holding)');
    }

    public function test_login_invalid_password() {
        $res = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'passwordsalah123'
        ]);

        $this->assertEquals(401, $res['code'], 'Password salah harus mengembalikan status HTTP 401');
        $this->assertFalse($res['body']['success'], 'Response success harus false');
        $this->assertContains('salah', strtolower($res['body']['message']), 'Pesan error harus menginformasikan password salah');
    }

    public function test_login_unknown_username() {
        $res = $this->api('POST', '/auth/login', [
            'username' => 'user_yang_tidak_ada_xyz',
            'password' => 'password123'
        ]);

        $this->assertEquals(401, $res['code'], 'Username tidak terdaftar harus mengembalikan status HTTP 401');
        $this->assertFalse($res['body']['success'], 'Response success harus false');
    }

    public function test_auth_me_with_valid_token() {
        // 1. Login dulu untuk dapatkan token
        $login = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $login['body']['data']['token'];

        // 2. Akses endpoint me
        $me = $this->api('GET', '/auth/me', null, $token);
        $this->assertEquals(200, $me['code'], 'GET /auth/me dengan token valid harus HTTP 200');
        $this->assertEquals('admin', $me['body']['data']['username'], 'Data me harus memuat username yang sesuai');
    }

    public function test_auth_me_unauthorized_without_token() {
        $me = $this->api('GET', '/auth/me');
        $this->assertEquals(401, $me['code'], 'GET /auth/me tanpa token harus ditolak dengan HTTP 401');
    }

    public function test_logout_endpoint() {
        $login = $this->api('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'password123'
        ]);
        $token = $login['body']['data']['token'];

        $logout = $this->api('POST', '/auth/logout', [], $token);
        $this->assertEquals(200, $logout['code'], 'POST /auth/logout harus mengembalikan HTTP 200');
        $this->assertTrue($logout['body']['success']);
    }
}
