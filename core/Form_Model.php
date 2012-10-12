<?php 

class Form_Model extends CI_Model {

	public $CI;
	public $validate = array();
	public $files = array();
	public $uploads = array();

	public $upload_config = array(
		'upload_path' => './uploads/',
		'allowed_types' => 'gif|jpg|png',
		'max_size' => '2000',
		'max_width' => '2000',
		'max_height' => '2000',
	);

	public function __construct()
	{
		parent::__construct();

		$this->load->library('session');
	}

	public function is_valid($fields=NULL)
	{
		if(!$fields) $fields = array_keys($this->validate);

		$rules = array();
		foreach($fields as $field)
		{
			if(isset($this->validate[$field]))
			{
				$this->validate[$field]['field'] = $field;

				if(isset($this->validate[$field]['file']) && $this->validate[$field]['file'])
				{
					$this->files[$field] = $this->validate[$field];
					continue;
				}

				$rules[] = $this->validate[$field];
			}
		}

		$this->load->library('form_validation');
		$this->form_validation->CI =& $this;

		$this->form_validation->set_rules($rules);
		$is_valid = $this->form_validation->run();

		//now check files
		if(!empty($this->files))
		{
			$this->load->library('upload', $this->upload_config);

			foreach($this->files as $field => $file){
				$rules = explode('|', $file['rules']);
				$required = in_array('required', $rules);

				if($required && (!isset($_FILES[$field]) || $_FILES[$field]['error'] == 4))
				{
					$this->form_validation->set_error($field, 'This file is required.');
					$is_valid = FALSE;
				}

				if(isset($_FILES[$field]) && $_FILES[$field]['error'] != 4)
				{
					if(!$this->upload->do_upload($field)){
						$this->form_validation->set_error($field, $this->upload->display_errors());
						$is_valid = FALSE;
					} else {
						$this->uploads[] = $this->upload->data();
					}
				}
				
			}
		}

		return $is_valid;
	}

	public function save_input($fields=NULL)
	{
		if(!$fields) $fields = array_keys($_POST);

		$data = array();

		foreach($fields as $field)
		{
			if(!isset($_POST[$field])) continue;
			$data[$field] = $_POST[$field];
		}

		$this->session->set_userdata($data);
	}

	public function get($field)
	{
		if($this->input->post($field)) return $this->input->post($field);

		if($this->session->userdata($field)) return $this->session->userdata($field);

		return FALSE;
	}

}