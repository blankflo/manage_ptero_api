<?php
namespace App\Classes;
use Illuminate\Support\Facades\Http;
use App\Models\{User, Survey};
use Carbon\Carbon;
use  Exception;
use App\Models\Offrespecs;

class Server_ptero
{
    public static $created;

    protected $user;//Instance de user
    protected $uri;
    protected $endpoint;
    protected $apikey;
    private static $count_server;

    private $id_allocation;
    private $node_id;



    public function __construct(User $user, String $uri, $apikey, String $endpoint="api/application/")
    {
        $this->user = $user;
        $this->uri = $uri;
        $this->apikey = $apikey;
        $this->endpoint = $endpoint;
        self::$count_server++;
        $this->UpdateSurvey();

    }

    private function verifyStatusCode($response, int $code_valid){ //ok

        try{
        if($response->getStatusCode() !== $code_valid || $response->getBody()==null){

            return false; }
         else {return true; }


    }catch(Exeption $e){ return false ;}

    }
    private function UpdateSurvey(){

        Survey::Create([
            "servers_started"=>self::$created,
            "servers_created"=>self::$count_server,
            "allocations_remaining"=>$this->allocation_remaining(),
            "created_at"=>Carbon::now()
        ]);

    }

    public function getUserIdPtero(){

        try{
            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."users/external/".$this->user->external_id);
            //  dump(json_decode($response->getBody()));
            dump($response);

            if(!$this->verifyStatusCode($response, 200)){
                return false;
            }
            return $response["attributes"]["id"];


        }
        catch(Exeption $e){

            return false;
        }

