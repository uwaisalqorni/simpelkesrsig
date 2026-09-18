<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Controller.php';

class AuditLogs extends Base_Api_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * GET /api/audit-logs
     */
    public function index() {
        $this->require_role(['admin']);
        $search = $this->input->get('search');
        $limit  = $this->input->get('limit') ? (int)$this->input->get('limit') : 50;

        $this->db->select('a.*, u.full_name as user_name, u.role as user_role');
        $this->db->from('audit_logs a');
        $this->db->join('users u', 'u.id = a.user_id', 'left');

        if ($search) {
            $this->db->group_start();
            $this->db->like('a.action', $search);
            $this->db->or_like('a.details', $search);
            $this->db->or_like('u.full_name', $search);
            $this->db->or_like('a.table_name', $search);
            $this->db->group_end();
        }

        $this->db->order_by('a.id', 'DESC');
        $this->db->limit($limit);
        $logs = $this->db->get()->result_array();

        $this->json_response(true, 'Data log audit aktivitas.', $logs);
    }
}
