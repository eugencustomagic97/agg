<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ReturnType;
use App\Models\Returns;
use App\Models\ClientBalance;
use App\Models\ClientHistory;
use App\Models\Synchronization;

class ReturnController extends Controller
{   
    public function syncTypes()
    {
		$needSync = false;
		
        $apiUrl = \Config::get('api.api_base_url');
        $apiUser = \Config::get('api.api_user');
        $apiPassword = \Config::get('api.api_password');

        $auth = base64_encode($apiUser . ":" . $apiPassword);
        $context = stream_context_create([
            "http" => [
                "header" => "Authorization: Basic " . $auth
            ]
        ]);

        $agentCode = auth()->user()->code_1c ?? 5698; // Webmaster do not have code 1c, so use some other code, while testing
        //$result =  preg_replace('/[[:^print:]]/', '', file_get_contents($apiUrl . "api/GetReturnType/".$agentCode, false, $context));
		$result =  file_get_contents($apiUrl . "api/GetReturnType/".$agentCode, false, $context);
		 $hash = md5($result);
        $prevSync = Synchronization::where('entity', 'ReturnTypes')->first();
		
		if(empty($prevSync)){
            $prevSync = new Synchronization();
            $prevSync->entity = "ReturnTypes";
            $prevSync->hash = md5($result);
            $prevSync->save();
            $needSync = true;
        } else {
            if($prevSync->hash === $hash){
                $needSync = false;
            } else {
                $needSync = true;
                $prevSync->hash = $hash;
                $prevSync->save();
            }
        }
		
		if($needSync){
			$data = json_decode($result, true);

			if(isset($data['Data']) && !empty($data['Data'])){
				foreach($data['Data'] as $returnType){
					$model = ReturnType::where('guid', $returnType['Id'])->first();
					if(empty($model)){
						$model = new ReturnType();
					}

					$model->guid = $returnType['Id'];
					$model->name = $returnType['Description'];

					$model->save();
				}
			}
		}
    }

    public function listTypes()
    {
        $returnTypes = ReturnType::get()->makeHidden(['created_at', 'updated_at']);

        if(empty($returnTypes)){
            return response()->json([
                'status' => 'error',
                'message' => 'Return types list is empty'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'types' => $returnTypes
        ], 200);
    }

    public function list()
    {
        $returns = Returns::where('agent_id', auth()->user()->id)->get()->makeHidden(['updated_at']);

        if(empty($returns)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no returns made by the agent'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'orders' => $returns
        ], 200);
    }

    public function new(Request $request)
    {
        $returnTypes = ReturnType::select('guid','name')->get()->pluck('name', 'guid')->toArray();
        $data = json_decode($request->getContent(), true);
        if(!empty($data)){
            if(!isset($data['client_id']) || !isset($data['store_id']) || !isset($data['list'])){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ], 400);
            }

            $return = new Returns();
            $return->guid = $this->generateReturnGuid();
            $return->agent_id = auth()->user()->id;
            $return->agent_guid = auth()->user()->guid;
            $return->client_id = $data['client_id'];
            $return->client_guid = $data['client_guid'];
            $return->store_id = $data['store_id'];
            $return->store_guid = $data['store_guid'];
            $return->amount = $data['amount'];
            $return->list = json_encode($data['list']);
            $return->comment = $data['comment'] ?? null;
            $return->doc_type = $data['doc_type'];
            $return->exported = $data['exported'];
            $return->sync_status = 0;

            if($return->save()){
                $apiUrl = \Config::get('api.api_base_url');
                $apiUser = \Config::get('api.api_user');
                $apiPassword = \Config::get('api.api_password');

                $nomenclature = [];
                foreach($data['list'] as $item){
                    $nomenclature[] = [
                        "IdReturn" => $item['return_type'],
                        "ReturnDescription" => $returnTypes[$item['return_type']],
                        "Price" => $item['price'],
                        "Quantity" => $item['quantity'],
                        "SkuDescription" => $item['nomenclature'],
                        "SkuGUID" => $item['nomenclature_guid']
                    ];

                    $balance = ClientBalance::where([
                        'client_guid' => $data['client_guid'],
                        'store_guid' => $data['store_guid'],
                        'nomenclature_guid' => $item['nomenclature_guid']
                    ])->first();

                    if(!empty($balance)){
                        $balance->total_supply -= $item['quantity'];
                        $balance->save();
                    }

                    $history = new ClientHistory();
                    $history->document = $return->guid;
                    $history->operation = "RETURN";
                    $history->client_id = $data['client_id'];
                    $history->client_guid = $data['client_guid'];
                    $history->store_id = $data['store_id'];
                    $history->store_guid = $data['store_guid'];
                    $history->nomenclature_id = $item['nomenclature_id'];
                    $history->nomenclature_guid = $item['nomenclature_guid'];
                    $history->quantity = $item['quantity'];
                    $history->price = $item['price'];
                    $history->save();
                    
                }

                $post = [
                    "ReturnOrders" => [
                        [
                            "AgentName" => $return->agent->name,
                            "AgentGUID" => $return->agent->guid,
                            "ClientName" => $return->client->name,
                            "ClientIDNO" => $return->client->idno,
                            "ClientGUID" => $return->client->guid,
                            "StoreName" => $return->store->name,
                            "StoreGUID" => $return->store->guid,
                            "docDate" => date('Y-m-d', strtotime($return->created_at)) . "T" . date('H:i:s', strtotime($return->created_at)),
                            "docNumber" => $return->guid,
                            "amountTotal" => $return->amount,
                            "docType" => $return->doc_type, // 0 - cash, 1 - invoice
                            "isExported" => $return->exported,
                            "Nomenclature" => $nomenclature,
                            "comment" => null != $return->comment ? $return->comment : ""
                        ]
                    ]
                ];

                $ch = curl_init($apiUrl . "api/SalesOrders/" . auth()->user()->code_1c);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_USERPWD, $apiUser . ":" . $apiPassword);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $response = preg_replace('/[[:^print:]]/', '', curl_exec($ch));
                curl_close($ch);

                $result = json_decode($response, true);

                // Change status in case return was sync
                if(isset($result['Response']) && !empty($result['Response'])){
                    foreach($result['Response'] as $status){
                        if($return->guid == $status['DocumentGUID']){
                            if($status['Status'] == 1){
                                $return->sync_status = 1;
                                $return->save();
                            }
                        }
                    }
                }

                return response()->json([
                    'status' => 'ok',
                    'order_guid' => $return->guid,
                    'response_1с' => $response
                ], 200);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data can not be empty'
        ], 400);
    }

    protected function generateReturnGuid()
    {
        return "RTN-" . auth()->user()->code_1c . "-" . date("Ymd") . "-" . $this->generateRandomString(18);
    }

    protected function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}