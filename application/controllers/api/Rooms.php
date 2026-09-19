<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class Rooms extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Room_model', 'room_m');
    }

    /**
     * GET /api/rooms
     */
    public function index() {
        if ($this->input->method() === 'post') {
            return $this->store();
        }
        $this->authenticate(true);
        $rooms = $this->room_m->get_all(false, $this->get_tenant_id()); // Sesuai tenant aktif
        $this->json_response(true, 'Data ruangan berhasil diambil.', $rooms);
    }

    /**
     * GET /api/rooms/{id}
     */
    public function show($id = null) {
        $this->authenticate(true);
        $room = $this->room_m->find_by_id($id);
        if (!$room) {
            $this->json_response(false, 'Ruangan tidak ditemukan.', null, 404);
        }
        $this->json_response(true, 'Detail ruangan.', $room);
    }

    /**
     * POST /api/rooms (Admin only)
     */
    public function store() {
        $this->require_role(['admin']);
        $input = $this->get_json_input();

        if (empty($input['code']) || empty($input['name'])) {
            $this->json_response(false, 'Kode dan nama ruangan wajib diisi.', null, 400);
        }

        $data = [
            'tenant_id' => $this->get_tenant_id(),
            'code'      => strtoupper(trim($input['code'])),
            'name'      => trim($input['name']),
            'building'  => isset($input['building']) ? trim($input['building']) : null,
            'floor'     => isset($input['floor']) ? trim($input['floor']) : null,
            'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : 1
        ];

        $insert_id = $this->room_m->insert($data);
        $this->log_audit('CREATE_ROOM', 'rooms', $insert_id, $data);

        $this->json_response(true, 'Ruangan berhasil ditambahkan.', ['id' => $insert_id], 201);
    }

    /**
     * PUT/POST /api/rooms/update/{id}
     */
    public function update($id = null) {
        $this->require_role(['admin']);
        $room = $this->room_m->find_by_id($id);
        if (!$room) {
            $this->json_response(false, 'Ruangan tidak ditemukan.', null, 404);
        }

        $input = $this->get_json_input();
        $data = [];
        if (isset($input['code'])) $data['code'] = strtoupper(trim($input['code']));
        if (isset($input['name'])) $data['name'] = trim($input['name']);
        if (isset($input['building'])) $data['building'] = trim($input['building']);
        if (isset($input['floor'])) $data['floor'] = trim($input['floor']);
        if (isset($input['is_active'])) $data['is_active'] = (int)$input['is_active'];

        $this->room_m->update($id, $data);
        $this->log_audit('UPDATE_ROOM', 'rooms', $id, $data);

        $this->json_response(true, 'Data ruangan berhasil diperbarui.');
    }
}
