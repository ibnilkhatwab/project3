<?php

class Session_service extends MS_Service
{
    public function next_semester()
    {
    	$this->load->model('year_model');
		$current_year = $this->year_model->get_active_session();
		$current_year['active_sem'] = $current_year['active_sem'] + 1;
		if($current_year['active_sem'] == 2)
		{
			$conditions = array(
				'year_numeric' => $current_year['year_numeric'],
				'year' => $current_year['year']);
			$this->db->update('year', array('active_sem' => $current_year['active_sem']), $conditions);
		}
		else
		{
			$this->db->update('year', array('active_sem' => 0), array('year' => $current_year['year']));
			$year_numeric = $current_year['year_numeric'] + 1;
			$year_string = '20' . ($year_numeric) . '/' . '20' . ($year_numeric + 1);
			$data = array(
				'year_numeric' => $year_numeric,
				'year' => $year_string,
				'active_sem' => 1
			);
			$is_exist = $this->db->get_where('year', array('year_numeric' => $year_numeric))->num_rows();
			if($is_exist)
			{
				$this->db->update('year', array('active_sem' => 1), array('year' => $year_string, 'year_numeric' => $year_numeric));
			}
			else
			{
				$this->db->insert('year', $data);
			}
		}


    }

    public function generate_reports_for_active_semester()
	{
		$this->load->model('year_model');
		$current_year = $this->year_model->get_active_session();

		$this->load->model('enrollement_model');
		$results = $this->enrollement_model->get_classes_subjects_from_current_session($current_year);
		$reports = array();

		foreach ($results as $r) {
			$array = array(
				'class_id' => $r['class_id'], 'subject_id' => $r['subject_code'],
				'semester' => $r['semister'], 'year' => $r['year'],
			);
			array_push($reports, $array);
		}

		if(count($reports)) $this->db->insert_batch('report', $reports);
	}

	public function generate_subjects_for_students()
	{
		$this->load->model('year_model');
		$current_year = $this->year_model->get_active_session();

		$this->load->model('enrollement_model');
		$results = $this->enrollement_model->get_subjects_for_students($current_year);

		if(count($results)) $this->db->insert_batch('student_subjects', $results);
	}

}
