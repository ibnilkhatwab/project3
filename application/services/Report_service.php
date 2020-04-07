<?php

class Report_service extends MS_Service
{
    public function get_all_class_reports()
    {
		$this->load->model('year_model');
		$active_session = $this->year_model->get_active_session();

        $this->load->model('report_model');
        return $this->report_model->get_all_class_reports($active_session);
    }

	public function get_subject_reports($class_code)
    {
		$this->load->model('year_model');
		$active_session = $this->year_model->get_active_session();

		$this->load->model('report_model');
        return $this->report_model->get_subject_reports($class_code, $active_session);
    }

    public function get_class_subject_report($class_code, $subject_code)
    {
		$this->load->model('year_model');
		$active_session = $this->year_model->get_active_session();

		$this->load->model('report_model');
        return $this->report_model->get_class_subject_report($class_code, $subject_code, $active_session);
    }

	public function update_class_subject_report($subject_code, $sids, $marks1, $marks2)
    {
    	$student_nums = count($sids);
    	$success = true;
        $this->load->model('Student_subjects_model');
        for($i = 0; $i < $student_nums; $i++)
		{
        	if(!$this->Student_subjects_model->update_student_marks($subject_code, $sids[$i], $marks1[$i], $marks2[$i]))
			{
				$success = false;
			}
		}
        return $success;
    }

	public function submit_class_subject_form($class, $subject)
	{
		$this->load->model('report_model');
		return $this->report_model->submit_class_subject_form($class, $subject);
	}

	public function get_submitted_class_reports()
	{
		$this->load->model('report_model');
		return $this->report_model->get_submitted_class_reports();
	}

	public function get_subject_results($class)
	{
		$this->load->model('year_model');
		$active_session = $this->year_model->get_active_session();

		$this->load->model('report_model');
		$results = $this->report_model->get_subjects_active_reports($class, $active_session);
		$subjects = array();
		foreach($results as $subject) array_push($subjects, $subject['subject_name']);

		$this->load->model('student_subjects_model');
		$class_subjects = $this->report_model->get_class_subjects($class, $active_session['active_sem']);
		$class_subjects_array = array();
		$class = array('class_name' => $class_subjects[0]['class_name'], 'class_code' => $class_subjects[0]['class_code']);
		foreach($class_subjects as $s) array_push($class_subjects_array, $s['subject_id']);
		$class_subjects = $this->report_model->get_subjects_active_unsubmitted_reports($class['class_code'], $active_session);
		$class_subjects_unsubmitted = array();
		foreach($class_subjects as $s) array_push($class_subjects_unsubmitted, $s['subject_id']);
		$is_complete = count($class_subjects_unsubmitted) ? false : true;

				// build student information based on semester
		$students = $this->student_subjects_model->get_students_marks($class_subjects_array, $class['class_code'], $active_session);
		$students_list = array();
		$current_student = "";
		foreach($students as $k => $s)
		{
			if($current_student == $s['sid'])
			{
				$student = array();
				$student['subject_code'] = $s['subject_code'];

				if(in_array($s['subject_code'], $class_subjects_unsubmitted)) $student['mark'] = null;
				else $student['mark'] = $s['course_mark'] + $s['exam_mark'];

				array_push($students_list[$current_student]['subjects'], $student);
			}
			else
			{
				$current_student = $s['sid'];
				$student = array();
				$student['sid'] = $s['sid'];
				$student['student_sid'] = $s['student_sid'];
				$student['full_name'] = $s['fname'] . ' ' . $s['mname'] . ' ' . $s['lname'];
				$student['semister'] = $s['semister'];
				$student['sem_1_avg'] = $s['sem_1_avg'];
				$student['sem_2_avg'] = $s['sem_2_avg'];
				$student['progress_id'] = $s['progress_id'];

				if(in_array($s['subject_code'], $class_subjects_unsubmitted)) $student['subjects'][0]['mark'] = null;
				else $student['subjects'][0]['mark'] = $s['course_mark'] + $s['exam_mark'];

				$student['subjects'][0]['subject_code'] = $s['subject_code'];

				$students_list["$current_student"] = $student;
			}
			$current_student = $s['sid'];
		}

				// calculate for sending
		foreach($students_list as $k => $v)
		{
			if($is_complete)
			{

				if($active_session['active_sem'] == 1)
				{
					$total = 0;
					$n = count($subjects);
					foreach($v['subjects'] as $s) $total = $total + $s['mark'];
					$students_list["$k"]['sem_1_avg'] = $total / $n;
					$students_list["$k"]['next'] = "next Semester";
				}
				else
				{
					$total = 0;
					$n = count($subjects);
					foreach($v['subjects'] as $s) $total = $total + $s['mark'];
					$students_list["$k"]['sem_2_avg'] = $total / $n;
					$students_list["$k"]['total'] = ($students_list["$k"]['sem_2_avg'] + $students_list["$k"]['sem_1_avg']) / 2;

					$this->load->model('progress_model');
					$progress = $this->progress_model->get_all_progress();
					$progress_list = array();
					foreach ($progress as $p) { $p_k = $p['progress_value']; $progress_list["$p_k"] = $p['progress_name']; }

					if($students_list["$k"]['total'] > 39)
					{
						$students_list["$k"]['next'] = $progress[1]['progress_name'];
						$students_list["$k"]['progress_id'] = $progress[1]['progress_value'];
					}
					else
					{
						switch($students_list["$k"]['progress_id'])
						{
							case $progress[3]['progress_value']:
								$students_list["$k"]['next'] = $progress[5]['progress_name'];
								$students_list["$k"]['progress_id'] = $progress[5]['progress_value'];
								break;
							case $progress[5]['progress_value']:
								$students_list["$k"]['next'] = $progress[0]['progress_name'];
								$students_list["$k"]['progress_id'] = $progress[0]['progress_value'];
								break;
							default:
								$students_list["$k"]['next'] = $progress[3]['progress_name'];
								$students_list["$k"]['progress_id'] = $progress[3]['progress_value'];
								break;
						}
					}
				}
			}
			else
			{
				if($active_session['active_sem'] == 1)
				{
					$students_list["$k"]['sem_1_avg'] = null;
					$students_list["$k"]['next'] = null;
				}
				else
				{
					$students_list["$k"]['sem_1_avg'] = null;
					$students_list["$k"]['sem_2_avg'] = null;
					$students_list["$k"]['total'] = null;
					$students_list["$k"]['next'] = null;
				}
			}
		}

		$data = array(
				'year' => $active_session['year'],
				'semester' => $active_session['active_sem'],
				'class_name' => $class['class_name'],
				'class_code' => $class['class_code'],
				'subjects' => $subjects,
				'students' => $students_list,
				'is_complete' => $is_complete,
				'progress_list' => isset($progress_list) ? $progress_list : null,
			);
		return $data;
	}

