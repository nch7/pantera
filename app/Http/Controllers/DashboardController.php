<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessMessage;

use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller {
        
    public function index(Request $request) {
        return view('dashboard');
    }

    public function newMessage(Request $request) {

        if($request->get('message') == "" OR !$request->has('message')) {
            return response("Error", 500);
        }

        $job = new ProcessMessage($request->user()->id, $request->get('message'));

        dispatch($job);

        return [
            "status" => "Ok"
        ];
    }

    public function getMessage(Request $request) {
        $id = $request->user()->id;
        return Redis::lpop('new:message:'.$id);
    }
}
