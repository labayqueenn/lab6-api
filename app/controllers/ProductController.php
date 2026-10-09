<?php

defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ProductController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->model('Product_model');
        $this->call->library('api');
    }

    // The Api library escapes HTML in input; decode it so values are stored as typed.
    private function text($value)
    {
        return trim(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
    }

    private function validate($data, $require_all)
    {
        $fields = [];

        if ($require_all || array_key_exists('product_name', $data)) {
            $name = $this->text($data['product_name'] ?? '');
            if ($name === '' || mb_strlen($name) > 100) {
                $this->api->respond_error('Product name is required (max 100 characters).', 422);
            }
            $fields['product_name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $fields['description'] = $this->text($data['description']);
        }

        if ($require_all || array_key_exists('price', $data)) {
            $price = $data['price'] ?? '';
            if (!is_numeric($price) || $price < 0 || $price > 99999999.99) {
                $this->api->respond_error('Price must be a number from 0 to 99999999.99.', 422);
            }
            $fields['price'] = round((float) $price, 2);
        }

        if ($require_all || array_key_exists('quantity', $data)) {
            $qty = filter_var($data['quantity'] ?? '', FILTER_VALIDATE_INT);
            if ($qty === false || $qty < 0) {
                $this->api->respond_error('Quantity must be a whole number, 0 or higher.', 422);
            }
            $fields['quantity'] = $qty;
        }

        return $fields;
    }

    public function index()
    {
        $this->api->require_method('GET');
        $this->api->require_jwt();

        $this->api->respond(['data' => $this->Product_model->all_products()]);
    }

    public function show($id)
    {
        $this->api->require_method('GET');
        $this->api->require_jwt();

        $product = $this->Product_model->find_product((int) $id);
        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }

        $this->api->respond(['data' => $product]);
    }

    public function store()
    {
        $this->api->require_method('POST');
        $this->api->require_jwt();

        $fields = $this->validate($this->api->body(), true);
        $id = $this->Product_model->create_product($fields);

        $this->api->respond([
            'message' => 'Product created.',
            'data'    => $this->Product_model->find_product($id),
        ], 201);
    }

    public function update($id)
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true)) {
            $this->api->respond_error('Method Not Allowed', 405);
        }
        $this->api->require_jwt();

        $id = (int) $id;
        if (!$this->Product_model->find_product($id)) {
            $this->api->respond_error('Product not found.', 404);
        }

        $fields = $this->validate($this->api->body(), false);
        if (empty($fields)) {
            $this->api->respond_error('No valid fields to update.', 422);
        }

        $this->Product_model->update_product($id, $fields);

        $this->api->respond([
            'message' => 'Product updated.',
            'data'    => $this->Product_model->find_product($id),
        ]);
    }

    public function destroy($id)
    {
        $this->api->require_method('DELETE');
        $this->api->require_jwt();

        $id = (int) $id;
        if (!$this->Product_model->find_product($id)) {
            $this->api->respond_error('Product not found.', 404);
        }

        $this->Product_model->delete_product($id);

        $this->api->respond(['message' => 'Product deleted.']);
    }
}
