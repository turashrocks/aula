<?php
namespace App\Http\Controllers;

class NewsBoardController extends Controller {

	var $data = array();
	var $panelInit ;
	var $layout = 'dashboard';

	public function __construct(){
		if(app('request')->header('Authorization') != "" || \Input::has('token')){
			$this->middleware('jwt.auth');
		}else{
			$this->middleware('authApplication');
		}

		$this->panelInit = new \DashboardInit();
		$this->data['panelInit'] = $this->panelInit;
		$this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/languages');
		$this->data['users'] = $this->panelInit->getAuthUser();
		if(!isset($this->data['users']->id)){
			return \Redirect::to('/');
		}

	}

	public function listAll($page = 1)
	{

		if(!$this->panelInit->can( array("newsboard.list","newsboard.View","newsboard.addNews","newsboard.editNews","newsboard.delNews") )){
			exit;
		}

		$toReturn = array();
		if($this->data['users']->role == "admin" ){
			$toReturn['newsboard'] = \newsboard::orderby('newsDate','DESC')->take('20')->skip(20* ($page - 1) )->get()->toArray();
			$toReturn['totalItems'] = \newsboard::count();
		}else{
			 $toReturn['newsboard'] = \newsboard::where('newsFor',$this->data['users']->role)->orWhere('newsFor','all')->orderby('newsDate','DESC')->take('20')->skip(20* ($page - 1) )->get()->toArray();
			 $toReturn['totalItems'] = \newsboard::where('newsFor',$this->data['users']->role)->orWhere('newsFor','all')->count();
		}

		foreach ($toReturn['newsboard'] as $key => $item) {
			$toReturn['newsboard'][$key]['newsText'] = strip_tags(htmlspecialchars_decode($toReturn['newsboard'][$key]['newsText'],ENT_QUOTES));
			$toReturn['newsboard'][$key]['newsDate'] = $this->panelInit->unix_to_date($toReturn['newsboard'][$key]['newsDate']);
		}

		return $toReturn;
	}

	public function search($keyword,$page = 1)
	{
		
		if(!$this->panelInit->can( array("newsboard.View","newsboard.addNews","newsboard.editNews","newsboard.delNews") )){
			exit;
		}

		$toReturn = array();
		if($this->data['users']->role == "admin" ){
			$toReturn['newsboard'] = \newsboard::where('newsTitle','like','%'.$keyword.'%')->orWhere('newsText','like','%'.$keyword.'%')->take('20')->skip(20* ($page - 1) )->get()->toArray();
			$toReturn['totalItems'] = \newsboard::where('newsTitle','like','%'.$keyword.'%')->orWhere('newsText','like','%'.$keyword.'%')->count();
		}else{
			 $toReturn['newsboard'] = \newsboard::where('newsFor',$this->data['users']->role)->orWhere('newsFor','all')->where('newsTitle','like','%'.$keyword.'%')->orWhere('newsText','like','%'.$keyword.'%')->take('20')->skip(20* ($page - 1) )->get()->toArray();
			 $toReturn['totalItems'] = \newsboard::where('newsFor',$this->data['users']->role)->orWhere('newsFor','all')->where('newsTitle','like','%'.$keyword.'%')->orWhere('newsText','like','%'.$keyword.'%')->count();
		}

		foreach ($toReturn['newsboard'] as $key => $item) {
			$toReturn['newsboard'][$key]['newsText'] = strip_tags(htmlspecialchars_decode($toReturn['newsboard'][$key]['newsText'],ENT_QUOTES));
			$toReturn['newsboard'][$key]['newsDate'] = $this->panelInit->unix_to_date($toReturn['newsboard'][$key]['newsDate']);
		}

		return $toReturn;
	}

