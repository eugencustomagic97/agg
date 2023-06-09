<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TerritorialDivision;
use App\Models\Synchronization;

class AgentController extends Controller
{   
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

        //$result =  preg_replace('/[[:^print:]]/', '', file_get_contents($apiUrl . "Agents/GetAgents/", false, $context));
		$result =  file_get_contents($apiUrl . "Agents/GetAgents/", false, $context);
		$hash = md5($result);
        $prevSync = Synchronization::where('entity', 'Agents')->first();
		
		if(empty($prevSync)){
            $prevSync = new Synchronization();
            $prevSync->entity = "Agents";
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
		$needSync = true;
		if($needSync){
			$data = json_decode($result, true);

			if(isset($data['Data']) && !empty($data['Data'])){
				foreach($data['Data'] as $agent){

					$modelAgent = User::where('guid', $agent['Id'])->first();
					if(empty($modelAgent)){
						$modelAgent = new User();

						$modelAgent->email = str_replace(" ", "_", strtolower(preg_replace("/[^a-zA-Z0-9\s]/", "", $agent['Description']))) . "@agg.md";
						$modelAgent->password = \Illuminate\Support\Facades\Hash::make($agent['Code']);
					}

					$modelAgent->guid = $agent['Id'];
					$modelAgent->name = $agent['Description'];
					$modelAgent->code_1c = $agent['Code'];

					if(isset($agent['IdTerritorialDivision']) && !empty($agent['IdTerritorialDivision'])){
						$modelTerritorialDivision = TerritorialDivision::where('guid', $agent['IdTerritorialDivision'])->first();
						if(empty($modelTerritorialDivision)){
							$modelTerritorialDivision = new TerritorialDivision();
							$modelTerritorialDivision->guid = $agent['IdTerritorialDivision'];
							$modelTerritorialDivision->name = $agent['TerritorialDivision'];
							$modelTerritorialDivision->save();
						}
					}

					$modelAgent->territorial_division = $modelTerritorialDivision->id;
					$modelAgent->save();
				}
			}
		}
    }
}