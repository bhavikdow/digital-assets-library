<?php 
defined('BASEPATH') or exit ('No direct script access allowed');

/**
 * Author Rajat Agarwal
 */
class Document extends CI_Controller{
	
	public function __construct(){
		parent::__construct();
		$this->load->model('site_model');
		$this->load->library('zip');
        $this->load->helper('download');
	}

	private function is_login(){
        return $this->session->userdata('user_id');
    }

    public function getDataByUniqueId() {
        if (!empty($this->session->userdata('unique_id'))) {
            $unique_id = $this->session->userdata('unique_id');
            $data = $this->site_model->getDataByUniqueId($unique_id);
            return $data;
        }
    }

    public function document_library(){
        if(empty($this->is_login())){
            redirect(base_url('/'));
        }
        $data['title'] = "Document Library";
        $this->load->view('user/commons/header', $data);
        $this->load->view('user/commons/document_header');
        $this->load->view('user/document_library');
        $this->load->view('user/commons/footer');
    }

    public function document_upload(){
        if(empty($this->is_login())){
            redirect(base_url('/'));
        }
        $data['title'] = "Upload Document";
        $this->load->view('user/commons/header', $data);
        $this->load->view('user/commons/document_header');
        $this->load->view('user/document_upload');
        $this->load->view('user/commons/footer');
    }

    private function set_upload_options($file_name) {
        //upload an image options
        $config = array();
        $config['upload_path'] = './uploads/documents';
        // $config['allowed_types'] = 'key|pages|pdf|doc|docx|ppt|pptx';
        $config['allowed_types'] = '*';
        $config['max_size'] = '1000000';
        $config['overwrite'] = FALSE;
        $config['file_name'] = $file_name;
        return $config;
    }

    public function doUploadDocuments() {
        $this->output->set_content_type('application/json');
        $this->load->library('upload');
        $count = count($_FILES['file_url']['size']);
        $document = [];
        $i = 1;
        foreach ($_FILES as $key => $value) {
            for ($s = 0; $s < $count; $s++) {
                $_FILES['file_url']['name'] = $value['name'][$s];
                $_FILES['file_url']['type'] = $value['type'][$s];
                $_FILES['file_url']['tmp_name'] = $value['tmp_name'][$s];
                $_FILES['file_url']['error'] = $value['error'][$s];
                $_FILES['file_url']['size'] = $value['size'][$s];
                $this->upload->initialize($this->set_upload_options($_FILES['file_url']['name']));
                $this->upload->do_upload('file_url');
                $data = $this->upload->data();
                // echo '<pre>';
                // print_r($data);die;
                $document[$i] = $data['file_name'];

                $fileName = $data['file_name'];
                // print_r($fileName);die;
                
                //File path at local server
                // $source = 'uploads/documents/'.$fileName;

                //Load codeigniter FTP class
                // $this->load->library('ftp');

                // //FTP configuration
                // $ftp_config['hostname'] = 'www.designoweb.work'; 
                // $ftp_config['username'] = 'y4vjealborkl';
                // $ftp_config['password'] = 'F$GPOBs9I]';
                // $ftp_config['debug']    = TRUE;

                // //Connect to the remote server
                // $this->ftp->connect($ftp_config);

                // // Check directory exist
                // $destination_path = '/public_html/dal_dropbox/documents';
                // if (!$this->ftp->list_files($destination_path)) {
                //     $this->ftp->mkdir($destination_path, 755);
                // }

                // //File upload path of remote server
                // $destination = $destination_path.'/'.$fileName;
                
                // //Upload file to the remote server
                // $this->ftp->upload($source, $destination);
                
                // //Close FTP connection
                // $this->ftp->close();

                // Remove file from local folder
                // unlink(FCPATH.$source);
                $i++;
            }
        }
        // if ($this->upload->do_upload('file_url')) {
        if (!empty($document)) {
            // echo 'if';die;
            return $document;
        } else {
            // echo 'else';die;
            $this->session->set_userdata('error', ['file_url' => $this->upload->display_errors()]);
            return 0;
        }
    }

    public function do_upload_document(){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $documents = $this->doUploadDocuments();
        if (!$documents[1]) {
            $this->output->set_output(json_encode(['result' => 0, 'errors' => $this->session->userdata('error')]));
            $this->session->unset_userdata('error');
            return FALSE;
        }else{
            $i = 0;
            foreach ($documents as $document) {
                $doc_ext = explode('.', $document);
                $count = count($doc_ext);
                $doc_extension = $doc_ext[$count-1];
                
                if($doc_extension == 'pdf' || $doc_extension == 'doc' || $doc_extension == 'docx' || $doc_extension == 'ppt' || $doc_extension == 'pptx' || $doc_extension == 'key' || $doc_extension == 'xls' || $doc_extension == 'xlsx'){
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://v2.convertapi.com/convert/'.$doc_extension.'/to/jpg?Secret=3IzXcCrPbaJxl8Nk');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    $post = array(
                        'File' => base_url('uploads/documents/'.$document),
                        'StoreFile' => 'true',
                        'PageRange' => '1',
                    );
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        
                    $result = curl_exec($ch);
                    $data = json_decode($result);
                    // echo '<pre>';
                    // print_r($data);die;
                    
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
                    
                    curl_close($ch);
        
                    $url = $data->Files[0]->Url;
                    
                    $img_path = '/home/ipgdal5/public_html/uploads/document_thumbnail/'.$doc_ext[0].'.jpg';
                    
                    // Save image
                    $ch = curl_init($url);
                    $fp = fopen($img_path, 'wb');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);
                }
                
                if($doc_extension == 'pages'){
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://v2.convertapi.com/convert/'.$doc_extension.'/to/pdf?Secret=3IzXcCrPbaJxl8Nk');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    $post = array(
                        'File' => base_url('uploads/documents/'.$document),
                        'StoreFile' => 'true',
                        'PageRange' => '1',
                    );
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        
                    $result = curl_exec($ch);
                    $data = json_decode($result);
                    // echo '<pre>';
                    // print_r($data);die;
                    
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
                    
                    curl_close($ch);
        
                    $url = $data->Files[0]->Url;
                    
                    $img_path = '/home/ipgdal5/public_html/uploads/document_thumbnail/'.$doc_ext[0].'.pdf';
                    
                    // Save image
                    $ch = curl_init($url);
                    $fp = fopen($img_path, 'wb');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://v2.convertapi.com/convert/pdf/to/jpg?Secret=3IzXcCrPbaJxl8Nk');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    $post = array(
                        'File' => base_url('uploads/document_thumbnail/'.$doc_ext[0].'.pdf'),
                        'StoreFile' => 'true'
                    );
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        
                    $result = curl_exec($ch);
                    $data = json_decode($result);
                    // echo '<pre>';
                    // print_r($data);die;
                    
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
                    
                    curl_close($ch);
        
                    $url = $data->Files[0]->Url;
                    
                    $img_path = '/home/ipgdal5/public_html/uploads/document_thumbnail/'.$doc_ext[0].'.jpg';
                    
