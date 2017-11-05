<?php


/*
 @nom: Configuration
 @auteur: Idleman (http://blog.idleman.fr)
 @description: Classe de gestion des préférences, fonctionne sur un simple système clé=>valeur avec un cache session pour eviter les requête inutiles
 */

class Configuration extends MysqlEntity{

    protected $id,$key,$value,$confTab;
    protected $TABLE_NAME = 'configuration';
    protected $object_fields =
    array(
        'id'=>'key',
        'key'=>'string',
        'value'=>'longstring'
    );

    protected $object_fields_uniques =
    array(
        'key'
    );

    protected $options = array(
        'feedMaxEvents' => '50',
        'syncGradCount' => '10',
        'synchronisationCode' => '',
        'synchronisationEnableCache' => '0',
        'synchronisationForceFeed' => '0',
        'synchronisationType' => 'auto',
        'root' => '',
        'otpEnabled' => 0
    );

    function __construct(){
        parent::__construct();
    }

    public function getAll(){

        if(!isset($_SESSION['configuration'])){

        $configurationManager = new Configuration();
        $configs = $configurationManager->populate();
        $confTab = array();

        foreach($configs as $config){
            $this->confTab[$config->getKey()] = $config->getValue();
        }

        $_SESSION['configuration'] = serialize($this->confTab);

        }else{
            $this->confTab = unserialize($_SESSION['configuration']);
        }
    }

    public function get($key){

        return (isset($this->confTab[$key])?$this->confTab[$key]:'');
    }

    public function put($key,$value){
        $configurationManager = new Configuration();
        if (isset($this->confTab[$key])){
            $configurationManager->change(array('value'=>$value),array('key'=>$key));
        } else {
            $configurationManager->add($key,$value);
        }
        $this->confTab[$key] = $value;
        unset($_SESSION['configuration']);
    }

    protected function createSynchronisationCode() {
        return substr(sha1(rand(0,30).time().rand(0,30)),0,10);
    }

    public function add($key,$value){
        $config = new Configuration();
        $config->setKey($key);
        $config->setValue($value);
        $config->save();
        $this->confTab[$key] = $value;
        unset($_SESSION['configuration']);
    }

    public function setDefaults() {
        foreach($this->options as $option => $defaultValue) {
            switch($option) {
                case 'synchronisationCode':
                    $value = $this->createSynchronisationCode();
                    break;
                case 'root':
                    $root = $_POST['root'];
                    $value = (substr($root, strlen($root)-1)=='/'?$root:$root.'/');
                    break;
                default:
                    $value = $defaultValue;
                    break;
            }
            $this->add($option, $value);
        }
    }

    function getId(){
        return $this->id;
    }

    function getKey(){
        return $this->key;
    }

    function setKey($key){
        $this->key = $key;
    }

    function getValue(){
        return $this->value;
    }

    function setValue($value){
        $this->value = $value;
    }




}

?>
