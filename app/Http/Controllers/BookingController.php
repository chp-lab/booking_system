<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Models\Guest;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $one_email =  'one_email';
    public $meeting_time_start =  'meeting_start';
    public $meeting_time_end =  'meeting_end';
    public $room_num =  'room_num';
    public $agenda =  'agenda';
    public $eject =  'eject_at';
    public $booking_num =  'booking_number';
    public $guest_email =  'guest_email';
    public $main_door =  'main_door';
    
    public $meeting_start_get;
    public $meeting_end_get;

    public function test(){
        $timeNow = Carbon::now();
        $timeNow->tz = new \DateTimeZone('Asia/Bangkok');
        $data[] = [
            "name" => "number of server deployed",
            "value" => 3
        ];
        $data[] = [
            "name" => "time when request",
            "value" => $timeNow->isoFormat('dddd D-MM-YYYY HH:mm:ss')
        ];
        return response()->json(['Status' => 'success',
                                 'Message' => "this is test api",
                                 'Value' => $data
                                ],200);
    }

    public function availableStat($day = null){

        $booking_data_json = [];
        $room_data_json = [];
        $timeNow = Carbon::now();
        $timeNow->tz = new \DateTimeZone('Asia/Bangkok');
        $green = new \DateTime('04:00');
        $red = new \DateTime('22:00');
        
        $roomsTable = Room::where($this->main_door, '=', null)->get();
        
        if(is_null($day)){
            $start = Carbon::create(Carbon::parse($timeNow)->year, Carbon::parse($timeNow)->month, 1, 0, 0, 0, 'Asia/Bangkok');
            $tmr = Carbon::create(Carbon::parse($timeNow)->year, Carbon::parse($timeNow)->month, 2, 0, 0, 0, 'Asia/Bangkok');
            $end = Carbon::create(Carbon::parse($timeNow)->year, Carbon::parse($timeNow)->month + 1, 1, 0, 0, 0, 'Asia/Bangkok');
        }else{
            if(!is_numeric($day)){
                return response()->json(['Status' => 'fail', 'Message' => "day should be number"], 400);
            }    
            $start = Carbon::create(Carbon::parse($timeNow)->year, Carbon::parse($timeNow)->month, $day, 0, 0, 0, 'Asia/Bangkok');
            $tmr = Carbon::create(Carbon::parse($timeNow)->year, Carbon::parse($timeNow)->month, $day + 1, 0, 0, 0, 'Asia/Bangkok');
            $end = Carbon::create(Carbon::parse($timeNow)->year, Carbon::parse($timeNow)->month, $day + 1, 0, 0, 0, 'Asia/Bangkok');
        }
        
        while($start < $end){
            foreach($roomsTable as $room){
                $ref = new \DateTime('00:00');  
                $total_time = new \DateTime('00:00');

                $bookingTable = Booking::where($this->meeting_time_start, '>=', $start)
                                        ->where($this->meeting_time_end, '<=', $tmr)
                                        ->where($this->eject , '=',  null)
                                        ->where($this->room_num , '=', $room->room_num)
                                        ->get();

                // dd($bookingTable);
                foreach($bookingTable as $booking){
                    $meeting_start = Carbon::createFromFormat('Y-m-d H:i:s',  $booking->meeting_start);
                    $meeting_end = Carbon::createFromFormat('Y-m-d H:i:s',  $booking->meeting_end);
                    $interval = $meeting_start->diff($meeting_end);
                    $total_time->add($interval);
                }

                $total_compare_time = new \DateTime($ref->diff($total_time)->format("%H:%I"));
                if($total_compare_time <= $green){
                    $status = 'green';
                }else if(($total_compare_time > $green) && ($total_compare_time < $red)){
                    $status = 'orange';
                }else if($total_compare_time >= $red){
                    $status = 'red';
                }else{
                    $status = 'undefined';
                }
                
                $room_data_json[] = [
                    "room" => $room->room_num,
                    "time" => $ref->diff($total_time)->format("%H:%I"),
                    "status" => $status,
                ];
            }

            $booking_data_json[] = [
                "day" => Carbon::parse($start)->day,
                "booking_sum_time" => $room_data_json,
            ];

            $room_data_json = [];
            $tmr->adddays(1);
            $start->adddays(1);
        }

        return response()->json(['Status' => 'success', 'Message' => "", 'Value' => $booking_data_json], 200);
    }

    public function nowMeetingTable($room_num = null){
        $booking_data_json = [];
        $timeNow = Carbon::now();
        $timeNow->tz = new \DateTimeZone('Asia/Bangkok');

        if(is_null($room_num)){
            $bookingTable = Booking::where($this->meeting_time_start, '<=', $timeNow)
                                    ->where($this->meeting_time_end, '>', $timeNow)
                                    ->Where($this->eject , '=',  null)
                                    ->get();
        }else{
            $rooms = Room::where($this->room_num, '=', $room_num)->first();
            if(is_null($rooms)){
                return response()->json([ 'Status' => 'fail',
                                          'Message' => 'room number is invalid'
                                        ], 404);
            }
            $bookingTable = Booking::join('rooms', 'bookings.room_num', '=', 'rooms.room_num')
                                    ->where('bookings.'.$this->room_num, '=', $room_num)
                                    ->where($this->meeting_time_start, '<=', $timeNow)
                                    ->where($this->meeting_time_end, '>', $timeNow)
                                    ->Where($this->eject , '=',  null)
                                    ->get();
        }
        
        foreach($bookingTable as $booking){
            $guests_arr = array();
            $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
            foreach($guests as $g){
                array_push($guests_arr, $g->guest_email);
            }

            if(Carbon::parse($booking->meeting_start)->day < 10){
                $day = "0" . Carbon::parse($booking->meeting_start)->day;
            }else{
                $day = "" . Carbon::parse($booking->meeting_start)->day;
            }

            $booking_data_json[] = [
                "booking_number" => $booking->booking_number,
                "one_email" => $booking->one_email,
                "guest_email" => $guests_arr,
                "room_num" => $booking->room_num,
                "agenda" => $booking->agenda,
                "meeting_start" => $booking->meeting_start,
                "meeting_start_day" => $day,
                "meeting_end" => $booking->meeting_end,
                // "created_at" => $booking->created_at,
                "eject_at" => $booking->eject_at
            ];
        }

        if(count($booking_data_json) == 0){
            return response()->json([ 'Status' => 'success', 'Message' => 'no meeting right now', 'Value' => $booking_data_json], 200);
        }else{
            return response()->json([ 'Status' => 'success', 'Message' => '', 'Value' => $booking_data_json], 200);
        }
    }

    public function bookingTable($one_email = null, $select = null, $date = null){   
        $booking_data_this_month = [];
        $booking_data_next_month = [];
        $booking_data_json = [];
        $timeNow = Carbon::now();
        $timeNow->tz = new \DateTimeZone('Asia/Bangkok');

        if(is_null($one_email)){
            $this_month_start = Carbon::create($timeNow->year, $timeNow->month, 1, 0, 0, 0, 'Asia/Bangkok');
            $this_month_end = Carbon::create($timeNow->year, $timeNow->month, 1, 0, 0, 0, 'Asia/Bangkok');
            $this_month_end->addMonthsNoOverflow(1);
            $next_month_start = Carbon::create($timeNow->year, $timeNow->month, 1, 0, 0, 0, 'Asia/Bangkok');
            $next_month_start->addMonthsNoOverflow(1);
            $next_month_end = Carbon::create($timeNow->year, $timeNow->month, 1, 0, 0, 0, 'Asia/Bangkok');
            $next_month_end->addMonthsNoOverflow(2);

            //this month
            $bookingTable = Booking::where($this->meeting_time_end, '>', $this_month_start)
                                    ->where($this->meeting_time_end, '<', $this_month_end)
                                    ->where($this->eject , '=',  null)
                                    ->get();
            foreach($bookingTable as $booking){
                $guests_arr = array();
                $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
                foreach($guests as $g){
                    array_push($guests_arr, $g->guest_email);
                }

                if(Carbon::parse($booking->meeting_start)->day < 10){
                    $day = "0" . Carbon::parse($booking->meeting_start)->day;
                }else{
                    $day = "" . Carbon::parse($booking->meeting_start)->day;
                }

                $booking_data_this_month[] = [
                    "booking_number" => $booking->booking_number,
                    "one_email" => $booking->one_email,
                    "guest_email" => $guests_arr,
                    "room_num" => $booking->room_num,
                    "agenda" => $booking->agenda,
                    "meeting_start" => $booking->meeting_start,
                    "meeting_start_day" => $day,
                    "meeting_end" => $booking->meeting_end,
                    // "created_at" => $booking->created_at,
                    "eject_at" => $booking->eject_at
                ];
            }

            //next month
            $bookingTable = Booking::where($this->meeting_time_end, '>', $next_month_start)
                                    ->where($this->meeting_time_end, '<', $next_month_end)
                                    ->where($this->eject , '=',  null)
                                    ->get();
            foreach($bookingTable as $booking){
                $guests_arr = array();
                $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
                foreach($guests as $g){
                    array_push($guests_arr, $g->guest_email);
                }
                $booking_data_next_month[] = [
                    "booking_number" => $booking->booking_number,
                    "one_email" => $booking->one_email,
                    "guest_email" => $guests_arr,
                    "room_num" => $booking->room_num,
                    "agenda" => $booking->agenda,
                    "meeting_start" => $booking->meeting_start,
                    "meeting_start_day" => Carbon::parse($booking->meeting_start)->day,
                    "meeting_end" => $booking->meeting_end,
                    // "created_at" => $booking->created_at,
                    "eject_at" => $booking->eject_at
                ];
            }

            $value = [
                'this_month' => $booking_data_this_month,
                'next_month' => $booking_data_next_month
            ];

            return response()->json(['Status' => 'success', 'Message' => '', "Value" => $value], 200);
        }else{
            if($select == 'history'){
                //***** must test *****
                $bookingTable_notEject = Booking::where($this->one_email , '=', $one_email)
                                        ->where($this->meeting_time_end, '<', $timeNow)
                                        ->Where($this->eject , '=',  null)
                                        ->get();
                
                $bookingTable_Eject = Booking::where($this->one_email , '=', $one_email)
                                        ->Where($this->eject , '!=',  null)
                                        ->get();

                foreach($bookingTable_notEject as $booking){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }

                    if(Carbon::parse($booking->meeting_start)->day < 10){
                        $day = "0" . Carbon::parse($booking->meeting_start)->day;
                    }else{
                        $day = "" . Carbon::parse($booking->meeting_start)->day;
                    }
    
                    $booking_data_json[] = [
                        "booking_number" => $booking->booking_number,
                        "one_email" => $booking->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $booking->room_num,
                        "agenda" => $booking->agenda,
                        "meeting_start" => $booking->meeting_start,
                        "meeting_start_day" => $day,
                        "meeting_end" => $booking->meeting_end,
                        // "created_at" => $booking->created_at,
                        "eject_at" => $booking->eject_at,
                        "guest" => false
                    ];
                }

                foreach($bookingTable_Eject as $booking){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }
                    $booking_data_json[] = [
                        "booking_number" => $booking->booking_number,
                        "one_email" => $booking->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $booking->room_num,
                        "agenda" => $booking->agenda,
                        "meeting_start" => $booking->meeting_start,
                        "meeting_start_day" => Carbon::parse($booking->meeting_start)->day,
                        "meeting_end" => $booking->meeting_end,
                        // "created_at" => $booking->created_at,
                        "eject_at" => $booking->eject_at,
                        "guest" => false
                    ];
                }

                $guestTable_notEject = Booking::join('guests', 'bookings.booking_number', '=', 'guests.booking_number')
                        ->where($this->guest_email , '=', $one_email)
                        ->where($this->meeting_time_end, '<', $timeNow)
                        ->where($this->eject , '=',  null)
                        ->get();

                $guestTable_Eject = Booking::join('guests', 'bookings.booking_number', '=', 'guests.booking_number')
                        ->where($this->guest_email , '=', $one_email)
                        ->where($this->eject , '!=',  null)
                        ->get();
            
                foreach($guestTable_notEject as $guest){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $guest->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }
                    $booking_data_json[] = [
                        "booking_number" => $guest->booking_number,
                        "one_email" => $guest->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $guest->room_num,
                        "agenda" => $guest->agenda,
                        "meeting_start" => $guest->meeting_start,
                        "meeting_start_day" => Carbon::parse($guest->meeting_start)->day,
                        "meeting_end" => $guest->meeting_end,
                        // "created_at" => $guest->created_at,
                        "eject_at" => $guest->eject_at,
                        "guest" => true
                    ];
                }

                foreach($guestTable_Eject as $guest){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $guest->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }
                    $booking_data_json[] = [
                        "booking_number" => $guest->booking_number,
                        "one_email" => $guest->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $guest->room_num,
                        "agenda" => $guest->agenda,
                        "meeting_start" => $guest->meeting_start,
                        "meeting_start_day" => Carbon::parse($guest->meeting_start)->day,
                        "meeting_end" => $guest->meeting_end,
                        // "created_at" => $guest->created_at,
                        "eject_at" => $guest->eject_at,
                        "guest" => true
                    ];
                }
                
                //sort
                $booking_data_sort_arr = array();
                $booking_data_sort = collect($booking_data_json)->sortBy("booking_number");
                foreach($booking_data_sort as $booking_data){
                    array_push($booking_data_sort_arr, $booking_data);
                }
                return response()->json(['Status' => 'success', 'Message' => '', "Value" => $booking_data_sort_arr], 200);
            }else if($select == 'future'){
                $bookingTable = Booking::where($this->one_email , '=', $one_email)
                                    ->where($this->meeting_time_end, '>', $timeNow)
                                    ->where($this->eject , '=',  null)
                                    ->get();
                foreach($bookingTable as $booking){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }

                    if(Carbon::parse($booking->meeting_start)->day < 10){
                        $day = "0" . Carbon::parse($booking->meeting_start)->day;
                    }else{
                        $day = "" . Carbon::parse($booking->meeting_start)->day;
                    }
    
                    $booking_data_json[] = [
                        "booking_number" => $booking->booking_number,
                        "one_email" => $booking->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $booking->room_num,
                        "agenda" => $booking->agenda,
                        "meeting_start" => $booking->meeting_start,
                        "meeting_start_day" => $day,
                        "meeting_end" => $booking->meeting_end,
                        // "created_at" => $booking->created_at,
                        "eject_at" => $booking->eject_at,
                        "guest" => false
                    ];
                }

                $guestTable = Booking::join('guests', 'bookings.booking_number', '=', 'guests.booking_number')
                        ->where($this->guest_email , '=', $one_email)
                        ->where($this->meeting_time_end, '>', $timeNow)
                        ->where($this->eject , '=',  null)
                        ->get();

                foreach($guestTable as $guest){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $guest->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }
                    $booking_data_json[] = [
                        "booking_number" => $guest->booking_number,
                        "one_email" => $guest->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $guest->room_num,
                        "agenda" => $guest->agenda,
                        "meeting_start" => $guest->meeting_start,
                        "meeting_start_day" => Carbon::parse($guest->meeting_start)->day,
                        "meeting_end" => $guest->meeting_end,
                        // "created_at" => $guest->created_at,
                        "eject_at" => $guest->eject_at,
                        "guest" => true
                    ];
                }

                //sort
                $booking_data_sort_arr = array();
                $booking_data_sort = collect($booking_data_json)->sortBy("booking_number");
                foreach($booking_data_sort as $booking_data){
                    array_push($booking_data_sort_arr, $booking_data);
                }

                return response()->json(['Status' => 'success', 'Message' => '', "Value" => $booking_data_sort_arr], 200);
                // return response()->json($booking_data_json);
            }else if($select == 'time'){
                $start_date = $date . " 00:00:00";
                $end_date = $date . " 23:59:59";
                $bookingTable = Booking::where($this->one_email , '=', $one_email)
                    ->where($this->meeting_time_start, '>=', $start_date)
                    ->where($this->meeting_time_start, '<=', $end_date)
                    ->where($this->eject , '=',  null)
                    ->get();

                foreach($bookingTable as $booking){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', $booking->booking_number)->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }

                    if(Carbon::parse($booking->meeting_start)->day < 10){
                        $day = "0" . Carbon::parse($booking->meeting_start)->day;
                    }else{
                        $day = "" . Carbon::parse($booking->meeting_start)->day;
                    }
    
                    $booking_data_json[] = [
                        "booking_number" => $booking->booking_number,
                        "one_email" => $booking->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $booking->room_num,
                        "agenda" => $booking->agenda,
                        "meeting_start" => $booking->meeting_start,
                        "meeting_start_day" => $day,
                        "meeting_end" => $booking->meeting_end,
                        // "created_at" => $booking->created_at,
                        "eject_at" => $booking->eject_at,
                        "guest" => false
                    ];
                }
                
                $guestTable = Booking::join('guests', 'bookings.booking_number', '=', 'guests.booking_number')
                        ->where($this->guest_email , '=', $one_email)
                        ->where($this->meeting_time_start, '>=', $start_date)
                        ->where($this->meeting_time_start, '<=', $end_date)
                        ->where($this->eject , '=',  null)
                        ->get();

                foreach($guestTable as $guest){
                    $guests_arr = array();
                    $guests = Guest::where($this->booking_num , '=', "$guest->booking_number")->get();
                    foreach($guests as $g){
                        array_push($guests_arr, $g->guest_email);
                    }
                    $booking_data_json[] = [
                        "booking_number" => $guest->booking_number,
                        "one_email" => $guest->one_email,
                        "guest_email" => $guests_arr,
                        "room_num" => $guest->room_num,
                        "agenda" => $guest->agenda,
                        "meeting_start" => $guest->meeting_start,
                        "meeting_start_day" => Carbon::parse($guest->meeting_start)->day,
                        "meeting_end" => $guest->meeting_end,
                        // "created_at" => $guest->created_at,
                        "eject_at" => $guest->eject_at,
                        "guest" => true
                    ];
                }

                //sort
                $booking_data_sort_arr = array();
                $booking_data_sort = collect($booking_data_json)->sortBy("booking_number");
                foreach($booking_data_sort as $booking_data){
                    array_push($booking_data_sort_arr, $booking_data);
                }
                
                return response()->json(['Status' => 'success', 'Message' => '', "Value" => $booking_data_sort_arr], 200);
            }else{
                return response()->json(['Status' => 'fail', 'Message' => 'page not found'], 404);
            }
        }    
    }

    public function userTable($select = null, $one_email = null){
        
        if($select == 'all'){
            if(is_null($one_email)){
                $userTable = User::all();
                return response()->json(['Status' => 'success','Message' => '' ,'Value' => $userTable], 200);
            }else{
                $userTable = User::where($this->one_email , '=', $one_email)->first();
                return response()->json(['Status' => 'success', 'Message' => '' , 'Value' => $userTable], 200);
            }
        }else if($select == 'email'){
            $user_email_arr = array();
            if(is_null($one_email)){
                $userTable = User::all();
                foreach($userTable as $user){
                    array_push($user_email_arr, $user->one_email);
                }
                return response()->json(['Status' => 'success', 'Message' => '', 'Value' => $user_email_arr], 200);
            }else{
                return response()->json(['Status' => 'fail', 'Message' => 'page not found'], 404);
            }
        }else{
            return response()->json(['Status' => 'fail', 'Message' => 'page not found'], 404);
        }
        
    }

    public function roomTable(){
        $roon_table = Room::all();
        return response()->json(['Status' => 'Query Successful', 'Value' => $roon_table], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function checkAvailableRoom(Request $request){
        $this->meeting_start_get = $request->get($this->meeting_time_start);
        $this->meeting_end_get = $request->get($this->meeting_time_end);
        $rooms_booked_arr = array();
        $rooms_not_booked_arr = array();
        $timeNow = Carbon::now();
        $timeNow->tz = new \DateTimeZone('Asia/Bangkok');

        $meeting_start_date = Carbon::parse($request->get($this->meeting_time_start));
        $meeting_end_date = Carbon::parse($request->get($this->meeting_time_end));

        if( is_null($request->get($this->meeting_time_start)) ||
            is_null($request->get($this->meeting_time_end))
        ){
            return response()->json(['Status' => 'fail', 'Message' => 'some value might be null'], 400);
        }

        if(($request->get($this->meeting_time_start) < $timeNow) || 
           ($meeting_end_date->day != $meeting_start_date->day)){
            return response()->json(['Status' => 'fail', 'Message' => 'datetime is invalid'], 400);
        }

        if($request->get($this->meeting_time_start) >= $request->get($this->meeting_time_end)){
            return response()->json(['Status' => 'fail', 'Message' => 'start time sould be less then end time'], 400);
        }
        
        // $booking = new Booking();

        $rooms = Room::where($this->main_door , '=', null)->get();

        $booked_list = Booking::where($this->eject , '=',  null)
                                ->where($this->meeting_time_end , '>', $timeNow)
                                ->where($this->meeting_time_end , '>',  $this->meeting_start_get)
                                ->where($this->meeting_time_start , '<',  $this->meeting_end_get)
                                ->get();
                            
        foreach($booked_list as $booked){
            array_push($rooms_booked_arr, $booked->room_num);
            $rooms_booked_arr = array_unique($rooms_booked_arr);
        }

        $found = false;
        foreach($rooms as $room){
            foreach($rooms_booked_arr as $room_booked){
                if($room->room_num == $room_booked){
                    $found = true;
                }
            }
            //if not found room in rooms_booked_arr
            if(!$found){
                array_push($rooms_not_booked_arr, $room->room_num);
            }
            $found = false;
        }

        // foreach($rooms_booked_arr as $room_booked_arr){
        //     //if query found somthing then room not available
        //     $checkBookingTime = Booking::where($this->room_num , $room_booked_arr)
        //                                 ->where($this->meeting_time_end , '>', $timeNow)
        //                                 ->where($this->meeting_time_end , '>',  $this->meeting_start_get)
        //                                 ->where($this->meeting_time_start , '<',  $this->meeting_end_get)
        //                                 ->where($this->eject , '=',  null)
        //                                 // ->orderBy($this->room_num , 'DESC')
        //                                 ->get();
        //     if(count($checkBookingTime) == 0){
        //         array_push($rooms_not_booked_arr, $room_booked_arr);
        //     }                           
        // }

        if(count($rooms_not_booked_arr) == 0){
            $rooms_not_booked_jason = [
                'Status' => 'success',
                'Message' => 'no room available',
                'Value' => $rooms_not_booked_arr
            ];
            return response()->json($rooms_not_booked_jason, 200);
        }else{
            $rooms_not_booked_jason = [
                'Status' => 'success',
                'Message' => 'these rooms are available',
                'Value' => $rooms_not_booked_arr
            ];
            return response()->json($rooms_not_booked_jason, 200);
        }    
    }

    public function booking(Request $request){

        if( is_null($request->get($this->one_email)) || 
            is_null($request->get($this->agenda)) ||
            is_null($request->get($this->room_num)) ||
            is_null($request->get($this->meeting_time_start)) ||
            is_null($request->get($this->meeting_time_end))
            //  || is_null($request->get("guests"))
        ){
            return response()->json(['Status' => 'fail', 'Message' => 'some value might be null'], 400);
        }

        $userTable = User::where($this->one_email , '=', $request->get($this->one_email))->first();
        if($userTable == null){
            return response()->json(['Status' => 'fail', 'Message' => 'booking email is invalid'], 400);
        }

        if($this->checkAvailableRoom($request)->getStatusCode() == 200) {
            $rooms_available_arr = $this->checkAvailableRoom($request)->getData()->Value;
            $user_room_select_stat = false;
        }else{
            return response()->json(['Status' => 'fail', 'Message' => 'room not available'], 400);
        }

        //if guest not null
        if(!is_null($request->get("guests"))){
            $guests = $request->get("guests");
            foreach($guests as $g){
                if($g == $request->get($this->one_email)){
                    return response()->json(['Status' => 'fail', 'Message' => 'booking email cannot be guest'], 400);
                }
            }
        }else{
            $guests = [];
        }

        foreach($rooms_available_arr as $room_available){
            if($room_available == $request->get($this->room_num)){
                $user_room_select_stat = true;
            }
        }

        if($user_room_select_stat){
            $booking = new Booking();
            $booking->one_email = $request->get($this->one_email);
            $booking->room_num = $request->get($this->room_num);
            $booking->meeting_start = $request->get($this->meeting_time_start);
            $booking->meeting_end = $request->get($this->meeting_time_end);
            $booking->agenda = $request->get($this->agenda);
            $booking->save();

            $bookingTable = Booking::where($this->one_email , '=', $request->get($this->one_email))
                                    ->where($this->room_num , '=', $request->get($this->room_num))
                                    ->where($this->meeting_time_start , '=', $request->get($this->meeting_time_start))
                                    ->where($this->meeting_time_end , '=', $request->get($this->meeting_time_end))
                                    ->where($this->eject , '=',  null)
                                    ->first();

            //if guest not null
            if(!is_null($request->get("guests"))){
                foreach($guests as $g){
                    $guest = new Guest();
                    $guest->booking_number = $bookingTable->booking_number;
                    $guest->guest_email = $g;
                    $guest->save();
                }
            }
            
            return response()->json(['Status' => 'success', 'Message' => '', 'Booking Info' => $bookingTable, 'Guests' => $guests], 201);
        }else{
            return response()->json(['Status' => 'fail', 'Message' => 'room not available'], 400);
        }
    }

    public function ejectBooking($one_email = null, $booking_number = null){
        
        if(is_null($booking_number) || is_null($one_email)){
            return response()->json(['Status' => 'fail', 'Message' => 'some value might be null'], 400);
        }else{
            $bookingTable = Booking::where($this->booking_num , '=', $booking_number)
                                    ->where($this->one_email , '=', $one_email)    
                                    ->first();

            if(is_null($bookingTable)){
                return response()->json(['Status' => 'fail', 'Message' => 'booking number is invalid'], 400);
            }

            if(!is_null($bookingTable->eject_at)){
                return response()->json(['Status' => 'fail','Message' => 'this booking number already eject', 'Eject at' => $bookingTable->eject_at], 400);
            }

            $timeNow = Carbon::now();
            $timeNow->tz = new \DateTimeZone('Asia/Bangkok');
            // $timeNow = Carbon::createFromFormat('Y-m-d H:i:s', $timeNow, 'Asia/Bangkok');

            $bookingTable = Booking::where($this->booking_num , '=', $booking_number)
                                    ->update([$this->eject => $timeNow]);

            $bookingTable = Booking::where($this->booking_num , '=', $booking_number)->first();
            return response()->json(['Status' => 'success', 'Message' => '', 'Eject at' =>  $bookingTable->eject_at], 200);
        }

    }

    public function getProfile($user_token = null){
        // $client = new \GuzzleHttp\Client();
        // $request = $client->get('http://192.168.72.24:8001/api/v1/bookingTable/nitchakarn.ho@one.th/time/2021-02-10');
        // $response = $request->getBody();
        // dd($response->getContents());
        
        $bot_token = 'Bearer Af58c5450f3b45c71a97bc51c05373ecefabc49bd2cd94f3c88d5b844813e69a17e26a828c2b64ef889ef0c10e2aee347';
        $bot_id = 'B75900943c6205ce084d1c5e8850d40f9';
        $client = new \GuzzleHttp\Client();

        try{
            $request = $client->post('https://chat-api.one.th/manage/api/v1/getprofile',[
                'headers' => [
                    'Authorization' => $bot_token,
                    'Content-Type' => 'application/json',
                ],
    
                'body' => json_encode([
                    'bot_id'=> $bot_id,
                    'source'=> $user_token,
                ])
            ]);
            $response = json_decode($request->getBody()->getContents());
        }catch (\Exception $ex) {
            // return response()->json(['Status' => $ex->getResponse()->getReasonPhrase()], $ex->getResponse()->getStatusCode());
            if(($ex->getResponse()->getStatusCode() == 401)){
                $m = 'token invalid';
            }else if(($ex->getResponse()->getStatusCode() == 400)){
                $m = 'bad request';
            }else if(($ex->getResponse()->getStatusCode() == 500)){
                $m = 'server error';
            }else{
                $m = '';
            }
            return response()->json([ 'Status' => 'fail',
                                      'Message' => $m
                                    ], $ex->getResponse()->getStatusCode());
        }
        if($response->status == 'success'){
            return response()->json(['Status' => 'success', 'Message' => '', 'Value' =>  $response->data], 200);
        }else{
            return response()->json(['Status' => 'fail', 'Message' => 'token invalid'], 401);
        }
    }

    public function unlock(Request $request, $user_token = null){
        $server = 'http://18.140.173.239:5003';
        $client = new \GuzzleHttp\Client();

        if(is_null($request->get($this->room_num)) || is_null($request->get($this->one_email))){
            return response()->json([ 'Status' => 'fail',
                                      'Message' => 'some value might be null'
                                    ], 400);
        }
        
        $rooms = Room::where($this->room_num, '=', $request->get($this->room_num))->first();
        if(is_null($rooms)){
            return response()->json([ 'Status' => 'fail',
                                      'Message' => 'room number is invalid'
                                    ], 404);
        }

        //check main door
        if($rooms->main_door == 1){
            $guest_req = 'none';
            try {
                $request = $client->post($server. '/api/v1/unlock/'. $request->get($this->room_num),[
                    'headers' => [
                        'Authorization' => $user_token,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'guest_req'=> $guest_req,
                    ])
                ]);
                $response = json_decode($request->getBody()->getContents());
            } catch (\Exception $ex) {
                if(($ex->getResponse()->getStatusCode() == 401)){
                    $m = 'token invalid';
                }else if(($ex->getResponse()->getStatusCode() == 400)){
                    $m = 'bad request';
                }else if(($ex->getResponse()->getStatusCode() == 500)){
                    $m = 'server error';
                }else{
                    $m = '';
                }
                return response()->json([ 'Status' => 'fail',
                                          'Message' => $m
                                        ], $ex->getResponse()->getStatusCode());
            }

            if($response->result[0]->door_action == 'open'){
                return response()->json([ 'Status' => 'success', 
                                          'Message' => 'main door accessed',
                                          'Value' => $response
                                        ], 200);
            }else{
                return response()->json([ 'Status' => 'success', 
                                          'Message' => 'cannot access main door',
                                          'Value' => $response
                                        ], 200);
            } 
        }

        $timeNow = Carbon::now();
        $timeNow->tz = new \DateTimeZone('Asia/Bangkok');

        $booking_table = Booking::join('rooms', 'bookings.room_num', '=', 'rooms.room_num')
                                ->where($this->one_email , '=', $request->get($this->one_email))
                                ->where('rooms.'.$this->room_num , '=', $request->get($this->room_num))
                                ->Where($this->eject , '=',  null)
                                ->where($this->meeting_time_start , '<=', $timeNow)
                                ->where($this->meeting_time_end , '>', $timeNow)
                                ->get();

        $guest_table = Guest::join('bookings', 'bookings.booking_number', '=', 'guests.booking_number')
                                ->join('rooms', 'bookings.room_num', '=', 'rooms.room_num')
                                ->where($this->guest_email , '=', $request->get($this->one_email))
                                ->where('rooms.'.$this->room_num , '=', $request->get($this->room_num))
                                ->Where($this->eject , '=',  null)
                                ->where($this->meeting_time_start , '<=', $timeNow)
                                ->where($this->meeting_time_end , '>', $timeNow)
                                ->get();


        if(count($booking_table) != 0 && count($guest_table) == 0){
            $guest_req = 'no';
        }else if(count($booking_table) == 0 && count($guest_table) != 0){
            $guest_req = 'yes';
        }
        
        if(count($booking_table) == 0 && count($guest_table) == 0){
            return response()->json([ 'Status' => 'success', 
                                      'Message' => 'cannot access meeting room',
                                    ], 200);
        }else{
            try {
                $request = $client->post($server. '/api/v1/unlock/'. $request->get($this->room_num),[
                    'headers' => [
                        'Authorization' => $user_token,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'guest_req'=> $guest_req,
                    ])
                ]);
                $response = json_decode($request->getBody()->getContents());
            } catch (\Exception $ex) {
                if(($ex->getResponse()->getStatusCode() == 401)){
                    $m = 'token invalid';
                }else if(($ex->getResponse()->getStatusCode() == 400)){
                    $m = 'bad request';
                }else if(($ex->getResponse()->getStatusCode() == 500)){
                    $m = 'server error';
                }else if(($ex->getResponse()->getStatusCode() == 404)){
                    $m = 'not found';
                }else{
                    $m = '';
                }
                return response()->json([ 'Status' => 'fail',
                                          'Message' => $m
                                        ], $ex->getResponse()->getStatusCode());
            }
            return response()->json([ 'Status' => 'success', 
                                          'Message' => 'meeting room accessed',
                                          'Value' => $response
                                        ], 200);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
