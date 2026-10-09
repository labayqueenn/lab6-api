<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Product_model extends Model {

    // Only these columns can ever be written from request data.
    private $product_columns = ['product_name', 'description', 'price', 'quantity'];

    public function __construct()
    {
        parent::__construct();
    }

    private function cast_row($row)
    {
        if (!$row) {
            return $row;
        }
        $row['id']       = (int) $row['id'];
        $row['price']    = (float) $row['price'];
        $row['quantity'] = (int) $row['quantity'];
        return $row;
    }

    public function all_products()
    {
        $stmt = $this->db->raw(
            "SELECT id, product_name, description, price, quantity, created_at FROM products ORDER BY id DESC"
        );
        return array_map([$this, 'cast_row'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find_product($id)
    {
        $stmt = $this->db->raw(
            "SELECT id, product_name, description, price, quantity, created_at FROM products WHERE id = ? LIMIT 1",
            [$id]
        );
        return $this->cast_row($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function create_product(array $fields)
    {
        $fields = array_intersect_key($fields, array_flip($this->product_columns));
        if (empty($fields)) {
            return 0;
        }

        $columns      = array_keys($fields);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $this->db->raw(
            "INSERT INTO products (" . implode(', ', $columns) . ") VALUES ($placeholders)",
            array_values($fields)
        );

        return (int) $this->db->last_id();
    }

    public function update_product($id, array $fields)
    {
        $fields = array_intersect_key($fields, array_flip($this->product_columns));
        if (empty($fields)) {
            return 0;
        }

        $set = implode(', ', array_map(function ($column) {
            return $column . ' = ?';
        }, array_keys($fields)));

        $values   = array_values($fields);
        $values[] = $id;

        $stmt = $this->db->raw("UPDATE products SET $set WHERE id = ?", $values);
        return $stmt->rowCount();
    }

    public function delete_product($id)
    {
        $stmt = $this->db->raw("DELETE FROM products WHERE id = ?", [$id]);
        return $stmt->rowCount();
    }
}