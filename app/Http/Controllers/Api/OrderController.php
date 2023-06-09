<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Nomenclature;
use App\Models\Balance;
use App\Models\ClientBalance;
use App\Models\ClientHistory;

class OrderController extends Controller
{   
    public function list()
    {
        $orders = Order::where('agent_id', auth()->user()->id)->get()->makeHidden(['updated_at']);

        if(empty($orders)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available orders'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'orders' => $orders
        ], 200);
    }

    public function new(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if(!empty($data)){
            if(!isset($data['client_id']) || !isset($data['store_id']) || !isset($data['list'])){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ], 400);
            }

            $order = new Order();
            $order->guid = $this->generateOrderGuid();
            $order->agent_id = auth()->user()->id;
            $order->agent_guid = auth()->user()->guid;
            $order->client_id = $data['client_id'];
            $order->client_guid = $data['client_guid'];
            $order->store_id = $data['store_id'];
            $order->store_guid = $data['store_guid'];
            $order->delivery_date = $data['delivery_date'];
			//$order->delivery_date = date('Y-m-d', strtotime("+1 day", strtotime($data['delivery_date'])));
            $order->amount = $data['amount'];
            $order->list = json_encode($data['list']);
            $order->comment = $data['comment'] ?? null;
            $order->check_required = $data['check_required'];
            $order->doc_type = $data['doc_type'];
            $order->exported = $data['exported'];
            $order->print_cert = $data['print_cert'];
            $order->promo = $data['promo'];
            $order->sync_status = 0;

            if($order->save()){

                $apiUrl = \Config::get('api.api_base_url');
                $apiUser = \Config::get('api.api_user');
                $apiPassword = \Config::get('api.api_password');

                $nomenclature = [];
                foreach($data['list'] as $item){
                    $nomenclature[] = [
                        /*"OrganizationIDNO" => 1002600056224,
                        "OrganizationName" => "CUSTOMAGIC SRL",
                        "organizationGUID" => "6b287d53-2d4d-11e0-97eb-003005a63c46",*/
                        "price" => $item['price'],
                        "quantity" => $item['quantity'],
                        "skuDescription" => $item['nomenclature'],
                        "skuGUID" => $item['nomenclature_guid']
                    ];

                    // 1 and 2 - common wms endpoint, 3 - another
                    $divisionIds = [
                        1 => [1,2],
                        2 => [1,2],
                        3 => [3]
                    ];
                    $stockBalance = Balance::whereIn('division_id',$divisionIds[auth()->user()->territorial_division])->where( 'nomenclature_id', $item['nomenclature_id'])->get();
                    if(!empty($stockBalance)){
                        foreach($stockBalance as $balanceRecord){
                            $balanceRecord->current -= $item['quantity'];
                            $balanceRecord->total -= $item['quantity'];
                            $balanceRecord->save();
                        }
                    }

                    $balance = ClientBalance::where([
                        'client_guid' => $data['client_guid'],
                        'store_guid' => $data['store_guid'],
                        'nomenclature_guid' => $item['nomenclature_guid']
                    ])->first();

                    if(empty($balance)){
                        $balance = new ClientBalance();
                        $balance->client_id = $data['client_id'];
                        $balance->client_guid = $data['client_guid'];
                        $balance->store_id = $data['store_id'];
                        $balance->store_guid = $data['store_guid'];
                        $balance->nomenclature_id = $item['nomenclature_id'];
                        $balance->nomenclature_guid = $item['nomenclature_guid'];
                    }

                    $balance->last_supply = $item['quantity'];
                    $balance->total_supply += $item['quantity'];
                    $balance->save();

                    $history = new ClientHistory();
                    $history->document = $order->guid;
                    $history->operation = "ORDER";
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
                    "SalesOrders" => [
                        [
                            "AgentName" => $order->agent->name,
                            "agentGUID" => $order->agent->guid,
                            "ClientName" => $order->client->name,
                            "ClientIDNO" => $order->client->idno,
                            "clientGUID" => $order->client->guid,
                            "OrderGUID" => $order->guid,
                            "StoreName" => $order->store->name,
                            "storeGUID" => $order->store->guid,
                            "deliveryDate" => $order->delivery_date,
                            "docDate" => date('Y-m-d', strtotime($order->created_at)),
                            "docNumber" => $order->guid,
                            "amountTotal" => $order->amount,
                            "isFacturaCuCheck" => $order->check_required,
                            "docType" => $order->doc_type, // 0 - cash, 1 - invoice
                            "isExported" => $order->exported,
                            "isPrintCertificate" => $order->print_cert,
                            "isPromo" => $order->promo,
                            "Nomenclature" => $nomenclature,
                            "comment" => null != $order->comment ? $order->comment : ""
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

                if(isset($result['Response']) && !empty($result['Response'])){
                    foreach($result['Response'] as $status){
                        if($order->guid == $status['DocumentGUID']){
                            if($status['Status'] == 1){
                                $order->sync_status = 1;
                                $order->save();
                            } else {
                                $logRecord = new \App\Models\ErrorLog();
                                $logRecord->source = "BACK";
                                $logRecord->breakpoint = 'Order ' . $order->guid . ' synchronization';
                                $logRecord->agent = '-';
                                $logRecord->name = 'SynchronizationError';
                                $logRecord->message = $response;
                                $logRecord->save();
                            }
                        }
                    }
                } else {
                    /* Just to prevent error losing in unhandled cases */
                    $logRecord = new \App\Models\ErrorLog();
                    $logRecord->source = "BACK";
                    $logRecord->breakpoint = 'New order synchronization';
                    $logRecord->agent = '-';
                    $logRecord->name = 'SynchronizationError';
                    $logRecord->message = $response;
                    $logRecord->save();
                }

                return response()->json([
                    'status' => 'ok',
                    'order_guid' => $order->guid,
                    'response_1с' => $response
                ], 200);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data can not be empty'
        ], 400);
    }

    protected function generateOrderGuid()
    {
        return "NSO-" . auth()->user()->code_1c . "-" . date("Ymd") . "-" . $this->generateRandomString(18);
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