	public function process_class_semester($array)
	{
		$this->load->model('year_model');
		$active_session = $this->year_model->get_active_session();

		$this->load->model('report_model');
		$class_subjects_unsubmitted = $this->report_model->get_subjects_active_unsubmitted_reports($array['class_code'], $active_session);
		if(count($class_subjects_unsubmitted)) { die('some missing forms'); }

		$class_subjects = $this->report_model->get_class_subjects($array['class_code'], $active_session['active_sem']);
		$class_subjects_array = array();
		foreach($class_subjects as $s) array_push($class_subjects_array, $s['subject_id']);

				// build student information based on semester
		$this->load->model('student_subjects_model');
		$students = $this->student_subjects_model->get_students_marks($class_subjects_array, $array['class_code'], $active_session);
		$students_list = array();
		$current_student = "";
		foreach($students as $k => $s)
		{
			if($current_student == $s['sid'])
			{
				$student = array();
				$student['subject_code'] = $s['subject_code'];

				if(in_array($s['subject_code'], $class_subjects_unsubmitted)) $student['mark'] = null;
				else $student['mark'] = $s['course_mark'] + $s['exam_mark'];

				array_push($students_list[$current_student]['subjects'], $student);
			}
			else
			{
				$current_student = $s['sid'];
				$student = array();
				$student['sid'] = $s['sid'];
				$student['enrol_id'] = $s['enrol_id'];
				$student['student_sid'] = $s['student_sid'];
				$student['full_name'] = $s['fname'] . ' ' . $s['mname'] . ' ' . $s['lname'];
				$student['semister'] = $s['semister'];
				$student['sem_1_avg'] = $s['sem_1_avg'];
				$student['sem_2_avg'] = $s['sem_2_avg'];
				$student['progress_id'] = $s['progress_id'];

				if(in_array($s['subject_code'], $class_subjects_unsubmitted))$student['subjects'][0]['mark'] = null;
				else $student['subjects'][0]['mark'] = $s['course_mark'] + $s['exam_mark'];

				$student['subjects'][0]['subject_code'] = $s['subject_code'];

				$students_list["$current_student"] = $student;
			}
			$current_student = $s['sid'];
		}

				// calculate for updation
		$update = array();
		$insert = array();
		$marks_reset = array();
		$class_code_process = $this->get_next_and_last_class_code($array['class_code']);
		$next_class_code = $class_code_process['next_class_code'];
		$last_class_code = $class_code_process['last_class_code'];
		$next_year = $this->get_next_year($active_session);
		foreach($students_list as $k => $v)
		{
			if($active_session['active_sem'] == 1)
			{
				$total = 0;
				$n = count($class_subjects_array);
				foreach ($v['subjects'] as $s) $total = $total + $s['mark'];
				$total = $total / $n;
				$update_student = array(
					'id' => $v['enrol_id'],
					'semister' => 2,
					'sem_1_avg' => $total,
				);
				array_push($update, $update_student);
			}
			else
			{
				$total = 0;
				$n = count($class_subjects_array);
				foreach($v['subjects'] as $s) $total = $total + $s['mark'];
				$students_list["$k"]['sem_2_avg'] = $total / $n;
				$students_list["$k"]['total'] = ($students_list["$k"]['sem_2_avg'] + $students_list["$k"]['sem_1_avg']) / 2;
				$progress = $this->input->post('progress');
				$students_list["$k"]['progress_id'] = $progress["$k"];

				$this->load->model('progress_model');
				$progress_result = $this->progress_model->get_all_progress();
				$progress_list = array();
				foreach ($progress_result as $p) { $p_k = $p['progress_value']; $progress_list["$p_k"] = $p['progress_name']; }

				switch($progress["$k"])
				{
					case 3:
					case 6:
					case 9:
						$update_student = array(
							'id' => $v['enrol_id'], 'sem_2_avg' => $students_list["$k"]['sem_2_avg'],
							'average' => $students_list["$k"]['total'], 'progress_id' => $progress["$k"]
						);
						break;
					case 12:
					case 18:
						$update_student = array(
							'id' => $v['enrol_id'], 'sem_2_avg' => null, 'sem_1_avg' => null, 'semister' => 1,
							'average' => null, 'progress_id' => $progress["$k"], 'year' => $next_year
						);
						$arr = $this->student_subjects_model->get_student_marks_on_semester(array('sid' => $v['sid'], 'class' => $array['class_code']));
						$marks_reset = array_merge($marks_reset, $this->reset_marks($arr));
						break;
				}

				array_push($update, $update_student);
				if($next_class_code !== $last_class_code && $progress["$k"] == 6)
				{
					array_push($insert, array('student_id' => $v['sid'], 'progress_id' => 15, 'class_id' => $next_class_code, 'year' => $next_year));
				}
			}
		}

		if(count($marks_reset))
		{
			$this->db->where_in('id', $marks_reset);
			$this->db->delete('student_subjects');
		}
		$this->db->update_batch('enrollement', $update, 'id');
		if(count($insert)) $this->db->insert_batch('enrollement', $insert, 'student_id');
		$this->report_model->confirm_reports($array['class_code'], $active_session);

		return true;
	}

