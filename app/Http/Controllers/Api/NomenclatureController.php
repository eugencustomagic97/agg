<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Nomenclature;
use App\Models\Balance;
use App\Models\PriceType;
use App\Models\NomenclaturePrices;
use App\Models\TerritorialDivision;
use App\Models\Synchronization;

class NomenclatureController extends Controller
{   
    public function list()
    {
        $nomenclature = Nomenclature::where('active', 1)->with(['balance','prices'])->get()->makeHidden(['created_at','updated_at']); // where('active', 1)->

        if(empty($nomenclature)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available nomenclature'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'nomenclature' => $nomenclature
        ], 200);
    }

    public function balance()
   	{
   		$balance = Balance::select(['nomenclature_id', 'total'])->where('division_id', auth()->user()->territorial_division)->get();
   		
   		if(empty($balance)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available balance'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'balance' => $balance
        ], 200);
   	}

    public function categoriesList()
    {
        $categories = Category::has('nomenclatures')->get()->makeHidden(['created_at','updated_at']); // has('nomenclatures') - return only categories which have nomenclatures TEMP FIX to exclude empty categories

        if(empty($categories)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available categories'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'categories' => $categories
        ], 200);
    }

    public function suppliersList()
    {
        $suppliers = Supplier::get()->makeHidden(['created_at','updated_at']);

        if(empty($suppliers)){
            return response()->json([
                'status' => 'error',
                'message' => 'There is no available suppliers'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'suppliers' => $suppliers
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

        //$result =  preg_replace('/[[:^print:]]/', '', file_get_contents($apiUrl . "Agents/GetSetOfGoods/", false, $context));
		$result =  file_get_contents($apiUrl . "Agents/GetSetOfGoods/", false, $context);
		$hash = md5($result);
        $prevSync = Synchronization::where('entity', 'Nomenclatures')->first();
		
		if(empty($prevSync)){
            $prevSync = new Synchronization();
            $prevSync->entity = "Nomenclatures";
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
		//$needSync = true;
		if($needSync){
			$data = json_decode($result, true);

			if(isset($data['Data']) && !empty($data['Data'])){
				// Get all elements in a checklist
				$checklist = Nomenclature::get(['id','guid'])->mapWithKeys(function ($item) {
					return [$item['guid'] => $item['id']];
				})->toArray();

				foreach($data['Data'] as $group){

					$modelCategory = Category::where('guid', $group['Id'])->first();
					if(empty($modelCategory)){
						$modelCategory = new Category();
					}

					$modelCategory->guid = $group['Id'];
					$modelCategory->name = $group['Description'];
					if($modelCategory->save()){
						$modelSupplier = Supplier::where('guid', $group['Organization'][0]['Id'])->first();
						if(empty($modelSupplier)){
							$modelSupplier = new Supplier();
						}

						$modelSupplier->guid = $group['Organization'][0]['Id'];
						$modelSupplier->name =  $group['Organization'][0]['Description'];

						if($modelSupplier->save()){
							if(isset($group['Nomenclature']) && !empty($group['Nomenclature'])){
								foreach($group['Nomenclature'] as $nomenclature){
									$modelNomencature = Nomenclature::where('guid', $nomenclature['Id'])->first();
									if(empty($modelNomencature)){
										$modelNomencature = new Nomenclature();
									}

									$modelNomencature->guid = $nomenclature['Id'];
									$modelNomencature->wms_id = $nomenclature['IDExchange'] ?? $nomenclature['IdExchange'] ?? null;
									$modelNomencature->category_id = $modelCategory->id;
									$modelNomencature->organization_id = $modelSupplier->id;
									$modelNomencature->name = $nomenclature['Description'];
									//$modelNomencature->price = !empty($nomenclature['Price']) ? $nomenclature['Price'] : 0;
									$modelNomencature->unit = $nomenclature['Unit'];
									$modelNomencature->coef = $nomenclature['Coef'];
									$modelNomencature->color = $nomenclature['BackgroundColor'] ?? null;
									$modelNomencature->active = 1;
									if($modelNomencature->save()){
										unset($checklist[$nomenclature['Id']]);
									}

									if(isset($nomenclature['PricesTypes']) && !empty($nomenclature['PricesTypes'])){
										foreach($nomenclature['PricesTypes'] as $priceType){
											$modelPriceType = PriceType::where(['guid' => $priceType['GUID']])->first();
											if(!empty($modelPriceType)){
												$modelNomencaturePrice = NomenclaturePrices::where([
													'nomenclature_id' => $modelNomencature->id, 
													'price_type_guid' => $priceType['GUID']
												])->first();
												if(empty($modelNomencaturePrice)){
													$modelNomencaturePrice = new NomenclaturePrices();
												}
												$modelNomencaturePrice->nomenclature_id = $modelNomencature->id;
												$modelNomencaturePrice->nomenclature_guid = $modelNomencature->guid;
												$modelNomencaturePrice->price_type_id = $modelPriceType->id;
												$modelNomencaturePrice->price_type_guid = $priceType['GUID'];
												$modelNomencaturePrice->price = $priceType['Price'];
												$modelNomencaturePrice->save();
											}
										}
									}

									/*if($modelNomencature->save()){
										if(isset($nomenclature['Balance']) && !empty($nomenclature['Balance'])){
											foreach($nomenclature['Balance'] as $balance){
												$territorialDivision = TerritorialDivision::where('guid', $balance['Id'])->first();

												$balanceModel = Balance::where(['division_id' => $territorialDivision->id, 'nomenclature_id' => $modelNomencature->id])->first();
												if(empty($balanceModel)){
													$balanceModel = new Balance();
												}

												$balanceModel->division_id = $territorialDivision->id;
												$balanceModel->division_guid = $territorialDivision->guid;
												$balanceModel->nomenclature_id = $modelNomencature->id;
												$balanceModel->nomenclature_guid = $modelNomencature->guid;
												$balanceModel->quantity = $balance['Balance'];

												$balanceModel->save();
											}
										}
									}*/
								}
							}
						}
					}
				}

				// If still there are some elements in checklist, inactivate them
				if(!empty($checklist)){
					$ids = array_values($checklist);
					$outdated = Nomenclature::whereIn('id',$ids)->get();
					foreach($outdated as $nomenclature){
						$nomenclature->active = 0;
						$nomenclature->update();
					}
				}
				
			}
		}

		return response()->json([
			'status' => 'ok',
			'message' => 'Sync triggered'
		], 200);
    }

    public function syncBalance()
    {
    	$territorialDivisions = TerritorialDivision::get(['id','guid'])->mapWithKeys(function ($item) {
		    return [$item['guid'] => $item['id']];
		});

		$nomenclatureList = Nomenclature::get(['id', 'guid', 'wms_id'])->mapWithKeys(function ($item) {
		    return [
		    	$item['wms_id'] => [
		    		'id' => $item['id'],
		    		'guid' => $item['guid']
		    	]
		    ];
		});

    	$apiUrl = \Config::get('api.api_wms_chisinau');
        $apiUser = \Config::get('api.api_user');
        $apiPassword = \Config::get('api.api_password');

        $auth = base64_encode($apiUser . ":" . $apiPassword);
        $context = stream_context_create([
            "http" => [
                "header" => "Authorization: Basic " . $auth
            ]
        ]);

        // Get for Center and South
        $result =  file_get_contents($apiUrl . "Agents/GetBalance/", false, $context);

        $data = json_decode($result, true);

        if(isset($data['Data']) && !empty($data['Data'])){
        	foreach($data['Data'] as $organization){
        		if(isset($organization['ProductsTable']) && !empty($organization['ProductsTable'])){
        			foreach($organization['ProductsTable'] as $nomenclature){
        				if(isset($nomenclatureList[$nomenclature['IDExchange']])){
        					// e964ee58-351a-11e0-bca3-003048ba25ef - Sciusev, Chisinau
	        				$balanceModel = Balance::where(['division_id' => $territorialDivisions['e964ee58-351a-11e0-bca3-003048ba25ef'], 'nomenclature_id' => $nomenclatureList[$nomenclature['IDExchange']]['id']])->first();
							if(empty($balanceModel)){
								$balanceModel = new Balance();
							}
							$balanceModel->division_id = $territorialDivisions['e964ee58-351a-11e0-bca3-003048ba25ef'];
							$balanceModel->division_guid = 'e964ee58-351a-11e0-bca3-003048ba25ef';
							$balanceModel->nomenclature_id = $nomenclatureList[$nomenclature['IDExchange']]['id'];
							$balanceModel->nomenclature_guid = $nomenclatureList[$nomenclature['IDExchange']]['guid'];
							$balanceModel->nomenclature_wms_id = $nomenclature['IDExchange'];
							$balanceModel->current = $nomenclature['CurrentBalance'];
							$balanceModel->expected = $nomenclature['ExpectedBalance'];
							$balanceModel->total = $nomenclature['TotalBalance'];
							$balanceModel->save();

							// e964ee5a-351a-11e0-bca3-003048ba25ef - Cahul
							$balanceModel = Balance::where(['division_id' => $territorialDivisions['e964ee5a-351a-11e0-bca3-003048ba25ef'], 'nomenclature_id' => $nomenclatureList[$nomenclature['IDExchange']]['id']])->first();
							if(empty($balanceModel)){
								$balanceModel = new Balance();
							}
							$balanceModel->division_id = $territorialDivisions['e964ee5a-351a-11e0-bca3-003048ba25ef'];
							$balanceModel->division_guid = 'e964ee5a-351a-11e0-bca3-003048ba25ef';
							$balanceModel->nomenclature_id = $nomenclatureList[$nomenclature['IDExchange']]['id'];
							$balanceModel->nomenclature_guid = $nomenclatureList[$nomenclature['IDExchange']]['guid'];
							$balanceModel->nomenclature_wms_id = $nomenclature['IDExchange'];
							$balanceModel->current = $nomenclature['CurrentBalance'];
							$balanceModel->expected = $nomenclature['ExpectedBalance'];
							$balanceModel->total = $nomenclature['TotalBalance'];
							$balanceModel->save();
        				}
        			}
        		}
        	}
        }


        $apiUrl = \Config::get('api.api_wms');

        // Get for North
        $result =  file_get_contents($apiUrl . "Agents/GetBalance/", false, $context);

        $data = json_decode($result, true);

        if(isset($data['Data']) && !empty($data['Data'])){
        	foreach($data['Data'] as $organization){
        		if(isset($organization['ProductsTable']) && !empty($organization['ProductsTable'])){
        			foreach($organization['ProductsTable'] as $nomenclature){
        				if(isset($nomenclatureList[$nomenclature['IDExchange']])){
        					// e964ee59-351a-11e0-bca3-003048ba25ef - Edinet
	        				$balanceModel = Balance::where(['division_id' => $territorialDivisions['e964ee59-351a-11e0-bca3-003048ba25ef'], 'nomenclature_id' => $nomenclatureList[$nomenclature['IDExchange']]['id']])->first();
							if(empty($balanceModel)){
								$balanceModel = new Balance();
							}
							$balanceModel->division_id = $territorialDivisions['e964ee59-351a-11e0-bca3-003048ba25ef'];
							$balanceModel->division_guid = 'e964ee59-351a-11e0-bca3-003048ba25ef';
							$balanceModel->nomenclature_id = $nomenclatureList[$nomenclature['IDExchange']]['id'];
							$balanceModel->nomenclature_guid = $nomenclatureList[$nomenclature['IDExchange']]['guid'];
							$balanceModel->nomenclature_wms_id = $nomenclature['IDExchange'];
							$balanceModel->current = $nomenclature['CurrentBalance'];
							$balanceModel->expected = $nomenclature['ExpectedBalance'];
							$balanceModel->total = $nomenclature['TotalBalance'];
							$balanceModel->save();
        				}
        			}
        		}
        	}
        }
    }
}