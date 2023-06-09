<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CashOrder;
use App\Models\Nomenclature;

class CashOrderController extends Controller
{  
	public function list()
    {
        $cashOrders = CashOrder::where('agent_id', auth()->user()->id)->get()->makeHidden(['updated_at']);

        if(empty($cashOrders)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available cash order receipts'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'cash_order_receipts' => $cashOrders
        ], 200);
    }

	public function new(Request $request)
    {
    	$data = json_decode($request->getContent(), true);
        if(!empty($data)){
        	if(!isset($data['client_id']) || !isset($data['organization_id']) || !isset($data['store_id'])){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ], 400);
            }

            $cashOrder = new CashOrder();
            $cashOrder->guid = $this->generateCashOrderGuid();
            $cashOrder->agent_id = auth()->user()->id;
            $cashOrder->agent_guid = auth()->user()->guid;
            $cashOrder->client_id = $data['client_id'];
            $cashOrder->client_guid = $data['client_guid'];
            $cashOrder->organization_id = $data['organization_id'];
            $cashOrder->organization_guid = $data['organization_guid'];
            $cashOrder->store_id = $data['store_id'];
            $cashOrder->store_guid = $data['store_guid'];
            $cashOrder->amount = $data['amount'];
            $cashOrder->doc_type = $data['doc_type'];
            $cashOrder->exported = $data['exported'];
            $cashOrder->comment = $data['comment'] ?? null;
            $cashOrder->sync_status = 0;

            if($cashOrder->save()){
            	$apiUrl = \Config::get('api.api_base_url');
                $apiUser = \Config::get('api.api_user');
                $apiPassword = \Config::get('api.api_password');

                $post = [
                    "CashOrders" => [
                        [
                            "AgentName" => $cashOrder->agent->name,
                            "AgentGUID" => $cashOrder->agent->guid,
                            "amount" => $cashOrder->amount,
                            "ClientName" => $cashOrder->client->name,
                            "ClientIDNO" => $cashOrder->client->idno,
                            "ClientGUID" => $cashOrder->client->guid,
                            "StoreName" => $cashOrder->store->name,
                            "StoreGUID" => $cashOrder->store->guid,
                            "organizationGUID" => $cashOrder->organization->guid,
                            "organizationIDNO" => $cashOrder->organization->idno,
                            "organizationName" => $cashOrder->organization->name,
                            "docDate" => date('Y-m-d', strtotime($cashOrder->created_at)) . "T" . date('H:i:s', strtotime($cashOrder->created_at)),
                            "docNumber" => $cashOrder->guid,
                            "docType" => $cashOrder->doc_type, // 0 - cash, 1 - invoice
                            "isExported" => $cashOrder->exported,
                            "incomeOrderGUID" => $cashOrder->guid,
                            "comment" => null != $cashOrder->comment ? $cashOrder->comment : ""
                        ]
                    ]
                ];

                $ch = curl_init($apiUrl . "api/CashOrders/" . auth()->user()->code_1c);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_USERPWD, $apiUser . ":" . $apiPassword);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $response = preg_replace('/[[:^print:]]/', '', curl_exec($ch));
                curl_close($ch);

                $result = json_decode($response, true);

                if(isset($result['Response']) && !empty($result['Response'])){
                    foreach($result['Response'] as $status){
                        if($cashOrder->guid == $status['DocumentGUID']){
                            if($status['Status'] == 1){
                                $cashOrder->sync_status = 1;
                                $cashOrder->save();
                            }
                        }
                    }
                }

            	return response()->json([
                    'status' => 'ok',
                    'doc_guid' => $cashOrder->guid,
                    'response_1с' => $result
                ], 200);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data can not be empty'
        ], 400);
    }

    protected function generateCashOrderGuid()
    {
        return "RCO-" . auth()->user()->code_1c . "-" . date("Ymd") . "-" . $this->generateRandomString(18);
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