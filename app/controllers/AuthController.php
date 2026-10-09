<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('User_model');
    }

    // Answers the browser's CORS preflight (OPTIONS) request.
    public function preflight()
    {
        http_response_code(204);
        exit;
    }

    public function register()
    {
        $this->api->require_method('POST');
        $data = $this->api->body();

        $username = $data['username'] ?? '';
        $email    = strtolower($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $this->api->respond_error('Username must be 3-50 characters (letters, numbers, underscore).', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->api->respond_error('A valid email is required.', 422);
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $this->api->respond_error('Password must be 8-72 characters.', 422);
        }

        if ($this->User_model->get_user_by_email($email)) {
            $this->api->respond_error('Email is already registered.', 409);
        }
        if ($this->User_model->get_user_by_username($username)) {
            $this->api->respond_error('Username is already taken.', 409);
        }

        $id = $this->User_model->create_user($username, $email, password_hash($password, PASSWORD_DEFAULT));

        $this->api->respond([
            'message' => 'User registered successfully',
            'user'    => ['id' => $id, 'username' => $username, 'email' => $email, 'role' => 'user'],
        ], 201);
    }

    public function login()
    {
        $this->api->require_method('POST');
        $this->api->rate_limit('login_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 60);

        $data     = $this->api->body();
        $email    = strtolower($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->api->respond_error('Email and password are required.', 422);
        }

        $user = $this->User_model->get_user_by_email($email);

        if (!$user || !$user['is_active'] || !password_verify($password, $user['password'])) {
            $this->api->respond_error('Invalid email or password.', 401);
        }

        $tokens = $this->api->issue_tokens([
            'id'   => (int) $user['id'],
            'role' => $user['role'],
        ]);

        $this->api->respond([
            'message' => 'Login successful',
            'tokens'  => $tokens,
            'user'    => [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
        ]);
    }

    public function refresh()
    {
        $this->api->require_method('POST');
        $data  = $this->api->body();
        $token = $data['refresh_token'] ?? '';

        if ($token === '') {
            $this->api->respond_error('refresh_token is required.', 422);
        }

        $this->api->refresh_access_token($token);
    }

    public function logout()
    {
        $this->api->require_method('POST');
        $payload = $this->api->require_jwt();

        $this->User_model->delete_refresh_tokens((int) $payload['sub']);

        $this->api->respond(['message' => 'Logged out successfully']);
    }

    public function me()
    {
        $this->api->require_method('GET');
        $payload = $this->api->require_jwt();

        $user = $this->User_model->get_user((int) $payload['sub']);
        if (!$user) {
            $this->api->respond_error('User not found.', 404);
        }

        $this->api->respond(['user' => $user]);
    }
}