                    // Save image
                    $ch = curl_init($url);
                    $fp = fopen($img_path, 'wb');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);
                    unlink('/home/ipgdal5/public_html/uploads/document_thumbnail/'.$doc_ext[0].'.pdf');
                }
                
                $result = $this->site_model->addDocuments($document, $doc_extension, $user_data['company_id'], $user_data['id']);
                $document_id[$i] = $result;
                $i++;
            }
            $this->session->set_userdata('document_session', $document_id);
        }
        if ($result) {
            $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Documents Added Sucessfully', 'url' => base_url('/document_tags')]));
            return FALSE;
        } else {
            $this->output->set_output(json_encode(['result' => -1, 'msg' => 'Documents Not Added']));
            return FALSE;
        }
    }

    public function document_tags(){
        if(empty($this->is_login())){
            redirect(base_url('/'));
        }
        $data['title'] = "Document Tags";
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['tag_title'] = $this->site_model->getTagTitleByCompanyId($user_data['company_id']);
        $i = 0;
        foreach($data['tag_title'] as $tag_title){
            $data['tag_title'][$i]['tag_names'] = $this->site_model->getTagNamesByTagTitleId($tag_title['id']);
            $i++;
        }
        $data['industry'] = $this->site_model->getDocumentIndustriesByCompanyId($user_data['company_id']);
        $j = 0;
        foreach($data['industry'] as $industries){
            $data['industry'][$j]['sub_industries'] = $this->site_model->getDocumentSubIndustryByIndustryId($industries['id']);
            $j++;
        }
        $document_id = $this->session->userdata('document_session');
        if(empty($document_id)){
            redirect(base_url('/document_untagged'));
        }
        $j = 0;
        $document = [];
        foreach ($document_id as $document_ids) {
            $document[$j] = $this->site_model->getDocumentsByDocumentId($document_ids);
            $j++;
        }
        $data['documents'] = $document;
        $this->load->view('user/commons/header', $data);
        $this->load->view('user/commons/document_header');
        $this->load->view('user/document_tags');
        $this->load->view('user/commons/footer');
    }

    public function document_untagged(){
        if(empty($this->is_login())){
            redirect(base_url('/'));
        }
        $data['title'] = "Untagged Documents";
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['untagged_documents'] = $this->site_model->getUntaggedDocumentsByUserAndCompanyIdCount($user_data['company_id'], $user_data['id']);
        $data['tag_title'] = $this->site_model->getTagTitleByCompanyId($user_data['company_id']);
        $i = 0;
        foreach($data['tag_title'] as $tag_title){
            $data['tag_title'][$i]['tag_names'] = $this->site_model->getTagNamesByTagTitleId($tag_title['id']);
            $i++;
        }

        $data['industry'] = $this->site_model->getDocumentIndustriesByCompanyId($user_data['company_id']);
        $j = 0;
        foreach($data['industry'] as $industries){
            $data['industry'][$j]['sub_industries'] = $this->site_model->getDocumentSubIndustryByIndustryId($industries['id']);
            $j++;
        }

        $per_page = 10;
        $data['per_page'] = $per_page;
        $page_value = $this->input->post('page_value');
        if(!empty($page_value)){
            $per_page = $page_value;
            $data['per_page'] = $per_page;
        }
        if (!empty($start)) {
            $srt = $start;
        } else {
            $srt = 0;
        }

        $this->load->library('pagination');
        $config = array();
        $config['base_url'] = base_url('document_untagged/');
        $config['total_rows'] = count($data['untagged_documents']);
        $config['per_page'] = $per_page;
        // $choice = $config["total_rows"] / $config["per_page"];
        // $config["num_links"] = floor($choice);

        $config['first_link']       = 'First';
        $config['last_link']        = 'Last';
        $config['next_link']        = 'Next';
        $config['prev_link']        = 'Prev';
        $config['full_tag_open']    = '<div class="pagging text-center"><nav><ul class="pagination justify-content-center">';
        $config['full_tag_close']   = '</ul></nav></div>';
        $config['num_tag_open']     = '<li class="document_paginate page-item"><span class="page-link">';
        $config['num_tag_close']    = '</span></li>';
        $config['cur_tag_open']     = '<li class="document_paginate page-item active"><span class="page-link">';
        $config['cur_tag_close']    = '<span class="sr-only">(current)</span></span></li>';
        $config['next_tag_open']    = '<li class="document_paginate page-item"><span class="page-link">';
        $config['next_tagl_close']  = '<span aria-hidden="true">&raquo;</span></span></li>';
        $config['prev_tag_open']    = '<li class="document_paginate page-item"><span class="page-link">';
        $config['prev_tagl_close']  = '</span>Next</li>';
        $config['first_tag_open']   = '<li class="document_paginate page-item"><span class="page-link">';
        $config['first_tagl_close'] = '</span></li>';
        $config['last_tag_open']    = '<li class="document_paginate page-item"><span class="page-link">';
        $config['last_tagl_close']  = '</span></li>';

        $this->pagination->initialize($config);
        // $srt = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
        $data['untagged_documents'] = $this->site_model->getUntaggedDocumentsByUserAndCompanyId($user_data['company_id'], $user_data['id'], $config["per_page"], $srt);
        $data['pagination'] = $this->pagination->create_links();

        $this->load->view('user/commons/header', $data);
        $this->load->view('user/commons/document_header');
        $this->load->view('user/document_untagged');
        $this->load->view('user/commons/footer');
    }

    public function getUntaggedDocumentWrapper($start = null){
        $this->output->set_content_type('application/json');

        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['tag_title'] = $this->site_model->getTagTitleByCompanyId($user_data['company_id']);
        $i = 0;
        foreach($data['tag_title'] as $tag_title){
            $data['tag_title'][$i]['tag_names'] = $this->site_model->getTagNamesByTagTitleId($tag_title['id']);
            $i++;
        }

        $data['industry'] = $this->site_model->getDocumentIndustriesByCompanyId($user_data['company_id']);
        $j = 0;
        foreach($data['industry'] as $industries){
            $data['industry'][$j]['sub_industries'] = $this->site_model->getDocumentSubIndustryByIndustryId($industries['id']);
            $j++;
        }

        $data['untagged_documents'] = $this->site_model->getUntaggedDocumentsByUserAndCompanyIdCount($user_data['company_id'], $user_data['id']);

        $per_page = 10;
        $data['per_page'] = $per_page;
        $page_value = $this->input->post('page_value');
        if(!empty($page_value)){
            $per_page = $page_value;
            $data['per_page'] = $per_page;
        }
        if (!empty($start)) {
            $srt = $start;
        } else {
            $srt = 0;
        }

        $this->load->library('pagination');
        $config = array();
        $config['base_url'] = base_url('document_untagged2/');
        $config['total_rows'] = count($data['untagged_documents']);
        $config['per_page'] = $per_page;
        // $choice = $config["total_rows"] / $config["per_page"];
        // $config["num_links"] = floor($choice);

        $config['first_link']       = 'First';
        $config['last_link']        = 'Last';
        $config['next_link']        = 'Next';
        $config['prev_link']        = 'Prev';
        $config['full_tag_open']    = '<div class="pagging text-center"><nav><ul class="pagination justify-content-center">';
        $config['full_tag_close']   = '</ul></nav></div>';
        $config['num_tag_open']     = '<li class="document_paginate page-item"><span class="page-link">';
        $config['num_tag_close']    = '</span></li>';
        $config['cur_tag_open']     = '<li class="document_paginate page-item active"><span class="page-link">';
        $config['cur_tag_close']    = '<span class="sr-only">(current)</span></span></li>';
        $config['next_tag_open']    = '<li class="document_paginate page-item"><span class="page-link">';
        $config['next_tagl_close']  = '<span aria-hidden="true">&raquo;</span></span></li>';
        $config['prev_tag_open']    = '<li class="document_paginate page-item"><span class="page-link">';
        $config['prev_tagl_close']  = '</span>Next</li>';
        $config['first_tag_open']   = '<li class="document_paginate page-item"><span class="page-link">';
        $config['first_tagl_close'] = '</span></li>';
        $config['last_tag_open']    = '<li class="document_paginate page-item"><span class="page-link">';
        $config['last_tagl_close']  = '</span></li>';

        $this->pagination->initialize($config);
        // $srt = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
        $data['untagged_documents'] = $this->site_model->getUntaggedDocumentsByUserAndCompanyId($user_data['company_id'], $user_data['id'], $config["per_page"], $srt);
        $data['pagination'] = $this->pagination->create_links();

        $content_wrapper = $this->load->view('user/commons/document-untagged-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function document_search(){
        if(empty($this->is_login())){
            redirect(base_url('/'));
        }
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['tag_title'] = $this->site_model->getTagTitleByCompanyId($user_data['company_id']);
        $data['doc_title'] = $this->site_model->getTaggedDocumentByCompanyId($user_data['company_id'], $user_data['id']);
        $i = 0;
        foreach($data['tag_title'] as $tag_title){
            $data['tag_title'][$i]['tag_names'] = $this->site_model->getTagNamesByTagTitleId($tag_title['id']);
            $i++;
        }
        $data['industry'] = $this->site_model->getDocumentIndustriesByCompanyId($user_data['company_id']);
        $j = 0;
        foreach($data['industry'] as $industries){
            $data['industry'][$j]['sub_industries'] = $this->site_model->getDocumentSubIndustryByIndustryId($industries['id']);
            $j++;
        }
        $data['title'] = "Search Document";
        $this->load->view('user/commons/header', $data);
        $this->load->view('user/commons/document_header');
        $this->load->view('user/document_search');
        $this->load->view('user/commons/footer');
    }

    public function document_folder_search(){
        if(empty($this->is_login())){
            redirect(base_url('/'));
        }
        $data['title'] = "Document Folder Search";
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['client_name'] = $this->site_model->getClientNameByToken($this->session->userdata('token'));
        $data['folders'] = $this->site_model->getFoldersListByCompanyId($user_data['company_id']);
        $this->load->view('user/commons/header', $data);
        $this->load->view('user/commons/document_header');
        $this->load->view('user/document_folder_search');
        $this->load->view('user/commons/footer');
    }

    public function downloadFile($file_id){
        $this->output->set_content_type('application/json');
        $id = substr($file_id, 10);
        $fil_id = substr($id, 0, -10);
        $this->load->helper('file');
        $file = $this->site_model->getFileByDocumentId($fil_id);
        $data = file_get_contents(base_url('uploads/documents/'.$file['file']));
        $name = $file['file'];
        force_download($name, $data);
    }

    public function deleteUntaggedDocument($doc_id){
        $this->output->set_content_type('application/json');
        $document = $this->site_model->getDocumentsByDocumentId($doc_id);
        if(empty($document)){
            $document = $this->site_model->getTaggedDocumentsByDocumentId($doc_id);
        }
        $path = './uploads/documents/';
        unlink($path.$document['file']);
        $this->site_model->do_delete_untagged_document($doc_id);
        $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Document Deleted Successfully.', 'url' => base_url('/document_folder_search')]));
        return FALSE;
    }

    public function deleteMultipleDocuments(){
        $this->output->set_content_type('application/json');
        $doc_id = $this->input->post('doc_id');
        foreach($doc_id as $doc_ids){
            $this->site_model->do_delete_multiple_documents($doc_ids);
        }
        $this->output->set_output(json_encode(['doc_id' => $doc_id]));
        return FALSE;
    }

    public function downloadDocumentsZip($folder_id = null){
        $this->output->set_content_type('application/json');
        $path = './uploads/documents/';
        if(!empty($folder_id)){
            $folder_documents = $this->site_model->getDocumentsByFolderId($folder_id);
            $folder_name = $this->site_model->getFolderNameByFolderId($folder_id);
            $zipName   = $folder_name['folder_name'];
            $documents = [];
            $i = 0;
            foreach($folder_documents as $folder_document){
                $documents[$i] = $folder_document['file'];
                $i++;
            }
            foreach ($documents as $row){
                $this->zip->read_file($path.$row, true);
            }
        }else{
            $document_array = $this->input->post('untagged_documents');
            $zipName = 'DAL Documents';
            $pathFile = './uploads/documents/';
            $array = [];
            if(!empty($document)){
                foreach($document_array as $document_arrays){
                    $tagged_documents = $this->site_model->getFileByDocumentId($document_arrays);
                    array_push($array, $tagged_documents);
                }
            }
            $document = [];
            $i = 0;

            foreach($array as $arrays){
                $document[$i] = $arrays['file'];
                $i++;   
            }
            if(!empty($document)){
                foreach ($document as $row){
                    $this->zip->read_file($path.$row, true);
                }
            }
        }
        $this->zip->download($zipName);
    }

    public function do_tag_documents(){
        $this->output->set_content_type('application/json');
        $document_id = explode(',', $this->input->post('doc_id'));
        $sub_tags_id = $this->input->post('sub_tags_id');

        $industry_id = $this->input->post('industry_id');
        $sub_industry_id = $this->input->post('sub_industry_id');
        $site_url = explode('ipg-dal.com/', $_SERVER['HTTP_REFERER']);
        $i=0;
        foreach($document_id as $document_ids){
            $deletetagsdocument = $this->site_model->deleteTagsByDocumentId($document_ids);
        }
        if(!empty($industry_id)){
            if(empty($sub_industry_id)){
                foreach ($industry_id as $industry_ids) {
                    $industry[$i] = $this->site_model->getDocumentSubIndustryIdsByIndustryId($industry_ids);
                    $i++;
                }
            }else{
                foreach ($sub_industry_id as $sub_industry_ids) {
                    $industry[$i] = $this->site_model->getDocumentSubIndustryIdsByIndustryIds($sub_industry_ids);
                    $i++;
                }
            }
        }
        $industry_data = [];
        if(!empty($industry_id)){
            foreach($industry as $arr){
                $industry_data = array_merge($industry_data , $arr);
            }
        }
        foreach($sub_tags_id as $sub_tags_ids){
            $tag_id[$i] = $this->site_model->getTagIdBySubTagId($sub_tags_ids);
            $i++;
        }
        if(!empty($industry_data)){
            foreach($tag_id as $tag_ids){
                foreach ($document_id as $document_ids) {
                    foreach ($industry_data as $industry_datas) {
                        $result = $this->site_model->insertTaggedDocuments($industry_datas['id'], $industry_datas['industry_id'], $tag_ids['id'], $tag_ids['tag_id'], $document_ids);
                        $updateStatus = $this->site_model->updateUntaggedDocStatus($document_ids);
                    }
                }
            }
        }else{
            foreach($tag_id as $tag_ids){
                foreach ($document_id as $document_ids) {
                    $result = $this->site_model->insertTaggedDocumentsNoIndustry($tag_ids['id'], $tag_ids['tag_id'], $document_ids);
                    $updateStatus = $this->site_model->updateUntaggedDocStatus($document_ids);
                }
            }
        }
        if($result){
            if($site_url[1] == 'document_search'){
                $this->output->set_output(json_encode(['result' => 2, 'msg' => 'Documents Tagged Successfully.']));
            }else{
                $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Documents Tagged Successfully.', 'url' => base_url('document_untagged')]));
                return FALSE;
            }
        }else{
            $this->output->set_output(json_encode(['result' => -3, 'msg' => 'Documents Not Tagged.']));
            return FALSE;
        }
    }

    public function showDocumentFolder($document_id = null, $page_url){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        if(!empty($document_id)) {
            $data['page_url'] = $page_url;
            $data['document'] = $this->site_model->getFileByDocumentId($document_id);
            $folders = $this->site_model->getFoldersNameByCompanyId($document_id, $user_data['company_id']);
            $i = 0;
            foreach($folders as $folder){
                $arrayFolder[$i] = $folder['id'];
                $i++;    
            }

            $addedDocFolders = $this->site_model->getAddedDocFolders($document_id);
            $j = 0;
            foreach($addedDocFolders as $addedDocFolder){
                $arrayFolders[$j] = $addedDocFolder['folder_id'];
                $j++;    
            }

            if(!empty($arrayFolders)){
                $data['folders'] = $this->site_model->getRemainingFolders($arrayFolders, $user_data['company_id']);
            }else{
                $data['folders'] = $this->site_model->getFoldersNameByCompanyId($document_id, $user_data['company_id']);
            }
            $data['folder_name'] = $this->site_model->getFolderNamesByDocumentId($document_id);
        }
        $content_wrapper = $this->load->view('user/commons/show-folder-wrapper', $data, true);
        $folder_wrapper = $this->load->view('user/commons/document-folder-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper, 'folder_wrapper' => $folder_wrapper]));
        return FALSE;
    }

    public function editDocumentName($id){
        $this->output->set_content_type('application/json');
        if(!empty($id)){
            $data['image'] = $this->site_model->getImageNameByImageId($id);            
        }
        $content_wrapper = $this->load->view('user/commons/edit-image-name-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function doEditDocumentName($id){
        $this->site_model->updateDocumentName($this->input->post('id'), $this->input->post('data'));
        return FALSE;
    }

    public function showDocumentDownload($document_id = null){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        if(!empty($document_id)) {
            $data['document'] = $this->site_model->getFileByDocumentId($document_id);
        }
        $content_wrapper = $this->load->view('user/commons/document-download-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function document_untagged_tag_wrapper(){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['tag_title'] = $this->site_model->getTagTitleByCompanyId($user_data['company_id']);
        $i = 0;
        foreach($data['tag_title'] as $tag_title){
            $data['tag_title'][$i]['tag_names'] = $this->site_model->getTagNamesByTagTitleId($tag_title['id']);
            $i++;
        }
        $data['industry'] = $this->site_model->getDocumentIndustriesByCompanyId($user_data['company_id']);
        $j = 0;
        foreach($data['industry'] as $industries){
            $data['industry'][$j]['sub_industries'] = $this->site_model->getDocumentSubIndustryByIndustryId($industries['id']);
            $j++;
        }
        $doc_id = $this->input->post('doc_id');

        $data['tagged_tags'] = $this->site_model->getTaggedTagsByDocumentId($doc_id);
        $j = 0;
        if(!empty($data['tagged_tags'])){
            foreach ($data['tagged_tags'] as $value) {
                $subtag_id[$j] = $value['id'];
                $j++;
            }
            $data['subtag_id'] = $subtag_id;
        }

        $data['tagged_industries'] = $this->site_model->getTaggedIndustriesByDocumentId($doc_id);
        $z = 0;
        if(!empty($data['tagged_industries'])){
            foreach ($data['tagged_industries'] as $value) {
                $industry_id[$z] = $value['id'];
                $z++;
            }
            $data['industry_id'] = $industry_id;
        }
        
        if(!empty($doc_id)){
            $data['single_document'] = $this->site_model->getFileByDocumentId($doc_id);
        }

        $data['tagged_sub_industries'] = $this->site_model->getTaggedSubIndustriesByDocumentId($doc_id);
        $x = 0;
        if(!empty($data['tagged_sub_industries'])){
            foreach ($data['tagged_sub_industries'] as $value) {
                $sub_industry_id[$x] = $value['id'];
                $x++;
            }
            $data['sub_industry_id'] = $sub_industry_id;
        }
        if(!empty($image_id)){
            $data['single_image'] = $this->site_model->getImagesByImageId($image_id);
        }

        $document_id = $this->input->post('document_id');
        if(!empty($document_id)){
            $array = [];
            $i = 0;
            foreach($document_id as $document_ids){
                $multiple_documents = $this->site_model->getFileByDocumentId($document_ids);
                $array[$i] = $multiple_documents;
                $i++;
            }
            $data['multiple_documents'] = $array;
        }
        $content_wrapper = $this->load->view('user/commons/document-tag-modal-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function do_tag_added_documents(){
        $this->output->set_content_type('application/json');
        $document_id = explode(',', $this->input->post('doc_id'));
        $sub_tags_id = $this->input->post('sub_tags_id');

        $industry_id = $this->input->post('industry_id');
        $sub_industry_id = $this->input->post('sub_industry_id');
        $site_url = explode('ipg-dal.com/', $_SERVER['HTTP_REFERER']);
        $i=0;
        foreach($document_id as $document_ids){
            $deletetagsdocument = $this->site_model->deleteTagsByDocumentId($document_ids);
        }
        if(!empty($industry_id)){
            if(empty($sub_industry_id)){
                foreach ($industry_id as $industry_ids) {
                    $industry[$i] = $this->site_model->getDocumentSubIndustryIdsByIndustryId($industry_ids);
                    $i++;
                }
            }else{
                foreach ($sub_industry_id as $sub_industry_ids) {
                    $industry[$i] = $this->site_model->getDocumentSubIndustryIdsByIndustryIds($sub_industry_ids);
                    $i++;
                }
            }
        }
        // dd($industry);die;
        $industry_data = [];
        if(!empty($industry_id)){
            foreach($industry as $arr){
                $industry_data = array_merge($industry_data , $arr);
            }
        }
        foreach($sub_tags_id as $sub_tags_ids){
            $tag_id[$i] = $this->site_model->getTagIdBySubTagId($sub_tags_ids);
            $i++;
        }
        if(!empty($industry_data)){
            foreach($tag_id as $tag_ids){
                foreach ($document_id as $document_ids) {
                    foreach ($industry_data as $industry_datas) {
                        $result = $this->site_model->insertTaggedDocuments($industry_datas['id'], $industry_datas['industry_id'], $tag_ids['id'], $tag_ids['tag_id'], $document_ids);
                        $updateStatus = $this->site_model->updateUntaggedDocStatus($document_ids);
                    }
                }
            }
        }else{
            foreach($tag_id as $tag_ids){
                foreach ($document_id as $document_ids) {
                    $result = $this->site_model->insertTaggedDocumentsNoIndustry($tag_ids['id'], $tag_ids['tag_id'], $document_ids);
                    $updateStatus = $this->site_model->updateUntaggedDocStatus($document_ids);
                }
            }
        }
        $document_ids = $this->session->userdata('document_session');
        if(in_array($document_id[0], $document_ids)){
            $array_without = array_diff($document_ids, array($document_id[0]));
        }
        $this->session->set_userdata('document_session', $array_without);
        if($result){
            if($site_url[1] == 'document_search'){
                $this->output->set_output(json_encode(['result' => 2, 'msg' => 'Documents Tagged Successfully.']));
            }else{
                $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Documents Tagged Successfully.', 'url' => base_url('document_tags')]));
                return FALSE;
            }
        }else{
            $this->output->set_output(json_encode(['result' => -3, 'msg' => 'Documents Not Tagged.']));
            return FALSE;
        }
    }

    // public function do_search_documents($start=null){
    //     $this->output->set_content_type('application/json');
    //     $data['user_data'] = $user_data = $this->getDataByUniqueId();
    //     $docs_title = $this->input->post('doc_title');
    //     $file_type = $this->input->post('file_type');
    //     $tag_arr = [];
    //     $sub_tags_arr = [];
    //     $i = 0;
    //     $data['tags'] = $this->input->post('array_tags');
    //     $industry_id = $this->input->post('industry_id');

    //     if(!empty($industry_id)){
    //         $ind_name = $this->site_model->getIndustryNameByIndustryId($industry_id);
    //     }
        
    //     $per_page = 8;
    //     $data['per_page'] = $per_page;
    //     $page_value = $this->input->post('page_value');
    //     if(!empty($page_value)){
    //         $per_page = $page_value;
    //         $data['per_page'] = $per_page;
    //     }
    //     if (!empty($start)) {
    //         $srt = $start;
    //         $page_number = ($start / $per_page) + 1;
    //     } else {
    //         $srt = 0;
    //         $page_number = 1;
    //     }

    //     if(!empty($docs_title) && empty($data['tags'])){
    //         $i = 0;
    //         foreach ($file_type as $file_types) {
    //             $document_data = $this->site_model->getDocumentByDocumentTitle($user_data['company_id'], $docs_title, $file_types);
    //             if(!empty($document_data)){
    //                 foreach ($document_data as $document_datas) {
    //                     $data['document_title'][$i] = $document_datas;
    //                     $i++;
    //                 }
    //             }else{
    //                 //$data['document_title'] = [];
    //             }
    //         }
    //     }

    //     $flag=0;
    //     $check_sub_ind = [];
    //     if(!empty($data['tags']) && empty($docs_title)){
    //         $mainTag=[];
    //         $k = 0;
    //         foreach($data['tags'] as $tag){
    //             $explode = explode('| ', $tag);
                
    //             $tag_id = $this->site_model->getTagIds($explode[0], $user_data['company_id']);
    //             if(!empty($tag_id)){
    //                 array_push($tag_arr, $tag_id['id']);
    //             }
                
    //             $j = 0;
    //             $check_sub_industry = $this->site_model->getSubIndustry($explode[1]);
    //             if(!empty($check_sub_industry)){
    //                 array_push($check_sub_ind, $check_sub_industry);
    //             }

    //             $sub_tag_id = $this->site_model->getSubTagIds($explode[1], $user_data['company_id']);
    //             $j++;
                
    //             $mainTag[$explode[0]][$k] = $sub_tag_id['id'];

    //             $k++;

    //             if(!empty($sub_tag_id['id'])){
    //                 array_push($sub_tags_arr, $sub_tag_id['id']);
    //             }
    //         }
    //         if(in_array('sub_industry', $check_sub_ind)){
    //             $flag = 1;
    //         }
    //         $simplified_sub_ind = [];
    //         if(!empty($industry_id) && $flag==0){
    //             $sub_industries = $this->site_model->getDocumentSubIndustryByIndustryId($industry_id);
    //             $sub_idustry_id = [];
    //             $p = 0;
    //             foreach ($sub_industries as $sub_industry) {
    //                 $sub_idustry_id[$p] = $sub_industry['id'];
    //                 $p++;
    //             }
    //             foreach ($sub_idustry_id as $sub_idustry_ids) {
    //                 $sub_ind_document = $this->site_model->getDocumentsBySubIndustry($sub_idustry_ids, $user_data['company_id']);
    //             }
    //             $m = 0;
               
    //             foreach ($sub_ind_document as $sub_ind_documents) {
    //                 $simplified_sub_ind[$sub_industries[0]['industry_name']][$m]=$sub_ind_documents['document_id'];
    //                 $m++;
    //             }
    //         }
            
    //         foreach($mainTag as $key => $value){
    //             $checkKey = $this->site_model->getKeyType(str_replace(' ','', $key));
    //             $a = 0;
    //             $simplified_data=[];
    //             foreach ($value as $mainSubTags) {
    //                 if($checkKey == 'tags'){
    //                     $sub_tag = $this->site_model->getDocumentsBySubTags($mainSubTags, $user_data['company_id']);
    //                 }else{
    //                     $sub_tag = $this->site_model->getDocumentsBySubIndustry($mainSubTags, $user_data['company_id']);
    //                 }
    //                 foreach ($sub_tag as $sub) {
    //                     $simplified_data[$a]=$sub['document_id'];
    //                     $a++;
    //                 }
    //             }
    //             $new_data[$key] = $simplified_data;
    //         }
    //         foreach ($new_data as $key => $value) {
    //             $common_data[$key] = array_unique($value);
    //         }

    //         if(!empty($simplified_sub_ind)){
    //             $arr=$simplified_sub_ind;
    //             $common_data[$ind_name['industry_name']]=$simplified_sub_ind[$ind_name['industry_name']];
    //         }
    //         $flag_arrays=[];
    //         foreach ($common_data as $key => $value) {
    //             $flag_arrays=$value;
    //             break;
    //         }
    //         $final_arr=[];
    //         $fi=0;
    //         foreach ($flag_arrays as $flag_a) {
    //             $flag=0;
    //             foreach ($common_data as $key => $value) {
    //                 $res= in_array($flag_a, $value);
    //                 if($res){
    //                   $flag=1;  
    //               }else{
    //                 $flag=0;
    //                 break;
    //               } 
    //             }
    //             if($flag){
    //                 $final_arr[$fi] = $flag_a;
    //                 $fi++;
    //             }
    //         }
    //         $searched_data = [];
    //         $final = 0;

    //         foreach ($final_arr as $final_array) {
    //             foreach ($file_type as $file_types) {
    //                 $arr = $this->site_model->getSearchedDocumentsByDocId($final_array, $file_types);
    //                 if(!empty($arr)){
    //                     $searched_data[$final] =$arr; 
    //                     $final++;    
    //                 }
    //             }
    //         }
    //         $data['document_title'] = $searched_data;
    //     }

    //     if(!empty($data['tags'] && $docs_title)){
    //         $mainTag=[];
    //         $k = 0;
    //         foreach($data['tags'] as $tag){
    //             $explode = explode('| ', $tag);
                
    //             $tag_id = $this->site_model->getTagIds($explode[0], $user_data['company_id']);
    //             array_push($tag_arr, $tag_id['id']);
                
    //             $j = 0;
    //             $check_sub_industry = $this->site_model->getSubIndustry($explode[1]);
    //             if(!empty($check_sub_industry)){
    //                 array_push($check_sub_ind, $check_sub_industry);
    //             }

    //             $sub_tag_id = $this->site_model->getSubTagIds($explode[1], $user_data['company_id']);
    //             $j++;
                
    //             $mainTag[$explode[0]][$k] = $sub_tag_id['id'];

    //             $k++;

    //             if(!empty($sub_tag_id['id'])){
    //                 array_push($sub_tags_arr, $sub_tag_id['id']);
    //             }
    //         }
    //         if(in_array('sub_industry', $check_sub_ind)){
    //             $flag = 1;
    //         }
    //         $simplified_sub_ind = [];
    //         if(!empty($industry_id) && $flag==0){
    //             $sub_industries = $this->site_model->getDocumentSubIndustryByIndustryId($industry_id);
    //             $sub_idustry_id = [];
    //             $p = 0;
    //             foreach ($sub_industries as $sub_industry) {
    //                 $sub_idustry_id[$p] = $sub_industry['id'];
    //                 $p++;
    //             }
    //             foreach ($sub_idustry_id as $sub_idustry_ids) {
    //                 $sub_ind_document = $this->site_model->getDocumentsBySubIndustry($sub_idustry_ids, $user_data['company_id']);
    //             }
    //             $m = 0;
               
    //             foreach ($sub_ind_document as $sub_ind_documents) {
    //                 $simplified_sub_ind[$sub_industries[0]['industry_name']][$m]=$sub_ind_documents['document_id'];
    //                 $m++;
    //             }
    //         }
            
    //         foreach($mainTag as $key => $value){
    //             $checkKey = $this->site_model->getKeyType(str_replace(' ','', $key));
    //             $a = 0;
    //             $simplified_data=[];
    //             foreach ($value as $mainSubTags) {
    //                 if($checkKey == 'tags'){
    //                     $sub_tag = $this->site_model->getDocumentsBySubTags($mainSubTags, $user_data['company_id']);
    //                 }else{
    //                     $sub_tag = $this->site_model->getDocumentsBySubIndustry($mainSubTags, $user_data['company_id']);
    //                 }
    //                 foreach ($sub_tag as $sub) {
    //                     $simplified_data[$a]=$sub['document_id'];
    //                     $a++;
    //                 }
    //             }
    //             $new_data[$key] = $simplified_data;
    //         }
    //         foreach ($new_data as $key => $value) {
    //             $common_data[$key] = array_unique($value);
    //         }

    //         if(!empty($simplified_sub_ind)){
    //             $arr=$simplified_sub_ind;
    //             $common_data[$ind_name['industry_name']]=$simplified_sub_ind[$ind_name['industry_name']];
    //         }
    //         $flag_arrays=[];
    //         foreach ($common_data as $key => $value) {
    //             $flag_arrays=$value;
    //             break;
    //         }
    //         $final_arr=[];
    //         $fi=0;
    //         foreach ($flag_arrays as $flag_a) {
    //             $flag=0;
    //             foreach ($common_data as $key => $value) {
    //                 $res= in_array($flag_a, $value);
    //                 if($res){
    //                   $flag=1;  
    //               }else{
    //                 $flag=0;
    //                 break;
    //               } 
    //             }
    //             if($flag){
    //                 $final_arr[$fi] = $flag_a;
    //                 $fi++;
    //             }
    //         }
    //         $searched_data = [];
    //         $final = 0;

    //         foreach ($final_arr as $final_array) {
    //             foreach ($file_type as $file_types) {
    //                 $arr = $this->site_model->getSearchedDocumentsByDocIdAndDocTitle($final_array, $file_types, $docs_title);
    //                 if(!empty($arr)){
    //                     $searched_data[$final] =$arr; 
    //                     $final++;    
    //                 }
    //             }
    //         }
    //         $data['document_title'] = $searched_data;
    //     }
        
    //     $this->load->library('pagination');
    //     $config = array();
    //     $config['base_url'] = base_url('document_search_page/');
    //     $config['total_rows'] = count($data['document_title']);
    //     $config['per_page'] = $per_page;  //show record per halaman
    //     // $choice = $config["total_rows"] / $config["per_page"];
    //     // $config["num_links"] = floor($choice);

    //     $config['first_link']       = 'First';
    //     $config['last_link']        = 'Last';
    //     $config['next_link']        = 'Next';
    //     $config['prev_link']        = 'Prev';
    //     $config['full_tag_open']    = '<div class="pagging text-center"><nav><ul class="pagination justify-content-center">';
    //     $config['full_tag_close']   = '</ul></nav></div>';
    //     $config['num_tag_open']     = '<li class="paginate_doc_search page-item"><span class="page-link">';
    //     $config['num_tag_close']    = '</span></li>';
    //     $config['cur_tag_open']     = '<li class="paginate_doc_search page-item active"><span class="page-link">';
    //     $config['cur_tag_close']    = '<span class="sr-only">(current)</span></span></li>';
    //     $config['next_tag_open']    = '<li class="paginate_doc_search page-item"><span class="page-link">';
    //     $config['next_tagl_close']  = '<span aria-hidden="true">&raquo;</span></span></li>';
    //     $config['prev_tag_open']    = '<li class="paginate_doc_search page-item"><span class="page-link">';
    //     $config['prev_tagl_close']  = '</span>Next</li>';
    //     $config['first_tag_open']   = '<li class="paginate_doc_search page-item"><span class="page-link">';
    //     $config['first_tagl_close'] = '</span></li>';
    //     $config['last_tag_open']    = '<li class="paginate_doc_search page-item"><span class="page-link">';
    //     $config['last_tagl_close']  = '</span></li>';

    //     $this->pagination->initialize($config);
    //     // $srt = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
    //     if(!empty($docs_title) && empty($data['tags'])){
    //         $arr=[];
    //         $j=0;
    //         /*for($i = $srt; $i < $config["per_page"]; $i++){
    //             if(!empty($data['document_title'][$i])){
    //                 $arr[$j] = $data['document_title'][$i];
    //                 $j++;    
    //             }
    //         }*/
            
    //         for($i = $srt; $i < $config["total_rows"], $i < $per_page * $page_number; $i++){
    //             if(!empty($data['document_title'][$i])){
    //                 $arr[$j] = $data['document_title'][$i];
    //                 $j++;    
    //             }
    //         }
    //         $data['document_title']=[];
    //         $data['doc_title'] = $arr;
    //     }

    //     if(!empty($data['tags']) && empty($docs_title)){
    //         $arr=[];
    //         $j=0;
    //         /*for($i = $srt; $i <= $config["per_page"]; $i++){
    //             if(!empty($data['document_title'][$i])){
    //                 $arr[$j] = $data['document_title'][$i];
    //                 $j++;    
    //             }
    //         }*/
            
    //         for($i = $srt; $i < $config["total_rows"], $i < $per_page * $page_number; $i++){
    //             if(!empty($data['document_title'][$i])){
    //                 $arr[$j] = $data['document_title'][$i];
    //                 $j++;    
    //             }
    //         }
    //         $data['document_title']=[];
    //         $data['doc_title'] = $arr;
    //     }

    //     if(!empty($data['tags'] && $docs_title)){
    //         $arr=[];
    //         $j=0;
    //         /*for($i = $srt; $i < $config["per_page"]; $i++){
    //             if(!empty($data['document_title'][$i])){
    //                 $arr[$j] = $data['document_title'][$i];
    //                 $j++;    
    //             }
    //         }*/
            
    //         for($i = $srt; $i < $config["total_rows"], $i < $per_page * $page_number; $i++){
    //             if(!empty($data['document_title'][$i])){
    //                 $arr[$j] = $data['document_title'][$i];
    //                 $j++;    
    //             }
    //         }
    //         $data['document_title']=[];
    //         $data['doc_title'] = $arr;
    //     }
        
    //     $data['pagination'] = $this->pagination->create_links();

    //     $content_wrapper = $this->load->view('user/commons/document-search-result-wrapper', $data, true);
    //     $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
    //     return FALSE;
    // }
    
    public function do_search_documents($start=null){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $docs_title = $this->input->post('doc_title');
        $file_type = $this->input->post('file_type');
        $tag_arr = [];
        $sub_tags_arr = [];
        $i = 0;
        $data['tags'] = $this->input->post('array_tags');
        $industry_id = $this->input->post('industry_id');
        if(!empty($industry_id)){
            $ind_name = $this->site_model->getIndustryNameByIndustryId($industry_id);
        }
        
        $per_page = 80;
        $data['per_page'] = $per_page;
        $page_value = $this->input->post('page_value');
        if(!empty($page_value)){
            $per_page = $page_value;
            $data['per_page'] = $per_page;
        }
        if (!empty($start)) {
            $srt = $start;
            $page_number = ($start / $per_page) + 1;
        } else {
            $srt = 0;
            $page_number = 1;
        }

        if(!empty($docs_title) && empty($data['tags'])){
            $i = 0;
            foreach ($file_type as $file_types) {
                $document_data = $this->site_model->getDocumentByDocumentTitle($user_data['company_id'], $docs_title, $file_types);
                if(!empty($document_data)){
                    foreach ($document_data as $document_datas) {
                        $data['document_title'][$i] = $document_datas;
                        $i++;
                    }
                }else{
                    //$data['document_title'] = [];
                }
            }
        }

        $flag=0;
        $check_sub_ind = [];
        if(!empty($data['tags']) && empty($docs_title)){
            $mainTag=[];
            $k = 0;
            foreach($data['tags'] as $tag){
                $explode = explode('| ', $tag);
                
                $tag_id = $this->site_model->getTagIds(trim($explode[0]), $user_data['company_id']);
                
                if(!empty($tag_id)){
                    array_push($tag_arr, $tag_id['id']);
                }
                
                $j = 0;
                $check_sub_industry = $this->site_model->getSubIndustry(trim($explode[1]));
                if(!empty($check_sub_industry)){
                    array_push($check_sub_ind, $check_sub_industry);
                }

                if(!empty($tag_id)){
                    $sub_tag_id = $this->site_model->getSubTagIds(trim($explode[1]), $user_data['company_id']);
                }else{
                    $sub_tag_id = $this->site_model->getSubTagIdsDocument(trim($explode[1]), $user_data['company_id']);
                }
    
                $j++;
                
                $mainTag[$explode[0]][$k] = $sub_tag_id['id'];

                $k++;

                if(!empty($sub_tag_id['id'])){
                    array_push($sub_tags_arr, $sub_tag_id['id']);
                }
            }
            if(in_array('sub_industry', $check_sub_ind)){
                $flag = 1;
            }
            $simplified_sub_ind = [];
            if(!empty($industry_id) && $flag==0){
                $sub_industries = $this->site_model->getDocumentSubIndustryByIndustryId($industry_id);
                $sub_idustry_id = [];
                $p = 0;
                foreach ($sub_industries as $sub_industry) {
                    $sub_idustry_id[$p] = $sub_industry['id'];
                    $p++;
                }
                foreach ($sub_idustry_id as $sub_idustry_ids) {
                    $sub_ind_document = $this->site_model->getDocumentsBySubIndustry($sub_idustry_ids, $user_data['company_id']);
                }
                $m = 0;
               
                foreach ($sub_ind_document as $sub_ind_documents) {
                    $simplified_sub_ind[$sub_industries[0]['industry_name']][$m]=$sub_ind_documents['document_id'];
                    $m++;
                }
            }
            
            foreach($mainTag as $key => $value){
                $checkKey = $this->site_model->getKeyType(str_replace(' ','', $key));
                $a = 0;
                $simplified_data=[];
                foreach ($value as $mainSubTags) {
                    if($checkKey == 'tags'){
                        $sub_tag = $this->site_model->getDocumentsBySubTags($mainSubTags, $user_data['company_id']);
                    }else{
                        $sub_tag = $this->site_model->getDocumentsBySubIndustry($mainSubTags, $user_data['company_id']);
                    }
                    foreach ($sub_tag as $sub) {
                        $simplified_data[$a]=$sub['document_id'];
                        $a++;
                    }
                }
                $new_data[$key] = $simplified_data;
            }
            foreach ($new_data as $key => $value) {
                $common_data[$key] = array_unique($value);
            }

            if(!empty($simplified_sub_ind)){
                $arr=$simplified_sub_ind;
                $common_data[$ind_name['industry_name']]=$simplified_sub_ind[$ind_name['industry_name']];
            }
            $flag_arrays=[];
            foreach ($common_data as $key => $value) {
                $flag_arrays=$value;
                break;
            }
            $final_arr=[];
            $fi=0;
            foreach ($flag_arrays as $flag_a) {
                $flag=0;
                foreach ($common_data as $key => $value) {
                    $res= in_array($flag_a, $value);
                    if($res){
                      $flag=1;  
                  }else{
                    $flag=0;
                    break;
                  } 
                }
                if($flag){
                    $final_arr[$fi] = $flag_a;
                    $fi++;
                }
            }
            $searched_data = [];
            $final = 0;

            foreach ($final_arr as $final_array) {
                foreach ($file_type as $file_types) {
                    $arr = $this->site_model->getSearchedDocumentsByDocId($final_array, $file_types);
                    if(!empty($arr)){
                        $searched_data[$final] =$arr; 
                        $final++;    
                    }
                }
            }
            $data['document_title'] = $searched_data;
        }

        if(!empty($data['tags'] && $docs_title)){
            $mainTag=[];
            $k = 0;
            foreach($data['tags'] as $tag){
                $explode = explode('| ', $tag);
                
                $tag_id = $this->site_model->getTagIds(trim($explode[0]), $user_data['company_id']);
                array_push($tag_arr, $tag_id['id']);
                
                $j = 0;
                $check_sub_industry = $this->site_model->getSubIndustry(trim($explode[1]));
                if(!empty($check_sub_industry)){
                    array_push($check_sub_ind, $check_sub_industry);
                }

                if(!empty($tag_id)){
                    $sub_tag_id = $this->site_model->getSubTagIds(trim($explode[1]), $user_data['company_id']);
                }else{
                    $sub_tag_id = $this->site_model->getSubTagIdsDocument(trim($explode[1]), $user_data['company_id']);
                }
                $j++;
                
                $mainTag[$explode[0]][$k] = $sub_tag_id['id'];

                $k++;

                if(!empty($sub_tag_id['id'])){
                    array_push($sub_tags_arr, $sub_tag_id['id']);
                }
            }
            if(in_array('sub_industry', $check_sub_ind)){
                $flag = 1;
            }
            $simplified_sub_ind = [];
            if(!empty($industry_id) && $flag==0){
                $sub_industries = $this->site_model->getDocumentSubIndustryByIndustryId($industry_id);
                $sub_idustry_id = [];
                $p = 0;
                foreach ($sub_industries as $sub_industry) {
                    $sub_idustry_id[$p] = $sub_industry['id'];
                    $p++;
                }
                foreach ($sub_idustry_id as $sub_idustry_ids) {
                    $sub_ind_document = $this->site_model->getDocumentsBySubIndustry($sub_idustry_ids, $user_data['company_id']);
                }
                $m = 0;
               
                foreach ($sub_ind_document as $sub_ind_documents) {
                    $simplified_sub_ind[$sub_industries[0]['industry_name']][$m]=$sub_ind_documents['document_id'];
                    $m++;
                }
            }
            
            foreach($mainTag as $key => $value){
                $checkKey = $this->site_model->getKeyType(str_replace(' ','', $key));
                $a = 0;
                $simplified_data=[];
                foreach ($value as $mainSubTags) {
                    if($checkKey == 'tags'){
                        $sub_tag = $this->site_model->getDocumentsBySubTags($mainSubTags, $user_data['company_id']);
                    }else{
                        $sub_tag = $this->site_model->getDocumentsBySubIndustry($mainSubTags, $user_data['company_id']);
                    }
                    foreach ($sub_tag as $sub) {
                        $simplified_data[$a]=$sub['document_id'];
                        $a++;
                    }
                }
                $new_data[$key] = $simplified_data;
            }
            foreach ($new_data as $key => $value) {
                $common_data[$key] = array_unique($value);
            }

            if(!empty($simplified_sub_ind)){
                $arr=$simplified_sub_ind;
                $common_data[$ind_name['industry_name']]=$simplified_sub_ind[$ind_name['industry_name']];
            }
            $flag_arrays=[];
            foreach ($common_data as $key => $value) {
                $flag_arrays=$value;
                break;
            }
            $final_arr=[];
            $fi=0;
            foreach ($flag_arrays as $flag_a) {
                $flag=0;
                foreach ($common_data as $key => $value) {
                    $res= in_array($flag_a, $value);
                    if($res){
                      $flag=1;  
                  }else{
                    $flag=0;
                    break;
                  } 
                }
                if($flag){
                    $final_arr[$fi] = $flag_a;
                    $fi++;
                }
            }
            $searched_data = [];
            $final = 0;

            foreach ($final_arr as $final_array) {
                foreach ($file_type as $file_types) {
                    $arr = $this->site_model->getSearchedDocumentsByDocIdAndDocTitle($final_array, $file_types, $docs_title);
                    if(!empty($arr)){
                        $searched_data[$final] =$arr; 
                        $final++;    
                    }
                }
            }
            $data['document_title'] = $searched_data;
        }
        
        $this->load->library('pagination');
        $config = array();
        $config['base_url'] = base_url('document_search_page/');
        $config['total_rows'] = count($data['document_title']);
        $config['per_page'] = $per_page;  //show record per halaman
        // $choice = $config["total_rows"] / $config["per_page"];
        // $config["num_links"] = floor($choice);

        $config['first_link']       = 'First';
        $config['last_link']        = 'Last';
        $config['next_link']        = 'Next';
        $config['prev_link']        = 'Prev';
        $config['full_tag_open']    = '<div class="pagging text-center"><nav><ul class="pagination justify-content-center">';
        $config['full_tag_close']   = '</ul></nav></div>';
        $config['num_tag_open']     = '<li class="paginate_doc_search page-item"><span class="page-link">';
        $config['num_tag_close']    = '</span></li>';
        $config['cur_tag_open']     = '<li class="paginate_doc_search page-item active"><span class="page-link">';
        $config['cur_tag_close']    = '<span class="sr-only">(current)</span></span></li>';
        $config['next_tag_open']    = '<li class="paginate_doc_search page-item"><span class="page-link">';
        $config['next_tagl_close']  = '<span aria-hidden="true">&raquo;</span></span></li>';
        $config['prev_tag_open']    = '<li class="paginate_doc_search page-item"><span class="page-link">';
        $config['prev_tagl_close']  = '</span>Next</li>';
        $config['first_tag_open']   = '<li class="paginate_doc_search page-item"><span class="page-link">';
        $config['first_tagl_close'] = '</span></li>';
        $config['last_tag_open']    = '<li class="paginate_doc_search page-item"><span class="page-link">';
        $config['last_tagl_close']  = '</span></li>';

        $this->pagination->initialize($config);
        // $srt = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;

        if(!empty($docs_title) && empty($data['tags'])){
            $arr=[];
            $j=0;
            /*for($i = $srt; $i < $config["per_page"]; $i++){
                if(!empty($data['document_title'][$i])){
                    $arr[$j] = $data['document_title'][$i];
                    $j++;    
                }
            }*/
            
            for($i = $srt; $i < $config["total_rows"], $i < $per_page * $page_number; $i++){
                if(!empty($data['document_title'][$i])){
                    $arr[$j] = $data['document_title'][$i];
                    $j++;    
                }
            }
            $data['document_title']=[];
            $data['doc_title'] = $arr;
        }

        if(!empty($data['tags']) && empty($docs_title)){
            $arr=[];
            $j=0;
            /*for($i = $srt; $i <= $config["per_page"]; $i++){
                if(!empty($data['document_title'][$i])){
                    $arr[$j] = $data['document_title'][$i];
                    $j++;    
                }
            }*/
            
            for($i = $srt; $i < $config["total_rows"], $i < $per_page * $page_number; $i++){
                if(!empty($data['document_title'][$i])){
                    $arr[$j] = $data['document_title'][$i];
                    $j++;    
                }
            }
            $data['document_title']=[];
            $data['doc_title'] = $arr;
        }

        if(!empty($data['tags'] && $docs_title)){
            $arr=[];
            $j=0;
            /*for($i = $srt; $i < $config["per_page"]; $i++){
                if(!empty($data['document_title'][$i])){
                    $arr[$j] = $data['document_title'][$i];
                    $j++;    
                }
            }*/
            
            for($i = $srt; $i < $config["total_rows"], $i < $per_page * $page_number; $i++){
                if(!empty($data['document_title'][$i])){
                    $arr[$j] = $data['document_title'][$i];
                    $j++;    
                }
            }
            $data['document_title']=[];
            $data['doc_title'] = $arr;
        }
        // dd($data['doc_title']);die;
        $data['pagination'] = $this->pagination->create_links();

        $content_wrapper = $this->load->view('user/commons/document-search-result-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function addMultipleDocumentsToFolder(){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['doc_array'] = $this->input->post('document_id');

        $array = [];
        foreach($data['doc_array'] as $document_arrays){
            $tagged_documents = $this->site_model->getFileByDocumentId($document_arrays);
            array_push($array, $tagged_documents);
        }
        $data['folders'] = $this->site_model->getDocumentFoldersNameByCompanyId($user_data['company_id']);
        $content_wrapper = $this->load->view('user/commons/save-multiple-document-folder-wrapper', $data, true);
        $folder_wrapper = $this->load->view('user/commons/multiple-document-folder-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper, 'folder_wrapper' => $folder_wrapper]));
        return FALSE;
    }

    public function insertMultipleDocumentsToFolder(){
        $this->output->set_content_type('application/json');
        $folder_id  = $this->security->xss_clean($this->input->post('folder_id'));
        $document_id  = $this->security->xss_clean($this->input->post('document_id'));
        $document_ids = explode(',', $document_id);
        // dd($document_ids);die;
        foreach($document_ids as $document_id){
            $this->site_model->deleteDocumentsFolders($folder_id, $document_id);
            $result = $this->site_model->insertDocumentToFolder($folder_id, $document_id);
        }
        if($result){
            $this->output->set_output(json_encode(['result' => 2, 'msg' => 'Documents saved to Folder Successfully!!', 'document_id' => $document_ids]));
            return FALSE;
        }else{
            $this->output->set_output(json_encode(['result' => -1, 'msg' => 'Something Went Wrong!!']));
            return FALSE;
        }
    }

    public function insertDocumentToFolder($page_url){
        $this->output->set_content_type('application/json');
        $folder_id  = $this->security->xss_clean($this->input->post('folder_id'));
        $document_id  = $this->security->xss_clean($this->input->post('document_id'));
        $result = $this->site_model->insertDocumentToFolder($folder_id, $document_id);
        if($result){
            if($page_url == 'document_tags'){
                $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Document saved to Folder Successfully!!', 'url' => base_url('document_tags')]));
            }else if($page_url == 'document_search'){
                $this->output->set_output(json_encode(['result' => 2, 'msg' => 'Document saved to Folder Successfully!!']));
            }else{
                $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Document saved to Folder Successfully!!', 'url' => base_url('document_untagged')]));
            }
            return FALSE;
        }else{
            $this->output->set_output(json_encode(['result' => -1, 'msg' => 'Something Went Wrong!!']));
            return FALSE;
        }
    }

    public function editFolder($folder_id = null){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        if(!empty($folder_id)) {
            $data['folder'] = $this->site_model->getFolderById($folder_id);
        }
        $content_wrapper = $this->load->view('user/commons/folder-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function editNewDocumentFolder($id){
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $this->site_model->editDocumentFolderName($id, $this->input->post('data'), $user_data['company_id']);
        return FALSE;
    }

    public function deleteFolder($id){
        $this->output->set_content_type('application/json');
        $folder_id = substr($id, 10);
        $fold_id = substr($folder_id, 0, -10);
        $this->site_model->do_delete_folder($fold_id);
        $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Folder Deleted Successfully.', 'url' => base_url('/document_untagged')]));
        return FALSE;
    }

    public function getFolderDocumentsByFolderId($folder_id = null){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        if(!empty($folder_id)){
            $data['folder_name'] = $this->site_model->getFolderNameByFolderId($folder_id);
            $data['documents'] = $this->site_model->getDocumentsByFolderId($folder_id);
        }
        $content_wrapper = $this->load->view('user/commons/folders-search-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function searchDocumentFolders(){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $data['folders'] = $this->site_model->getFoldersListByCompanyId($user_data['company_id']);
        $content_wrapper = $this->load->view('user/commons/list-document-folder-search-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function filterByFolderName(){
        $this->output->set_content_type('application/json');
        $data['user_data'] = $user_data = $this->getDataByUniqueId();
        $folder_name = $this->input->post('folder_name');
        $data['folders'] = $this->site_model->getDocumentFolderNameByFolderTitle($folder_name, $user_data['company_id']);
        $content_wrapper = $this->load->view('user/commons/list-document-folder-search-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 1, 'content_wrapper' => $content_wrapper]));
        return FALSE;
    }

    public function deleteDocumentsFromFolder($folder_id, $document_id){
        $this->output->set_content_type('application/json');
        $this->site_model->do_delete_documents_from_folder($folder_id, $document_id);
        if(!empty($folder_id)){
            $data['folder_name'] = $this->site_model->getFolderNameByFolderId($folder_id);
            $data['documents'] = $this->site_model->getDocumentsByFolderId($folder_id);
        }
        $content_wrapper = $this->load->view('user/commons/folders-search-wrapper', $data, true);
        $this->output->set_output(json_encode(['result' => 4, 'msg' => 'Document Deleted Successfully.', 'url' => base_url('/document_folder_search'),'html'=>$content_wrapper]));
        return FALSE;
    }

    public function doDeleteDocumentFolder($folder_name){
        $this->output->set_content_type('application/json');
        $folder_new_name = str_replace('-', ' ', $folder_name);
        $folder_id = $this->site_model->getFolderIdByFolderName($folder_new_name);
        $this->site_model->do_delete_folder($folder_id['id']);
        $this->output->set_output(json_encode(['result' => 1, 'msg' => 'Folder Deleted Successfully.', 'url' => base_url('/document_folder_search')]));
        return FALSE;
    }
}
?>