	public function delete($id){

		if(!$this->panelInit->can( "newsboard.delNews" )){
			exit;
		}

		if ( $postDelete = \newsboard::where('id', $id)->first() )
        {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true,$this->panelInit->language['delNews'],$this->panelInit->language['newsDeleted']);
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['delNews'],$this->panelInit->language['newsNotEist']);
        }
	}

	public function create(){
		
		if(!$this->panelInit->can( "newsboard.addNews" )){
			exit;
		}

		$newsboard = new \newsboard();
		$newsboard->newsTitle = \Input::get('newsTitle');
		$newsboard->newsText = htmlspecialchars(\Input::get('newsText'),ENT_QUOTES);
		$newsboard->newsFor = \Input::get('newsFor');
		$newsboard->newsDate = $this->panelInit->date_to_unix(\Input::get('newsDate'));
		$newsboard->fe_active = \Input::get('fe_active');
		$newsboard->creationDate = time();

		if (\Input::hasFile('newsImage')) {
			$fileInstance = \Input::file('newsImage');

			if(!$this->panelInit->validate_upload($fileInstance)){
				return $this->panelInit->apiOutput(false,$this->panelInit->language['addNews'],"Sorry, This File Type Is Not Permitted For Security Reasons ");
			}

			$newFileName = uniqid().".".$fileInstance->getClientOriginalExtension();
			$fileInstance->move('uploads/news/',$newFileName);

			$newsboard->newsImage = $newFileName;
		}

		$newsboard->save();

		//Send Push Notifications
		$tokens_list = array();
		if($newsboard->newsFor == "all"){
			$user_list = \User::select('firebase_token')->get();
		}else{
			$user_list = \User::where('role',$newsboard->newsFor)->select('firebase_token')->get();
		}
		foreach ($user_list as $value) {
			if($value['firebase_token'] != ""){
				$tokens_list[] = $value['firebase_token'];		
			}
		}

		if(count($tokens_list) > 0){
			$newsText = strip_tags(\Input::get('newsText'));
			$this->panelInit->send_push_notification($tokens_list,$newsText,$newsboard->newsTitle,"newsboard",$newsboard->id);			
		}

		$newsboard->newsText = strip_tags(htmlspecialchars_decode($newsboard->newsText));

		return $this->panelInit->apiOutput(true,$this->panelInit->language['addNews'],$this->panelInit->language['newsCreated'],$newsboard->toArray() );
	}

	function fetch($id){

		if(!$this->panelInit->can( array("newsboard.View","newsboard.editNews") )){
			exit;
		}

		$data = \newsboard::where('id',$id)->first()->toArray();
		$data['newsText'] = htmlspecialchars_decode($data['newsText'],ENT_QUOTES);
		$data['newsDate'] = $this->panelInit->unix_to_date($data['newsDate']);
		return json_encode($data);
	}

	function edit($id){

		if(!$this->panelInit->can( "newsboard.editNews" )){
			exit;
		}

		$newsboard = \newsboard::find($id);
		$newsboard->newsTitle = \Input::get('newsTitle');
		$newsboard->newsText = htmlspecialchars(\Input::get('newsText'),ENT_QUOTES);
		$newsboard->newsFor = \Input::get('newsFor');
		$newsboard->newsDate = $this->panelInit->date_to_unix(\Input::get('newsDate'));
		$newsboard->fe_active = \Input::get('fe_active');

		if (\Input::hasFile('newsImage')) {
			$fileInstance = \Input::file('newsImage');

			if(!$this->panelInit->validate_upload($fileInstance)){
				return $this->panelInit->apiOutput(false,$this->panelInit->language['editNews'],"Sorry, This File Type Is Not Permitted For Security Reasons ");
			}
			
			$newFileName = uniqid().".".$fileInstance->getClientOriginalExtension();
			$fileInstance->move('uploads/news/',$newFileName);

			$newsboard->newsImage = $newFileName;
		}
		
		$newsboard->save();

		$newsboard->newsText = strip_tags(htmlspecialchars_decode($newsboard->newsText));

		return $this->panelInit->apiOutput(true,$this->panelInit->language['editNews'],$this->panelInit->language['newsModified'],$newsboard->toArray() );
	}

	function fe_active($id){
		
		if(!$this->panelInit->can( "newsboard.editNews" )){
			exit;
		}
		
		$newsboard = \newsboard::find($id);
		
		if($newsboard->fe_active == 1){
			$newsboard->fe_active = 0;
		}else{
			$newsboard->fe_active = 1;
		}

		$newsboard->save();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['editNews'],$this->panelInit->language['newsModified'], array("fe_active"=>$newsboard->fe_active) );
	}
}
