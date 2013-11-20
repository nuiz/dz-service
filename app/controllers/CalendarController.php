<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 5/11/2556
 * Time: 10:35 น.
 * To change this template use File | Settings | File Templates.
 */

class CalendarController extends BaseController {
    public function index(){
        try {
            $user = Auth::getUser();
            $data = array();

            $validator = Validator::make(Input::all(), array(
                "start_date"=> 'required',
                "end_date"=> 'required'
            ));
            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }

            $activities = Activity::where(DB::raw("DATE(start_time)"), ">=", $_GET['start_date'])
                ->where(DB::raw("DATE(start_time)"), "<=", $_GET['end_date'])
                ->orderBy('start_time', 'asc')
                ->get();
            $studies = GroupStudy::where(DB::raw("DATE(start)"), ">=", $_GET['start_date'])
                ->where(DB::raw("DATE(start)"), "<=", $_GET['end_date'])
                ->orderBy('start', 'asc')
                ->get();

            $date = new DateTime($_GET['start_date']);
            $end = new DateTime($_GET['end_date']);

            $listDay = array();
            $fnFilterDay = function($date) use($activities, $studies, $user){
                $day = array(
	   'date'=> $date,
                    'activities' => array(),
                    'studies'=> array()
                );

                /* activity section */
                $buffer = $activities->filter(function($item) use($date){
                    $dateTime_activity = new DateTime($item->start_time);
                    if($dateTime_activity->format("Y-m-d")==$date)
                        return true;
                });
                $activities = array();
                if($buffer->count() > 0){
                    $activities['has_data'] = "yes";
                    $activities['length'] = $buffer->count();
                    $activities['data'] = $buffer->toArray();
                }
                else {
                    $activities['has_data'] = "no";
                }
                $day['activities'] = $activities;

                /* study section */
                $buffer = $studies->filter(function($item) use($date){
                    $dateTime_activity = new DateTime($item->start);
                    if($dateTime_activity->format("Y-m-d")==$date)
                        return true;
                });
                $studies = array();
                if($buffer->count() > 0){
                    $studies['has_data'] = "yes";
                    $studies['length'] = $buffer->count();
                    $studies['data'] = $buffer->toArray();
                }
                else {
                    $studies['has_data'] = "no";
                }
                $day['activities'] = $activities;
                $day['studies'] = $studies;

                return $day;
            };
            for($i=0; $date->getTimestamp() <= $end->getTimestamp(); $i++) {
                $listDay[] = $fnFilterDay($date->format('Y-m-d'));
                $date->add(new DateInterval("P1D"));
            }
            $data = $listDay;

            return Response::json(array(
                'length'=> count($data),
                'data'=> $data
            ));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}