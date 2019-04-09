<?php
namespace App\Http\Controllers;

class biometricController extends Controller {

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
		if($this->data['users']->role != "admin") exit;
	}

	public function get_devices()
	{
		$devices = array();
		if($this->panelInit->settingsArray['biometric_device_ip'] != ""){
			$devices[] = $this->panelInit->settingsArray['biometric_device_ip'];
		}

		return $devices;
	}

	public function sync_devices(){

		$devices = json_decode(\Input::get('devices'),true);
		$attendance = json_decode(\Input::get('attendance'),true);

		if(is_array($devices)){

			$settings = \settings::where('fieldName','biometric_device_status')->first();
			$settings->fieldValue = json_encode($devices);
			$settings->save();
			
		}

		//Get attendance array
		$user_bio_ids = array();
		$user_ids = array();
		$user_att = array();
		$user_list = array();
		if(is_array($attendance)){
			foreach ($attendance as $key => $value) {
				$user_bio_ids[] = $value['userId'];

				//2018-6-30 22:33:30
				$splitted_date = explode(" ", $value['date']);
				$value['date'] = $splitted_date[0];
				$d = \DateTime::createFromFormat('Y-m-d', $value['date'] );
				$d->setTime(0,0,0);

				$value['timestamp'] = $d->getTimestamp();
				$user_att[ $value['userId'] ] = $value;
			}
		}

		//Get users list
		$users_list = \User::whereIn('biometric_id',$user_bio_ids)->select('id','fullName','role','biometric_id','studentClass','firebase_token')->get()->toArray();
		foreach ($users_list as $key => $value) {
			$user_list[ $value['biometric_id'] ] = $value;
		}

		//Filter attendance
		$send_notifications = array();
		foreach ($user_att as $key => $value) {
			if(!isset($user_list[ $value['userId'] ])){
				continue;
			}
			if($user_list[ $value['userId'] ]['role'] == "student" && $this->panelInit->settingsArray['attendanceModel'] == "subject"){
				continue;
			}

			$has_past_att = \attendance::where('date', $value['timestamp'] )->where('studentId', $user_list[ $value['userId'] ]['id'] );
			if($has_past_att->count() > 0){
				$has_past_att = $has_past_att->first();
				$has_past_att->status = 1;
				$has_past_att->save();
			}else{
				$attendanceN = new \attendance();
				if( $user_list[ $value['userId'] ]['studentClass'] != "" AND $user_list[ $value['userId'] ]['studentClass'] != 0){
					$attendanceN->classId = $user_list[ $value['userId'] ]['studentClass'];
				}else{
					$attendanceN->classId = 0;
				}
				$attendanceN->date = $value['timestamp'];
				$attendanceN->studentId = $user_list[ $value['userId'] ]['id'];
				$attendanceN->status = 1;
				$attendanceN->subjectId = 0;
				$attendanceN->save();
			}

			$send_notifications[] = array( "role" => $user_list[ $value['userId'] ]['role'],"id"=> $user_list[ $value['userId'] ]['id'],"fullName"=> $user_list[ $value['userId'] ]['fullName'],"firebase_token"=>$user_list[ $value['userId'] ]['firebase_token'],"date"=>$value['date'] );
		}

		//Send Push Notifications
		$tokens_list = array();
		
		foreach ($send_notifications as $value) {

			
			if( $value['role'] == "teacher" AND $value['firebase_token'] != "" ){
				$this->panelInit->send_push_notification($value['firebase_token'],"Your attendance is : ".$this->panelInit->language['Present']." - Date :".$value['date'],$this->panelInit->language['staffAttendance']);
			}

			if( $value['role'] == "student" ){
				$parents = \User::where('parentOf','like','%"'.$value['id'].'"%')->orWhere('parentOf','like','%:'.$value['id'].'}%')->select('id','firebase_token')->get()->toArray();
				foreach ($parents as $key => $parent) {
					if($parent['firebase_token'] != ""){
						$this->panelInit->send_push_notification($parent['firebase_token'],"Attendance for student : " . $value['fullName'] . " is " . $this->panelInit->language['Present'] . " - Date : " . $value['date'],$this->panelInit->language['Attendance'],"attendance");						
					}			
				}

			}

		}
			

		echo "Updated Attendance for : ".count($send_notifications);

	}

}
