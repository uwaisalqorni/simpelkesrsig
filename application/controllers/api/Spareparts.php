<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Spareparts extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Sparepart_model', 'sparepart_m');
    }

    /**
     * GET /api/spareparts or POST to create
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->store();
        }

        $this->authenticate(true);
        $search = $this->input->get('search');
        $parts = $this->sparepart_m->get_all($search);
        $this->json_response(true, 'Data suku cadang berhasil diambil.', $parts);
    }

    /**
     * POST /api/spareparts (Admin/Teknisi)
     */
    public function store() {
        $this->require_role(['admin', 'teknisi']);
        $input = $this->get_json_input();

        if (empty($input['part_number']) || empty($input['name'])) {
            $this->json_response(false, 'Nomor part dan nama sparepart wajib diisi.', null, 400);
        }

        $data = [
            'part_number' => strtoupper(trim($input['part_number'])),
            'name'        => trim($input['name']),
            'category'    => isset($input['category']) ? trim($input['category']) : null,
            'stock_qty'   => isset($input['stock_qty']) ? (int)$input['stock_qty'] : 0,
            'unit_cost'   => isset($input['unit_cost']) ? (float)$input['unit_cost'] : 0.00
        ];

        $insert_id = $this->sparepart_m->insert($data);
        $this->log_audit('CREATE_SPAREPART', 'spareparts', $insert_id, $data);

        $this->json_response(true, 'Suku cadang berhasil ditambahkan.', ['id' => $insert_id], 201);
    }

    /**
     * POST /api/spareparts/update/{id}
     */
    public function update($id = null) {
        $this->require_role(['admin', 'teknisi']);
        $part = $this->sparepart_m->find_by_id($id);
        if (!$part) {
            $this->json_response(false, 'Suku cadang tidak ditemukan.', null, 404);
        }

        $input = $this->get_json_input();
        $data = [];
        if (isset($input['part_number'])) $data['part_number'] = strtoupper(trim($input['part_number']));
        if (isset($input['name'])) $data['name'] = trim($input['name']);
        if (isset($input['category'])) $data['category'] = trim($input['category']);
        if (isset($input['stock_qty'])) $data['stock_qty'] = (int)$input['stock_qty'];
        if (isset($input['unit_cost'])) $data['unit_cost'] = (float)$input['unit_cost'];

        $this->sparepart_m->update($id, $data);
        $this->log_audit('UPDATE_SPAREPART', 'spareparts', $id, $data);

        $this->json_response(true, 'Data suku cadang berhasil diperbarui.');
    }
}
