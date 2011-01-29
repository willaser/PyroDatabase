<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database
 *
 * Controller for the redirects module
 * 
 * @author 		Addict Add-ons Dev Team
 * @link		http://addictaddons.com
 * @package 	PyroDatabase
 * @category	Modules
 */
class Admin extends Admin_Controller
{
	/**
	 * Constructor method
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$this->template->append_metadata( css('database.css', 'database') )
				->set_partial('shortcuts', 'admin/partials/shortcuts');
	}

	// --------------------------------------------------------------------------	
	
	/**
	 * Show general table stats
	 *
	 * @access	public
	 */
	public function index()
	{
		$raw_stats = explode('  ', mysql_stat());

		$this->data->stats = array();
		
		foreach( $raw_stats as $stat ):
		
			$break = explode(":", $stat);
			
			$this->data->stats[trim($break[0])] = 	trim($break[1]);		
		
		endforeach;
		
		$this->template->build('admin/overview', $this->data);
	}

	// --------------------------------------------------------------------------	

	/**
	 * Show tables
	 *
	 * @access	public
	 */
	function tables()
	{
		if( $this->input->post('repair') ):
		
			$this->_perform_operation( 'repair' );
		
		elseif( $this->input->post('optimize') ):
		
			$this->_perform_operation( 'optimize' );
		
		endif;
	
		$this->load->helper( array('form', 'number') );
	
		$db_obj = $this->db->query("SHOW TABLE STATUS");	
		
		$this->data->tables = $db_obj->result();
		
		$this->template->build('admin/list_tables', $this->data);
	}

	// --------------------------------------------------------------------------	
	
	/**
	 * Perform an operation (repair or optimize)
	 *
	 * @access	private
	 */
	private function _perform_operation( $type )
	{
		// -------------------------------------
		// Easy out if there ain't no data
		// -------------------------------------

		if( ! $this->input->post('tables') ):
			
			$this->session->set_flashdata('notice', 'You must select at least one table to perform this action.');
			
			redirect('admin/database/tables');

		endif;

		// -------------------------------------
		// Repair/Optimize the Tables
		// -------------------------------------

		if( $type == 'repair' ):
		
			$action = 'repair_table';
			$lang 	= 'repaired';
		
		else:
		
			$action = 'optimize_table';
			$lang	= 'optimized';
		
		endif;
		
		$this->load->dbutil();
		
		$outcome = "The following tables were $lang:\n\n";
	
		foreach( $this->input->post('tables') as $table ):
		
			$outcome .= $table.' (';	
		
			$this->dbutil->repair_table('table_name') ? $outcome .= 'Success' : $outcome .= 'Failure';
			
			$outcome .= ")\n";
		
		endforeach;
		
		$this->session->set_flashdata('success', $outcome);

		redirect('admin/database/tables');
	}

	// --------------------------------------------------------------------------	

	/**
	 * View a table's stucture
	 *
	 * @access	public
	 */
	public function table()
	{
		$this->load->helper('number');

		$table_name = $this->uri->segment(4);
		
		if( !$table_name || !$this->db->table_exists($table_name) ):
		
			show_error("Invalid Table Name");
	
		endif;
	
		// -------------------------------------
		// Get field data
		// -------------------------------------

		$this->data->fields = $this->db->field_data( $table_name );
		
		$this->data->table_name = $table_name;

		$this->template->build('admin/list_table_structure', $this->data);
	}

	// --------------------------------------------------------------------------	

	/**
	 * Show processlist
	 *
	 * @access	public
	 */
	function processes()
	{
		$this->load->helper( 'number' );
	
		$db_obj = $this->db->query("SHOW PROCESSLIST");	
		
		$this->data->processes = $db_obj->result();
		
		$this->template->build('admin/list_processes', $this->data);
	}


	// --------------------------------------------------------------------------	

	/**
	 * Run a Query and display the results
	 *
	 * @access	public
	 */
	public function query()
	{
		$this->data->query_string = '';
		
		$this->data->mysql_result_error = '';

		$this->db->db_debug = FALSE;
		
		$this->data->query_run = FALSE;
	
		if( $this->input->post('query') && $this->input->post('query') != '' ):
		
			// Perform Query
			
			$db_obj = $this->db->query( $this->input->post('query_window') );
				
			$this->data->query_run = TRUE;
			
			if( $db_obj ):
			
				$this->data->query_string = $this->input->post('query_window');
			
				$this->data->results = $db_obj->result_array();
			
			else:
			
				$this->data->mysql_result_error = mysql_error();
			
			endif;
			
		endif;

		$this->template->append_metadata(js('codemirror/codemirror.js'))->build('admin/query', $this->data);	
	}
}

/* End of file admin.php */