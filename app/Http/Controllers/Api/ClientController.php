<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\AgentClient;
use App\Models\AgentStore;
use App\Models\Store;
use App\Models\Contact;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\PriceType;
use App\Models\StoresPriceTypes;
use App\Models\Synchronization;

class ClientController extends Controller
{
    public function list()
    {
        //$clients = Client::with(['stores', 'statuses'])->get();
        $clients = auth()->user()->clients->makeHidden(['created_at', 'updated_at']);
        foreach($clients as $client){
        	$client->stores->makeHidden(['created_at', 'updated_at']);
        	$client->statuses->makeHidden(['created_at', 'updated_at']);
        	foreach($client->stores as $store){
        		$store->priceTypes->makeHidden(['created_at', 'updated_at']);
        	}
        }
        if(empty($clients)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available clients'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'clients' => $clients
        ], 200);
    }

    public function storesList($client)
    {
        $stores = Store::where('client_id', $client)->get()->makeHidden(['created_at', 'updated_at']);

        if(empty($stores)){
            return response()->json([
                'status' => 'error',
                'message' => 'The client has no available stores'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'stores' => $stores
        ], 200);
    }

    public function contactsList($client)
    {
        $contacts = Contact::where('client_id', $client)->get()->makeHidden(['created_at', 'updated_at']);

        if(empty($contacts)){
            return response()->json([
                'status' => 'error',
                'message' => 'The client has no available contacts'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'contacts' => $contacts
        ], 200);
    }

    public function sync()
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

        $agentCode = auth()->user()->code_1c; // Webmaster do not have code 1c, so use some other code, while testing
        if(!empty($agentCode)){
            //$result =  preg_replace('/[[:^print:]]/', '', file_get_contents($apiUrl . "api/clients/".$agentCode, false, $context));
			$result =  file_get_contents($apiUrl . "api/clients/".$agentCode, false, $context);


			$hash = md5($result);
            $prevSync = Synchronization::where('entity', 'ClientsFor_'.$agentCode)->first();

			if(empty($prevSync)){
                $prevSync = new Synchronization();
                $prevSync->entity = "ClientsFor_".$agentCode;
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
        }

		//$needSync = true; // TODO: Delete tomorrow, need to sync price types after fixes

		if($needSync){
			$data = json_decode($result, true);

			if(isset($data['Clients']) && !empty($data['Clients'])){
				foreach($data['Clients'] as $client){

					$modelClient = Client::where('guid', $client['GUID'])->first();
					if(empty($modelClient)){
						$modelClient = new Client();
					}

					$modelClient->guid = $client['GUID'];
					$modelClient->name = $client['Name'];
					$modelClient->idno = $client['IDNO'];
					$modelClient->juridical_address = $client['JuridicalAddress'];
					$modelClient->total_debt = $client['TotalDebt'];

					if($modelClient->save()){

						$pivot = AgentClient::where(['agent_id' => auth()->user()->id, 'client_id' => $modelClient->id])->first();

						if(empty($pivot)){
							$pivot = new AgentClient();
							$pivot->agent_id = auth()->user()->id;
							$pivot->client_id = $modelClient->id;
							$pivot->save();
						}

						if(isset($client['ClientStores']) && !empty($client['ClientStores'])){
							foreach($client['ClientStores'] as $store){
								$modelStore = Store::where('guid', $store['GUID'])->first();
								if(empty($modelStore)){
									$modelStore = new Store();
								}

								$modelStore->client_id = $modelClient->id;
								$modelStore->guid = $store['GUID'];
								$modelStore->name = $store['name'];
								$modelStore->address = $store['address'];
								$modelStore->telephone = $store['telephone'];
								$modelStore->email = $store['email'];

								if($modelStore->save()){
									$pivot = AgentStore::where(['agent_id' => auth()->user()->id, 'store_id' => $modelStore->id])->first();

									if(empty($pivot)){
										$pivot = new AgentStore();
										$pivot->agent_id = auth()->user()->id;
										$pivot->store_id = $modelStore->id;
										$pivot->save();
									}
								}

								if(isset($store['PricesTypes']) && !empty($store['PricesTypes'])){
									foreach($store['PricesTypes'] as $priceType){
										$supplier = Supplier::where('guid', $priceType['organizationGUID'])->first();
										if(!empty($supplier)){
											$modelPriceType = PriceType::where([
												'guid' => $priceType['GUID'],
												'organization_guid' => $priceType['organizationGUID']
											])->first();
											if(empty($modelPriceType)){
												$modelPriceType = new PriceType();
											}
											$modelPriceType->guid = $priceType['GUID'];
											$modelPriceType->name = $priceType['Name'];
											$modelPriceType->organization_id = $supplier->id;
											$modelPriceType->organization_guid = $priceType['organizationGUID'];
											if($modelPriceType->save()){
												// Fixed
												// Delete price for organization, to prevent accumulation of prices connected to store
												$pivot = StoresPriceTypes::where([
													'store_id' => $modelStore->id,
													'organization_guid' => $modelPriceType->organization_guid
												])->delete();

													$pivot = new StoresPriceTypes();
													$pivot->store_id = $modelStore->id;
													$pivot->price_type_id = $modelPriceType->id;
													$pivot->organization_id = $modelPriceType->organization_id;
													$pivot->organization_guid = $modelPriceType->organization_guid;
													$pivot->save();


											}
										}
									}
								}

								if(isset($client['Contacts']) && !empty($client['Contacts'])){
									foreach($client['Contacts'] as $contact){
										$modelContact = Contact::where('client_id', $modelClient->id)->where('office_telephone', 'like', '%' . $contact['officeTelephone'] . '%')->first();
										if(empty($modelContact)){
											$modelContact = new Contact();
										}

										$modelContact->client_id = $modelClient->id;
										$modelContact->office_telephone = $contact['officeTelephone'];
										$modelContact->email = $contact['email'];

										$modelContact->save();
									}
								}

								if(isset($client['BlockedStatuses']) && !empty($client['BlockedStatuses'])){
									foreach($client['BlockedStatuses'] as $status){
										$supplierModel = Supplier::where('guid', $status['organizationGUID'])->first();
										$modelStatus = Status::where('client_id', $modelClient->id)->where('organization_id', $supplierModel->id)->first();
										if(empty($modelStatus)){
											$modelStatus = new Status();
										}

										$modelStatus->client_id = $modelClient->id;
										$modelStatus->client_guid = $modelClient->guid;
										$modelStatus->organization_id = $supplierModel->id;
										$modelStatus->organization_guid = $supplierModel->guid;
										if(isset($client['Debts']) && !empty($client['Debts'])){
											foreach($client['Debts'] as $debt){
												if($debt['organizationGUID'] == $modelStatus->organization_guid){
													$modelStatus->debt = $debt['debtAmount'];
												}
											}
										}
										$modelStatus->reason = $status['reason'];
										$modelStatus->blocked = $status['isLocked'];

										$modelStatus->save();
									}
								}
							}
						}
					}
				}
			}
		}
        return $hash;
    }
}
