<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Data_uji extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        // $this->output->enable_profiler(TRUE);
        $this->load->model("m_login");
        $this->load->model("m_data_latih");
        $this->load->model("m_data_uji");
        $this->load->model("m_data_latih_normalized");
        $this->load->model("m_data_uji_normalized");
        $this->load->model("m_register");
        $this->load->model("m_admin");
        $this->load->model("m_user");
        $this->load->model("m_log");
        $this->load->helper('array');
        $this->load->library("pagination");
        $this->load->library('form_validation');
    }


    public function index()
    {
        $log['log_datetime'] = date("Y-m-d H:i:s");
        $log['log_message'] = "HOMEPAGE :  user => " . $this->session->userdata('user_username') . "( id = " . $this->session->userdata('user_id') . ") ; result => ";
        $log['user_id'] = $this->session->userdata('user_id');
        $log['log_message'] .= "true";
        $this->m_log->inserLog($log);

        $data['page_name'] = "Data Uji";
        $data['user'] = $this->m_user->getUser($this->session->userdata('user_id'))[0];
        $data['files'] = $this->m_data_uji->read();
        $data['files_normalized'] = $this->m_data_uji_normalized->read();
        $data['data_testing']  = $this->m_data_latih_normalized->read();

        $data['data_uji_count'] = $this->m_data_uji->record_count();
        $data['data_uji_normalized_count'] = $this->m_data_uji_normalized->record_count();

        $this->load->view("_admin/_template/header");
        $this->load->view("_admin/_template/sidebar_menu");
        $this->load->view("_admin/data_uji/View_list", $data);
        $this->load->view("_admin/_template/footer");
    }

    public function rangking()
    {
        $quota = $this->input->post('quota');
        $data['files']  = $this->m_data_uji_normalized->rangking(-1,  "object", $quota);
        // echo json_encode( $data['files'] );return;
        $data['page_name'] = "Perengkingan";

        $this->load->view("_admin/_template/header");
        $this->load->view("_admin/_template/sidebar_menu");
        $this->load->view("_admin/data_uji/View_rangking", $data);
        $this->load->view("_admin/_template/footer");
    }

    public function create()
    {
        $data['page_name'] = "Tambah Data Uji";
        $inpust =  ($this->input->post('area[]') == null) ? array() : $this->input->post('area[]');
        // echo var_dump( $inpust );
        foreach ($inpust as $ind => $val) {
            if (!empty($this->input->post('area')[$ind])) {
                $this->form_validation->set_rules('area[' . $ind . ']', 'area', 'trim|required');
                $this->form_validation->set_rules('perimeter[' . $ind . ']', 'perimeter', 'trim|required');
                $this->form_validation->set_rules('bentuk[' . $ind . ']', 'bentuk', 'trim|required');
                $this->form_validation->set_rules('G0_kontras[' . $ind . ']', 'G0_kontras', 'trim|required');
                $this->form_validation->set_rules('G45_kontras[' . $ind . ']', 'G45_kontras', 'trim|required');
                $this->form_validation->set_rules('G90_kontras[' . $ind . ']', 'G90_kontras', 'trim|required');
                $this->form_validation->set_rules('G135_kontras[' . $ind . ']', 'G135_kontras', 'trim|required');
                $this->form_validation->set_rules('jenis[' . $ind . ']', 'jenis', 'trim|required');
            }
        }



        if ($this->form_validation->run() == true) {
            $data_uji = array();
            $inpust =  ($this->input->post('area[]') == null) ? array() : $this->input->post('area[]');
            foreach ($inpust as $ind => $val) {
                $data = array();
                if (!empty($this->input->post('area')[$ind])) {
                    $data_test["area"] = $this->input->post('area')[$ind];
                    $data_test["perimeter"] = $this->input->post('perimeter')[$ind];
                    $data_test["bentuk"] = $this->input->post('bentuk')[$ind];
                    $data_test["G0_kontras"] = $this->input->post('G0_kontras')[$ind];
                    $data_test["G45_kontras"] = $this->input->post('G45_kontras')[$ind];
                    $data_test["G90_kontras"] = $this->input->post('G90_kontras')[$ind];
                    $data_test["G135_kontras"] = $this->input->post('G135_kontras')[$ind];
                    $data_test["jenis"] = $this->input->post('jenis')[$ind];

                    array_push($data_uji, $data_test);
                }
            }

            // echo var_dump( $data_testing );
            if ($this->m_data_uji->create($data_uji)) {
                $this->session->set_flashdata('info', array(
                    'from' => 1,
                    'message' =>  'item berhasil ditambah'
                ));
                redirect(site_url('admin/data_uji'));
                return;
            }
            $this->session->set_flashdata('info', array(
                'from' => 0,
                'message' =>  'terjadi kesalahan saat mengirim data'
            ));
            redirect(site_url('admin/data_uji'));
        } else {
            $data['files'] = $this->m_data_uji->read();
            $data['user'] = $this->m_user->getUser($this->session->userdata('user_id'));
            $this->load->view("_admin/_template/header");
            $this->load->view("_admin/_template/sidebar_menu");
            $this->load->view("_admin/data_uji/View_create", $data);
            $this->load->view("_admin/_template/footer");
        }
    }

    public function import()
    {
        $data['page_name'] = "import Data Peserta";
        // if( !($_POST) ) redirect(site_url(''));  

        $this->load->library('upload'); // Load librari upload
        $filename = "excel";
        $config['upload_path'] = './upload/datatestingexcel/';
        $config['allowed_types'] = "xls|xlsx";
        $config['overwrite'] = "true";
        $config['max_size'] = "2048";
        $config['file_name'] = '' . $filename;
        $this->upload->initialize($config);

        if ($this->upload->do_upload("document_file")) {
            $filename = $this->upload->data()["file_name"];
            // echo $filename;
            // Load plugin PHPExcel nya
            include APPPATH . 'third_party/PHPExcel.php';

            $excelreader = new PHPExcel_Reader_Excel2007();
            $loadexcel = $excelreader->load('upload/datatestingexcel/' . $filename); // Load file yang telah diupload ke folder excel
            $sheet = $loadexcel->getActiveSheet()->toArray(null, true, true, true);

            // Buat sebuah vari
            $__data_uji = array();
            $numrow = 1;
            foreach ($sheet as $row) {
                // Cek $numrow apakah lebih dari 1
                // Artinya karena baris pertama adalah nama-nama kolom
                // Jadi dilewat saja, tidak usah diimport
                if ($numrow > 1 &&  !empty($row['A'])) {
                    $data_uji["id_uji"] = $row['A'];
                    $data_uji["jenis"] = -1;
                    $data_uji["area"] = $row['B'];
                    $data_uji["perimeter"] = $row['C'];
                    $data_uji["bentuk"] = $row['D'];
                    $data_uji["G0_kontras"] = $row['E'];
                    $data_uji["G45_kontras"] = $row['F'];
                    $data_uji["G90_kontras"] = $row['G'];
                    $data_uji["G135_kontras"] = $row['H'];

                    ##########################################################
                    // $data_profile["user_profile_fullname"] = $row['A'];
                    // $data_profile["user_profile_address"] = $row['G'];
                    // $data_profile["user_profile_email"] = $row['H'];
                    // $data_profile["user_profile_phone"] = $row['I'];
                    // //add user
                    // $identitas = time();
                    // $data_user['user_username'] = $identitas + $numrow;
                    // $data_user['user_password'] = md5($identitas);
                    // $result = $this->m_register->register($data_user, $data_profile);
                    // if ($result['status']) {
                    // $data_uji["jenis"] = $result['message']['user_id'];
                    // Kita push (add) array data ke variabel data
                    array_push($__data_uji, $data_uji);
                    // }
                }

                $numrow++; // Tambah 1 setiap kali looping
            }

            // echo json_encode( $__data_uji );
            // return;
            if ($this->m_data_uji->create($__data_uji)) {
                $this->session->set_flashdata('info', array(
                    'from' => 1,
                    'message' =>  'item berhasil diimport'
                ));
                redirect(site_url('admin/data_uji'));
                return;
            }
            $this->session->set_flashdata('info', array(
                'from' => 0,
                'message' =>  'terjadi kesalahan saat mengirim data'
            ));
            redirect(site_url('admin/user_management'));
        } else {
            // echo  $this->upload->display_errors();
            $this->load->view("_admin/_template/header");
            $this->load->view("_admin/_template/sidebar_menu");
            $this->load->view("_admin/data_uji/View_import", $data);
            $this->load->view("_admin/_template/footer");
        }
    }

    public function normalize()
    {
        $this->m_data_uji_normalized->clear(); //kosongka normalisasi
        $files = $this->m_data_uji->read_normalize();

        $min_max = $this->m_data_latih->get_min_max();

        if (empty($min_max) || empty($files)) {
            redirect(site_url('admin/data_uji'));
            return;
        }
        // echo json_encode( $min_max );
        // prosedur untuk menormalisasi
        for ($i = 0; $i < count($files); $i++) {
            // echo round( $files[ $i ]->G0_kontras,3)."<br>";
            $len = $min_max["max_area"] -  $min_max["min_area"];
            $files[$i]->area  =  (($files[$i]->area - $min_max["min_area"]) / ($len)) * 1 + 0;
            $files[$i]->area = round($files[$i]->area, 4);

            $len = $min_max["max_perimeter"] -  $min_max["min_perimeter"];
            $files[$i]->perimeter  =  (($files[$i]->perimeter - $min_max["min_perimeter"]) / ($len)) * 1 + 0;
            $files[$i]->perimeter = round($files[$i]->perimeter, 4);

            $len = $min_max["max_area"] -  $min_max["min_area"];
            $files[$i]->area  =  (($files[$i]->area - $min_max["min_area"]) / ($len)) * 1 + 0;
            $files[$i]->area = round($files[$i]->area, 4);

            $len = $min_max["max_bentuk"] -  $min_max["min_bentuk"];
            $files[$i]->bentuk  =  (($files[$i]->bentuk - $min_max["min_bentuk"]) / ($len)) * 1 + 0;
            $files[$i]->bentuk = round($files[$i]->bentuk, 4);

            $len = $min_max["max_G0_kontras"] -  $min_max["min_G0_kontras"];
            $files[$i]->G0_kontras  =  (($files[$i]->G0_kontras - $min_max["min_G0_kontras"]) / ($len)) * 1 + 0;
            $files[$i]->G0_kontras = round($files[$i]->G0_kontras, 4);

            $len = $min_max["max_G45_kontras"] -  $min_max["min_G45_kontras"];
            $files[$i]->G45_kontras  =  (($files[$i]->G45_kontras - $min_max["min_G45_kontras"]) / ($len)) * 1 + 0;
            $files[$i]->G45_kontras = round($files[$i]->G45_kontras, 4);

            $len = $min_max["max_G90_kontras"] -  $min_max["min_G90_kontras"];
            $files[$i]->G90_kontras  =  (($files[$i]->G90_kontras - $min_max["min_G90_kontras"]) / ($len)) * 1 + 0;
            $files[$i]->G90_kontras = round($files[$i]->G90_kontras, 4);

            $len = $min_max["max_G135_kontras"] -  $min_max["min_G135_kontras"];
            $files[$i]->G135_kontras  =  (($files[$i]->G135_kontras - $min_max["min_G135_kontras"]) / ($len)) * 1 + 0;
            $files[$i]->G135_kontras = round($files[$i]->G135_kontras, 4);
        }

        if ($this->m_data_uji_normalized->create($files)) {
            $this->session->set_flashdata('info', array(
                'from' => 1,
                'message' =>  'item berhasil di normalisasi'
            ));
            redirect(site_url('admin/data_uji'));
            return;
        }
        $this->session->set_flashdata('info', array(
            'from' => 0,
            'message' =>  'terjadi kesalahan saat mengirim data'
        ));
        redirect(site_url('admin/data_uji'));
    }

    public function uji()
    {
        if (!($_POST)) redirect(site_url('admin/data_uji'));

        $id_uji = $this->input->post('id_uji');
        // $data_uji = $this->m_data_uji_normalized->read_single_table( $id_uji , "array" );
        $data_uji = $this->m_data_uji_normalized->read($id_uji, "array");
        $data_testing = $this->m_data_latih_normalized->read(-1, "array");

        $min_max = $this->m_data_latih->get_min_max();
        // echo json_encode( $data_uji ).'<br>' ;
        // return;
        if (empty($data_uji) || empty($data_testing)) {
            redirect(site_url('admin/data_uji'));
            return;
        }

        if (empty($data_uji)) {
            redirect(site_url('/data_uji'));
            return;
        }
        $DISTANCES = array();
        //prosedur mencari tetangga terdekat
        for ($i = 0; $i < count($data_uji); $i++) {
            $DISTANCES = array();
            for ($j = 0; $j < count($data_testing); $j++) {
                $dist['distance'] = $this->distance($data_uji[$i], $data_testing[$j]);
                $dist['jenis'] = $data_testing[$j]['jenis'];
                $dist['id_uji'] = $data_testing[$j]['id_latih'];

                array_push($DISTANCES, $dist);
            }
            sort($DISTANCES); //mengurutkan distance dari terdekat

            $K_VALUE = $this->input->post('k_value');
            $NEIGHBOUR = array();
            for ($k = 0; $k < $K_VALUE; $k++) //memetakan tetangga
            {
                if (!isset($NEIGHBOUR[$DISTANCES[$k]['jenis']]))
                    $NEIGHBOUR[$DISTANCES[$k]['jenis']] = array();

                array_push($NEIGHBOUR[$DISTANCES[$k]['jenis']], $DISTANCES[$k]);
            }

            $terbesar =  array(); //mencari tetangga terbanyak
            foreach (array_keys($NEIGHBOUR) as $paramName) {

                if (count($NEIGHBOUR[$paramName])  > count($terbesar)) {
                    $terbesar = $NEIGHBOUR[$paramName];
                }
            }

            $data_uji[$i]['jenis'] = $terbesar[0]['jenis']; //update nilai label (lulus / tidak lulus)
        }


        $data["K_VALUE"] = $K_VALUE;
        $data["NEIGHBOURS"] = $NEIGHBOUR;
        $data["distances"] = $DISTANCES;
        //ubah ke array object
        foreach ($data["distances"]  as  $ind => $val) {
        }
        $data["data_uji"] = $data_uji;
        //ubah ke array object
        foreach ($data["data_uji"]  as  $ind => $val) {
            $data["data_uji"][$ind] = (object) $data_uji[$ind];
            unset($data_uji[$ind]['user_profile_fullname']);
        }
        // echo json_encode( $data_uji ).'<br>' ;

        // update data uji
        $this->m_data_uji_normalized->_update_batch($data_uji);

        $data['page_name'] = "Hasil Data Uji";
        $this->load->view("_admin/_template/header");
        $this->load->view("_admin/_template/sidebar_menu");
        $this->load->view("_admin/data_uji/View_detail_uji", $data);
        $this->load->view("_admin/_template/footer");
    }

    public function uji_batch()
    {
        if (!($_POST)) redirect(site_url('admin/data_uji'));

        $data_uji = $this->m_data_uji_normalized->read_single_table(-1, "array");
        $data_testing = $this->m_data_latih_normalized->read(-1, "array");

        $min_max = $this->m_data_latih->get_min_max();
        // echo json_encode( $data_testing ).'<br>' ;
        // return;

        if (empty($data_uji) || empty($data_testing)) {
            redirect(site_url('admin/data_uji'));
            return;
        }
        // echo json_encode( $data_testing );


        for ($i = 0; $i < count($data_uji); $i++) {
            $DISTANCES = array();
            for ($j = 0; $j < count($data_testing); $j++) {
                $dist['distances'] = $this->distance($data_uji[$i], $data_testing[$j]);
                $dist['jenis'] = $data_testing[$j]['jenis'];
                $dist['id_uji'] = $data_testing[$j]['id_latih'];
                // echo json_encode( $dist ).'<br>' ;

                array_push($DISTANCES, $dist);
            }
            sort($DISTANCES); //mengurutkan distance dari terdekat
            // echo "DISTANCES".json_encode( $DISTANCES ).'<br>' ;

            $K_VALUE = $this->input->post('k_value');
            $NEIGHBOUR = array();
            for ($k = 0; $k < $K_VALUE; $k++) //memetakan tetangga
            {
                if (!isset($NEIGHBOUR[$DISTANCES[$k]['jenis']]))
                    $NEIGHBOUR[$DISTANCES[$k]['jenis']] = array();

                array_push($NEIGHBOUR[$DISTANCES[$k]['jenis']], $DISTANCES[$k]);
            }
            // echo 'NEIGHBOUR'.json_encode( $NEIGHBOUR ).'<br>' ;


            $terbesar =  array();



            foreach (array_keys($NEIGHBOUR) as $paramName) {

                if (count($NEIGHBOUR[$paramName])  > count($terbesar)) {
                    $terbesar = $NEIGHBOUR[$paramName];
                    // echo json_encode( $terbesar ).'<br>' ;
                }
            }
            // echo 'terbesar'.json_encode( $terbesar ).'<br>' ;

            $data_uji[$i]['jenis']        = $terbesar[0]['jenis']; //update nilai label (lulus / tidak lulus)
            $data_uji[$i]['tetangga_terdekat'] =  $terbesar[0]['id_uji'] . "(" . $terbesar[0]['distances']  . ")"; //update nilai label (lulus / tidak lulus)
        }

        foreach ($data_uji  as  $ind => $val) {
            unset($data_uji[$ind]['user_profile_fullname']);
        }
        // echo json_encode( $data_uji );
        if ($this->m_data_uji_normalized->_update_batch($data_uji)) {
            $this->session->set_flashdata('info', array(
                'from' => 1,
                'message' =>  'item berhasil di di uji'
            ));
            redirect(site_url('admin/data_uji'));
            return;
        } else {
            $this->session->set_flashdata('info', array(
                'from' => 0,
                'message' =>  'terjadi kesalahan saat menguji data'
            ));
            redirect(site_url('admin/data_uji'));
        }
    }

    public function uji_batch_2()
    {
        if (!($_POST)) redirect(site_url('admin/data_uji'));

        // $data_uji = $this->m_data_uji_normalized->read_single_table( -1, "array" );
        $data_uji = $this->m_data_uji_normalized->read(-1, "array");
        $data_testing = $this->m_data_latih_normalized->read(-1, "array");

        $min_max = $this->m_data_latih->get_min_max();
        // echo json_encode( $data_testing ).'<br>' ;
        // return;

        if (empty($data_uji) || empty($data_testing)) {
            redirect(site_url('admin/data_uji'));
            return;
        }
        // echo json_encode( $data_testing );


        for ($i = 0; $i < count($data_uji); $i++) {
            $DISTANCES = array();
            for ($j = 0; $j < count($data_testing); $j++) {
                $dist['distances'] = $this->distance($data_uji[$i], $data_testing[$j]);
                $dist['jenis'] = $data_testing[$j]['jenis'];
                $dist['id_uji'] = $data_testing[$j]['id_latih'];
                // echo json_encode( $dist ).'<br>' ;

                array_push($DISTANCES, $dist);
            }
            sort($DISTANCES); //mengurutkan distance dari terdekat
            // echo "DISTANCES".json_encode( $DISTANCES ).'<br>' ;

            $K_VALUE = $this->input->post('k_value');
            $NEIGHBOUR = array();
            for ($k = 0; $k < $K_VALUE; $k++) //memetakan tetangga
            {
                if (!isset($NEIGHBOUR[$DISTANCES[$k]['jenis']]))
                    $NEIGHBOUR[$DISTANCES[$k]['jenis']] = array();

                array_push($NEIGHBOUR[$DISTANCES[$k]['jenis']], $DISTANCES[$k]);
            }

            $terbesar =  array();
            foreach (array_keys($NEIGHBOUR) as $paramName) {

                if (count($NEIGHBOUR[$paramName])  > count($terbesar)) {
                    $terbesar = $NEIGHBOUR[$paramName];
                }
            }
            // $lulus = ($NEIGHBOUR[1])  ? count($NEIGHBOUR[1])  : 0;
            $sum = 0;
            $count = count($NEIGHBOUR[1]);

            foreach ($NEIGHBOUR[1] as $_length) {
                $sum += $_length['distances'];
            }

            $avrg = $sum / $count; // perhitungan nilai jarak rata-rata
            $data_uji[$i]['jenis']        = $terbesar[0]['jenis']; //update nilai label (lulus / tidak lulus)
            $data_uji[$i]['tetangga_terdekat'] = $avrg;
            $data_uji[$i]['K_VALUE']           = $K_VALUE;
            $data_uji[$i]['distances']         = $DISTANCES;
            $data_uji[$i]['NEIGHBOURS']        = $NEIGHBOUR;

            $data_uji_param['id_uji'] = $data_uji[$i]['id_uji'];
            $this->m_data_uji_normalized->update($data_uji[$i], $data_uji_param);
        }

        $data['data_uji'] = $data_uji;

        $data['files']  = $this->m_data_uji_normalized->rangking();
        // $data['data_uji'] = $data_uji;
        $data['page_name'] = "Hasil Data Uji";
        $this->load->view("_admin/_template/header");
        $this->load->view("_admin/_template/sidebar_menu");
        $this->load->view("_admin/data_uji/View_detail_uji_batch", $data);
        $this->load->view("_admin/_template/footer");
    }

    function hapus(){
		$this->m_data_uji->hapus_data();
		redirect(site_url('admin/data_uji'));
	}

    //   fungsi untuk menghitung jarak
    private function distance($data_uji, $data_testing)
    {
        $attrs = array(
            'area', 'perimeter', 'bentuk', 'G0_kontras',
            'G45_kontras', 'G90_kontras', 'G135_kontras'
        );
        $value = 0;
        foreach ($attrs as $attr) {
            $value += pow(($data_uji[$attr] - $data_testing[$attr]), 2);
        }
        return round(sqrt($value), 6);
    }




}
