<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Categories extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * GET /api/categories
     */
    public function index() {
        $this->authenticate(true);
        $categories = $this->db->order_by('id', 'ASC')->get('equipment_categories')->result_array();
        $this->json_response(true, 'Data kategori alkes berhasil diambil.', $categories);
    }
}
