<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class About_admin extends Admin_Controller 
{
    // this class using for administrator not user/public on admin
    // can access by admin
    public function __construct()
    {
        parent::__construct();
	
        $this->load->model(array('about_model'));
        $this->load->library(array('template', 'form_validation'));
        // $this->load->library('database');
        $this->load->helper(array('adminlte_helper','language','url', 'form'));

        // load ion auth
        $this->load->add_package_path(APPPATH.'third_party/ion_auth/');
        $this->load->library('ion_auth');
        $this->lang->load('auth');
        $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

        // users can access by admin
        if(!$this->ion_auth->logged_in() or !$this->ion_auth->is_admin())
        {
             redirect('auth', 'refresh');
        }
    }

    public function index()
	{   
        $this->load->library('datatables');
        $data['page_title'] = 'About';
        $data['page_description'] = 'About List';
        $data['message'] = $this->session->flashdata('message');
        $data['dt_users'] = $this->about_model->_datatable_index();
        $this->template->_set_css('admin','dataTables.bootstrap.min.css','adminlte/bower_components/datatables.net-bs/css')
                    ->_set_js('admin','footer','jquery.dataTables.min.js','adminlte/bower_components/datatables.net/js')
                    ->_set_js('admin','footer','dataTables.bootstrap.min.js','adminlte/bower_components/datatables.net-bs/js')
                    // ->_set_js('admin','footer','serverside.dataTables.js','adminlte/script')
                    ->_set_js('admin','footer','htmldom.dataTables.js','adminlte/script')
                    ->_set_js('admin','footer','dataTables.buttons.min.js','https://cdn.datatables.net/buttons/1.5.2/js', TRUE)
                    ->_set_js('admin','footer','buttons.flash.min.js','https://cdn.datatables.net/buttons/1.5.2/js', TRUE)
                    ->_set_js('admin','footer','jszip.min.js','https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3', TRUE)
                    ->_set_js('admin','fopdfmake.min.js','https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36', TRUE)
                    ->_set_js('admin','footer','vfs_fonts.js','https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36', TRUE)
                    ->_set_js('admin','footer','buttons.html5.min.js','https://cdn.datatables.net/buttons/1.5.2/js', TRUE)
                    ->_set_js('admin','footer','buttons.print.min.js','https://cdn.datatables.net/buttons/1.5.2/js', TRUE)
                    ->_render_admin('index_user_admin', $data);
    }

    public function edit($id)
    {
		if (!$this->ion_auth->logged_in() || (!$this->ion_auth->is_admin() && !($this->ion_auth->user()->row()->id == $id)))
		{
			redirect('auth', 'refresh');
		}

        $product = $this->about_model->_read($id);
		$user = $this->ion_auth->user($id)->row();
		$groups = $this->ion_auth->groups()->result_array();
		$currentGroups = $this->ion_auth->get_users_groups($id)->result();

		// validate form input
        $this->form_validation->set_rules('title_image', 'TItle Image', 'trim|required');
        $this->form_validation->set_rules('sub_title_image', 'Sub Title Image', 'trim|required');
        $this->form_validation->set_rules('company_name', 'Company Name', 'trim|required');

        $identity_column = $this->config->item('identity', 'about');
        $data['form']['identity_column'] = $identity_column;
        
		if (isset($_POST) && !empty($_POST))
		{
			// do we have a valid request?
			if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id'))
			{
				show_error('This form post did not pass our security checks.'); // $this->lang->line('error_csrf')
			}

			if ($this->form_validation->run() === TRUE)
			{

                $config['upload_path']          = './assets/public/uploads/';
                $config['allowed_types']        = 'gif|jpg|png|jpeg';
                // $config['max_size']             = 100;
                // $config['max_width']            = 1024;
                // $config['max_height']           = 768;

                $this->load->library('upload', $config);

                if ( ! $this->upload->do_upload('image')){
                    $error = array('error' => $this->upload->display_errors());
                    redirect('admin'. DIRECTORY_SEPARATOR .'about/edit/' .$product->id, 'refresh');
                }else{
                    $data = array($this->upload->data());
                    $additional_data = array(
                        'title_image' => $this->input->post('title_image'),
                        'sub_title_image' => $this->input->post('sub_title_image'),
                        'company_name' => $this->input->post('company_name'),
                        'ket' => $this->input->post('ket'),
                        'image' => $data[0]['file_name'],
                    );
                    $this->about_model->_update($product->id, $additional_data);
                                $this->session->set_flashdata('message', 'Sukses merubah about');
                    redirect('admin'. DIRECTORY_SEPARATOR .'about', 'refresh');
                }
			}
		}
        
		// display the edit user form
		$data['form']['csrf'] = $this->_get_csrf_nonce();

		// set the flash data error message if there is one
		$data['message'] = (validation_errors() ? validation_errors() : ($this->session->flashdata('message')));

        $data['form']['id'] = array(
            'name' => 'id',
            'id' => 'input-id',
            'class' => 'form-control',
            'type' => 'text',
            'placeholder' => 'Id',
            'value' => $this->form_validation->set_value('id', $product->id),
        );
            $data['form']['title_image'] = array(
                'name' => 'title_image',
                'id' => 'title_image',
                'class' => 'form-control',
                'type' => 'text',
                'placeholder' => 'Title Image',
                'value' => $this->form_validation->set_value('title_image'),
            );

            $data['form']['sub_title_image'] = array(
                'name' => 'sub_title_image',
                'id' => 'sub_title_image',
                'class' => 'form-control',
                'type' => 'text',
                'placeholder' => 'Sub Title Image',
                'value' => $this->form_validation->set_value('sub_title_image'),
            );
            $data['form']['company_name'] = array(
                'name' => 'company_name',
                'id' => 'company_name',
                'class' => 'form-control',
                'type' => 'text',
                'placeholder' => 'Company Name',
                'value' => $this->form_validation->set_value('company_name'),
            );
            $data['form']['ket'] = array(
                'name' => 'ket',
                'id' => 'ket',
                'class' => 'form-control',
                'type' => 'text',
                'placeholder' => 'Keterangan',
                'value' => $this->form_validation->set_value('ket'),
            );

		// $this->_render_page('authx' . DIRECTORY_SEPARATOR . 'edit_user', $this->data);
        
        $data['page_title'] = 'Edit About';
        $data['page_description'] = '';
        $data['dt_users'] = $this->about_model->_read($id);
        $this->template->_render_admin('edit_user_admin', $data);
    }

    /**
	 * @return array A CSRF key-value pair
	 */
	public function _get_csrf_nonce()
	{
		$this->load->helper('string');
		$key = random_string('alnum', 8);
		$value = random_string('alnum', 20);
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);

		return array($key => $value);
    }
    
    /**
	 * @return bool Whether the posted CSRF token matches
	 */
	public function _valid_csrf_nonce(){
		$csrfkey = $this->input->post($this->session->flashdata('csrfkey'));
		if ($csrfkey && $csrfkey === $this->session->flashdata('csrfvalue')){
			return TRUE;
		}
			return FALSE;
	}

    // public function json_users()
    // {
    //     $this->load->library('datatables');
    //     return print_r($this->datatables->select('username, email, last_login')
    //                         ->from('users')
    //                         ->generate());
    //     // echo var_dump($this->datatables->_get_table('users'));
    // }
}