	private function get_next_and_last_class_code($class)
	{
		$this->load->model('class_model');

		$class_value = $this->class_model->get_value_from_code($class)['class_value'];
		$results = $this->class_model->get_all_classes();
		$results_size = count($results);
		$last_index = $results_size - 1;
		$before_last_index = $last_index - 1;
		$data = array();

		for($i = 0; $i < $results_size; $i++)
		{
			if($results["$i"]['class_value'] == $class_value)
			{
				if($i == $last_index)
				{
					$data['next_class_code'] = $results["$last_index"]['class_code'];
					$data['last_class_code'] = $results["$last_index"]['class_code'];
				}
				else if($i == $before_last_index)
				{
					$data['next_class_code'] = $results["$last_index"]['class_code'];
					$data['last_class_code'] = '---';
				}
				else
				{
					$i++;
					$data['next_class_code'] = $results["$i"]['class_code'];
					$data['last_class_code'] = $results["$last_index"]['class_code'];
				}
				return $data;
			}
		}
	}

	private function get_next_year($current_year)
	{
		$year = $current_year['year_numeric'] + 1;
		$year_string = '20' . ($year) . '/' . '20' . ($year + 1);
		$is_exist = $this->db->get_where('year', array('year' => $year_string, 'year_numeric' => $year))->num_rows();
		if(!$is_exist)
		{
			$this->db->insert('year', array('year' => $year_string, 'year_numeric' => $year));
		}
		return $year_string;
	}

	private function reset_marks($array)
	{
		$arr = array();
		foreach ($array as $subject) array_push($arr, $subject['id']);
		return $arr;
	}

}
