<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User_model extends Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_user($id)
    {
        $stmt = $this->db->raw(
            "SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1",
            [$id]
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get_user_by_email($email)
    {
        $stmt = $this->db->raw(
            "SELECT id, username, email, password, role, is_active FROM users WHERE email = ? LIMIT 1",
            [$email]
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get_user_by_username($username)
    {
        $stmt = $this->db->raw(
            "SELECT id FROM users WHERE username = ? LIMIT 1",
            [$username]
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create_user($username, $email, $password_hash)
    {
        $this->db->raw(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)",
            [$username, $email, $password_hash]
        );
        return (int) $this->db->last_id();
    }

    public function delete_refresh_tokens($user_id)
    {
        $this->db->raw("DELETE FROM refresh_tokens WHERE user_id = ?", [$user_id]);
    }
}