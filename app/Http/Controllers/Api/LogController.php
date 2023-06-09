<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ErrorLog;

class LogController extends Controller
{   
    public function error(Request $request)
    {
		$data = json_decode($request->getContent(), true);
		if(!empty($data)){
			$logRecord = new ErrorLog();
			$logRecord->source = "APP";
			$logRecord->breakpoint = $data['breakpoint'];
			$logRecord->agent = $data['email'];
			$logRecord->name = $data['name'];
			$logRecord->message = $data['message'];
			if($logRecord->save()){
				return response()->json([
                    'status' => 'ok',
                    'message' => 'Record saved'
                ], 200);
			}
			return response()->json([
                'status' => 'error',
                'message' => 'Record was not saved'
           	], 200);
		}
		return response()->json([
            'status' => 'error',
            'message' => 'Data can not be empty'
        ], 200);
    }
}