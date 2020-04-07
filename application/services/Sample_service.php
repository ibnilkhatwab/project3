<?php

class Sample_service extends MS_Service
{
    public function hello_world()
    {
        $this->load->model('entity_model');
        return $this->entity_model->get_data();
    }
}