        return false;
    }



    public function getInfoServer(String $external_id){ //ok

        try{
            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."servers/external/".$external_id);

                //$this->verifyStatusCode($response, 201);
                ////dump(json_decode($response->getBody()));

                if (!$this->verifyStatusCode($response, 200)){return false;}

            // $response = json_decode($response->getBody());
                $response = json_decode($response->getBody());
            return $response;

        }

            catch(Exeption $e){

                return false;
            }


        }


    public function getIdserver(String $external_id){ //ok

        try{
            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."servers/external/".$external_id);

                // $this->verifyStatusCode($response, 200);
                dump(json_decode($response->getBody()));

                if (!$this->verifyStatusCode($response, 200)){return false;}
            // $response = json_decode($response->getBody());
            dump($response);
            // $response = json_decode($response->getBody());


                foreach($response['attributes'] as $rep=>$val){

                    if($rep == "id"){
                        return $val;
                    }
                }

        }

            catch(Exeption $e){

                return false;
            }
               

        }
        public function getidentifier(String $external_id){ //ok

            try{
                $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."servers/external/".$external_id);

                    //$this->verifyStatusCode($response, 201);
                    //dump(json_decode($response->getBody()));

                    if (!$this->verifyStatusCode($response, 200)){return false;}

                // $response = json_decode($response->getBody());
                $response = json_decode($response->getBody());
                // $response = json_decode($response->getBody());
                    foreach($response->attributes as $rep=>$val){

                        if($rep == "identifier"){
                            return $val;
                        }


                    }

            }

                catch(Exeption $e){

                    return false;
                }
                    return false;

            }



    //debut server
    // public function getUserIdPtero(){

    //     $email =$this->user->email;

    //     try{
    //         $response = Http::timeout(400)->withToken($this->apikey)->put($this->uri.$this->endpoint."/users",[
    //             "email"=>$email
    //         ]);

    //             $this->verifyStatusCode($response, 201);

    //             foreach($response['data'] as $obj){
    //                 return $obj["attributes"]["id"];
    //         }
    //     }
    //         catch(Exeption $e){

    //             return false;
    //         }

    //         return false;
    //     }
        // public function getIdServers($external_id){

        //     $email =$this->user->email;

        //     try{
        //         $response = Http::timeout(400)->withToken($this->apikey)->post($this->uri.$this->endpoint."server/external/".$external_id);

        //             $this->verifyStatusCode($response, 201);

        //             foreach($response['data'] as $obj){
        //                 return $obj["attributes"]["id"];
        //         }
        //     }
        //         catch(Exeption $e){

        //             return false;
        //         }

        //         return false;
            // }



    public function createServer(String $name , $model, String $games, String $external_id=null, String $nameNode){ //to finish
//tested
$environment=[];
    try{
        // $games =strtoupper($games);
        switch($games){
            case "gmod":
                $environment=["SRCDS_MAP" => "gm_flatgrass",
                                "SRCDS_APPID"=>"4020",
                                 "GAMEMODE" => "sandbox",
                                     "TICKRATE"=>22,
                                        "MAX_PLAYERS"=>$model->max_players];
            break;



             case "mc":
                          $environment=["BUNGEE_VERSION"=> "latest",
                                         "SERVER_JARFILE"=>"server.jar"];
             break;

            //  default:
            //     throw new Exception('bad games used');

        }


       $id_allocation=$this->id_allocation_available($nameNode);
            // if(isset($option["external_id"])&&isset($option["name"])&&isset($option["egg"])&&isset($option["docker_img"])&&isset($option["startup"])&&isset($option["environment"])&&isset($option["limits"])&&isset($option["features_limits"])&&isset($option["allocations"])){
            $encode = [
                "external_id"=>$external_id,
                "name"=>$name,
                "user"=>$this->getUserIdPtero(),
                "egg"=>$model->egg,

                "docker_image"=>$model->docker_img,
                "startup" =>$model->startup,
                "auto_deploy"=>true,

                "environment"=>$environment,
                "limits"=>[
                    "memory"=>$model->ram,
                    "swap"=>$model->swap,
                    "disk"=>$model->disk,
                    "io"=>$model->io,
                    "cpu"=>$model->cpu
                     ],
                "feature_limits"=>["databases"=> $model->database,
                "backups"=> $model->backup],
                "allocation"=>["default"=>$id_allocation]
            ];

             //    ];}}

            // //dump($this->listEgg());



            $response = Http::timeout(30)->withToken($this->apikey)->post($this->uri.$this->endpoint."servers",$encode);

           if($this->verifyStatusCode($response, 201)){
            self::$created = self::$created++;

                return true;

           }



                }
                catch(Exeption $e)
                {
                    return false;
                }

            return false;

    }

    // public function generateSlugFrom($string)
    // {
    //     // Put any language specific filters here, 
    //     // like, for example, turning the Swedish letter "å" into "a"
    
    // // Remove any character that is not alphanumeric, white-space, or a hyphen 
    // $string = preg_replace('/[^a-z0-9\s\-]/i', ' ', $string);
    // // Replace all spaces with hyphens
    // $string = preg_replace('/\s/', ' ', $string);
    // // Replace multiple hyphens with a single hyphen
    // $string = preg_replace('/\-\-+/', ' ', $string);
    // // Remove leading and trailing hyphens, and then lowercase the URL
    // $string = strtolower(trim($string, ' '));
    
    //     return $string;
    // }


    public function actionServerPtero(String $external_id, String $method) //to test
    {
        $methods = ['suspend','unsuspend', 'reinstall'];
        try{
            if(!in_array($method,$methods)){

                throw new Exeption("bad method use");
            }
            $methods = "/".$method;

        $response = Http::timeout(400)->withToken($this->apikey)->post($this->uri.$this->endpoint."servers/".$this->getIdServer($external_id).$methods);
        // dump($response);

        if (!$this->verifyStatusCode($response, 204)){return false;}

        }
        catch(Exeption $e){

            return false;
        }
        return true;
    }

    public function deleteServer(String $external_id){ //tested
        if(isset($external_id)){
        // $force = "force";
        // $force = ($force) ?: '';

        $id_server = 0;

        if($this->getInfoServer($external_id)!== false){$id_server = $this->getInfoServer($external_id);

            foreach($id_server->attributes as $ia=>$val){
                if($ia == "id"){
                    $id_server = $val;
                }


            }}
        else{return false;}

        try{
            $response = Http::timeout(400)->withToken($this->apikey)->DELETE($this->uri.$this->endpoint."servers/".$id_server);
            $code = $response->getStatusCode();

            if($code!==204){
                return false;
            }


            }
            catch(Exeption $e){

                return false;
            }
    }
        else{throw new Execption("pls check every parameters") ;}
        return true;

    }
    public function ModifServer(bool $build=false ,String $external_id, Offrespecs $model  ){ //finish

        try{
                $details_verif = "/details";
                $build_verif = "/build";
                $startup_verif = "/startup";
                $req=[];
                $method;
                $allocationServer = null;

                if($build){
                    $method = $build_verif;

                   $zebi = $this->getInfoServer((string)$external_id);

                    foreach($zebi->attributes as $z=>$value){
              
                      if($z==='allocation'){
                       $allocationServer = $value;
                      }
                 
                    }
                    

                    ;
                    // if(isset($option["external_id"])&&isset($option["name"])&&isset($option["egg"])&&isset($option["docker_img"])&&isset($option["startup"])&&isset($option["environment"])&&isset($option["limits"])&&isset($option["features_limits"])&&isset($option["allocations"])){
                    $encode = [
                            "allocation"=>$allocationServer,
                            "memory"=>$model->ram,
                            "swap"=>$model->swap,
                            "disk"=>$model->disk,
                            "io"=>$model->io,
                            "cpu"=>$model->cpu,
                            "threads"=> null,
                            "feature_limits"=>[
                            "databases"=> $model->database,
                            "allocations"=>0,
                            "backups"=> $model->backup,
                           
                            ]
                    ];
        
                }

                $response = Http::timeout(400)->withToken($this->apikey)->patch($this->uri.$this->endpoint."servers/".$this->getIdServer($external_id).$method,$encode);
                // dump($response);


               if($this->verifyStatusCode($response, 200))return true;

            }
            catch(Exeption $e){

                return false;
            }
        return false;

    }


    public function listEgg(){ //work

        try{
            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri."api/application/nests/1/eggs?include=nest,servers");

                //$this->verifyStatusCode($response, 201);
                if ($this->verifyStatusCode($response, 200)){return false;}
               return $response;

        }
            catch(Exeption $e){

                return false;
            }

            return false;
        }



    public function list_server(){ //work

        try{
            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri."api/application/servers");

                //$this->verifyStatusCode($response, 201);
                                    //dump(json_decode($response->getBody()));

                                    if (!$this->verifyStatusCode($response, 200)){return false;}
                                    return $response;

                    // return $obj["attributes"]["id"];

        }
            catch(Exeption $e){

                return false;
            }

            return false;
        }


        public function list_allocation(String $nameNode){ //work

            try{
                $id_node = $this->count_node($nameNode);
                $tab_alloc=[];
                $i=0;

             if(is_array($id_node)){
                if(count($id_node)>1){
                    while($id_node < count($id_node)){
                       $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nodes/".$id_node[$i]."/allocations");

                        if ($this->verifyStatusCode($response, 200)){ $tab_alloc[$i] = $response;unset($id_node[$i]);}
                       $i++;
                     }
                     return $tab_alloc;
                 }
            
                else{
                
                $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nodes/".$id_node."/allocations");
                    if ($this->verifyStatusCode($response, 200)){return $response;}

                        // return $obj["attributes"]["id"];
                }
            }
             else return false;
            




             }

                catch(Exeption $e){

                    return false;
                }

                return false;
            }

            public function list_nest(){ //work

                try{
                    $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nests");

                        //$this->verifyStatusCode($response, 201);
                                            //dump(json_decode($response->getBody()));

                            // return $obj["attributes"]["id"];


                            if ($this->verifyStatusCode($response, 200)){return false;}
                            return $response;

                }
                    catch(Exeption $e){

                        return false;
                    }

                    return false;
                }


                public function get_id_external($external_id){ //work

                    try{
                        $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."servers/external/".$external_id);

                            $this->verifyStatusCode($response, 200);

                            //dump(json_decode($response->getBody()));

                                foreach($response['data'] as $obj){
                                return$obj["attributes"]["id"];
                              }

                    }
                        catch(Exeption $e){

                            return false;
                        }

                        return false;
                    }

                    public function list_node(){ //work

                        try{
                            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nodes");

                                //$this->verifyStatusCode($response, 201);
                                                   // dump(json_decode($response->getBody()));

                                    // return $obj["attributes"]["id"];
                                    if (!$this->verifyStatusCode($response, 200)){return false;}
                                    return json_decode($response->getBody());

                        }
                            catch(Exeption $e){

                                return false;
                            }

                            return false;
                        }

                        // public function list_node(){ //work

                        //     try{
                        //         $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nodes");
    
                        //             //$this->verifyStatusCode($response, 201);
                        //                                // dump(json_decode($response->getBody()));
    
                        //                 // return $obj["attributes"]["id"];
                        //                 if (!$this->verifyStatusCode($response, 200)){return false;}
                        //                 return json_decode($response->getBody());
    
                        //     }
                        //         catch(Exeption $e){
    
                        //             return false;
                        //         }
    
                        //         return false;
                        //     }

                        public function egg_detail(){ //work

                            try{
                                $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nests/2/eggs/9");

                                    //$this->verifyStatusCode($response, 201);
                                                        //dump(json_decode($response->getBody()));

                                                        if ($this->verifyStatusCode($response, 200)){return false;}
                                                        return $response;

                                        // return $obj["attributes"]["id"];

                            }
                                catch(Exeption $e){

                                    return false;
                                }

                                return false;
                            }

                        public function allocation_remaining(){  //tested

                            if($this->list_allocation() !== false && !is_bool($this->list_allocation())){$alloc_ptero = $this->list_allocation();}
                            else{return false;}
                            $count = 0;

                                            //dump($alloc_ptero);
                                foreach($alloc_ptero['data'] as $ap){

                                    if(!$ap['attributes']['assigned']){
                                        $count = $count + 1;
                                    }
                                }

                                return $count;


                        }
                        public function id_allocation_available(String $nameNode){ //tested
                            try{
                                $alloc_ptero=0;
                            if($this->list_allocation($nameNode) !== false){$alloc_ptero = $this->list_allocation($nameNode);}
                           else{throw new Exception('no more id available');}
                            //$count;
                            $id=[];
                            $c=0;

                                foreach($alloc_ptero['data'] as $ap){
                                    $c = $c+1;
                                    if(!$ap["attributes"]['assigned']){

                                        ////dump($c);

                                        return $ap["attributes"]['id'];

                                    }


                                }
                                return $id;
                            }
                            catch(Exeption $e){

                            }

                            return false;


                        }


                        public function get_ip_by_id_allocation(String $external_id){ //to test
                            try{
                            //$count;

                           // $id = $this->id_location_available()  
                           $node_id;
                           $id_allocation;
                           $ip;
                           $port;

                           $infoserver = $this->getInfoServer($external_id);

                           if($infoserver !==false ){


                                foreach($infoserver->attributes as $info=>$value){


                                    if($info=="node"){
                                        $node_id = $value;
                                    }
                                    elseif($info == "allocation"){
                                        $id_allocation = $value;
                                    }
                               }


                           }
                           else {return false;}



                            $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nodes/".$node_id."/allocations");
                            if (!$this->verifyStatusCode($response, 200)){
                              return false;
                            }
                            //dump(json_decode($response->getBody()));

                                foreach($response['data'] as $ap){
                                    //dump($id_allocation);
                                    if($ap["attributes"]['id'] ==$id_allocation){
                                        $ip = $ap["attributes"]['ip'];
                                        $port = $ap["attributes"]['port'];
                                        return compact("ip","port");
                                    }

                                }
                            }
                            catch(Exeption $e){

                            }




                        }

                        public function count_node(String $nameNode){ //work

                            try{
                                $idNode=[];
                                $i = 0;
                                $response = Http::timeout(400)->withToken($this->apikey)->get($this->uri.$this->endpoint."nodes");

                                    //$this->verifyStatusCode($response, 201);


                                                        if (!$this->verifyStatusCode($response, 200)){return false;}

                                                        else{
                                                            foreach($response["data"]as $b){

                                                                if(str_contains($b['attributes']['name'], $nameNode))$idNode[$i] = $b['attributes']["id"];$i++;
                                                                
                                                            }
                                                            return $idNode
                                                        }
                                                    


                                        // return $obj["attributes"]["id"];

                            }
                                catch(Exeption $e){

                                    return false;
                                }

                                return false;
                            }

}

