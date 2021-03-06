<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderOutput;
use App\Models\OrderLocation;
use App\Models\OrderStatus;

class OrderController extends Controller
{
    public function create(Request $request){

         // Validate
         $this->validate($request, [
            'mulai' => 'required|string',
            'akhir' => 'required|string',
            'kegunaan' => 'required|string',
           ]);

        $hasil_array = $request->json('hasil');
        // Generate polygon
        $latlng_array = $request->json('latlng');
        $odd = array();
        $even = array();
        foreach ($latlng_array as $key => $value) {
            if ($key % 2 == 0) {
                $even[] = $value;
            }
            else {
                $odd[] = $value;
            }
        }


      // date("j M, Y", strtotime($b));

        if ($request->json('subject') == '') {
            // Generated subject
            $carbon = Carbon::now();
            $subject =  $carbon->format('d-M-Y H:i A');
        }else{
            $subject = $request->json('subject');
        }
        // Generated orderhours
        $mulai = Carbon::createFromFormat('Y-m-d', $request->json('mulai'));
        $akhir = Carbon::createFromFormat('Y-m-d', $request->json('akhir'));
        $orderhourduration = $mulai->diffInHours($akhir);


        // Store order
        $result_order = Order::create([
            'subject' => $subject,
            'createdby' => $request->json('createdby_id'),
            'dtprojectstart' => $mulai,
            'dtprojectend' => $akhir,
            'orderhourduration' => $orderhourduration,
            'projecttype' => $request->json('kegunaan'),
            'comment' => $request->json('comment')
        ]);

        // Store order_outputs
        foreach($hasil_array as $hasil ){
            $result_order_output = OrderOutput::create([
                'order_id' => $result_order->id,
                'output_id' => $hasil
             ]);
        }
            // Store order_location
         $count = count($even);
            for ($i = 0; $i < $count ; $i++) {
                $result_order_polygon = OrderLocation::create([
                    'order_id' => $result_order->id,
                    'latitude' => $even[$i],
                    'longitude' => $odd[$i]
                ]);
        }

        // Store OrderStatus
        $status_id = '1';
        $result_order_status = OrderStatus::create([
            'order_id' => $result_order->id,
            'status_id' => $status_id,
            'changedby_id' => $request->json('createdby_id')
        ]);
        $client = new \GuzzleHttp\Client();

        $url = 'https://fcm.googleapis.com/fcm/send';
        $headers = [
            'Content-Type' =>'application/json',
            'Authorization' => 'key=AIzaSyBBM08AA_Gt0U0ov0pB0swrvfN9qiDKcqs'

        ];
        $notification = [
            "title" => "Proyek Tawaran",
            "body" => "Ada Tawaran Baru",
            "sound" => "default",
            "click_action" => "FCM_PLUGIN_ACTIVITY",
            "icon" =>"fcm_push_icon"
        ];

        $data = [
            "title" => "Proyek Tawaran",
            "body" => "Ada Tawaran Baru",
            "action" => "tawaran",
            "forceStart" => "1"
        ];
        $params = [
            'notification'=> $notification,
            'data' => $data,
            "to" => "/topics/tawaran",
            "priority" => "high"
        ];

    $response = $client->post('https://fcm.googleapis.com/fcm/send', [
        'headers' => ['Content-Type' => 'application/json',
        'Authorization' => 'key=AIzaSyBBM08AA_Gt0U0ov0pB0swrvfN9qiDKcqs'
    ],
        'body' => json_encode([
            'notification'=> $notification,
            'data' => $data,
            "to" => "/topics/tawaran",
            "priority" => "high"
        ])
    ]);
    $result_subscribe =  $response->getBody();
        if($result_order && $result_order_output && $result_order_polygon){
            return response()->json([
                'success' => true
                ]);
        }

    }

    public function timezone(){
        return Carbon::now();
    }
}
