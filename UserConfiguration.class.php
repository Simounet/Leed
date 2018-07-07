<?php


/*
 @nom: UserConfiguration
 @description: Classe de gestion des préférences de l'utilisateur, fonctionne sur un simple système clé=>valeur avec un cache session pour eviter les requête inutiles
 */

class UserConfiguration extends Configuration{

    const SESSION = 'user_configuration';
    protected $userid,$key,$value,$confTab;
    protected $TABLE_NAME = 'user_configuration';
    protected $object_fields =
    array(
        'userid'=>'integer',
        'key'=>'string',
        'value'=>'longstring'
    );

    protected $object_fields_uniques =
    array(
        'key'
    );

    protected $options = array(
        'articleDisplayAuthor' => '1',
        'articleDisplayDate' => '1',
        'articleDisplayFolderSort' => '1',
        'articleDisplayHomeSort' => '1',
        'articleDisplayLink' => '1',
        'articleDisplayMode' => 'summary',
        'articlePerPages' => '5',
        'displayOnlyUnreadFeedFolder' => 'false',
        'language' => 'en',
        'optionFeedIsVerbose' => 1,
        'otpEnabled' => 0,
        'theme' => 'marigolds'
    );

    public function __construct($userid = 1) {
        parent::__construct();
        $this->setUserid($userid);
    }

    public function add($key,$value){
        $userConfig = new self();
        $userConfig->setUserid($this->getUserid());
        $userConfig->setKey($key);
        $userConfig->setValue($value);
        $userConfig->save();
        $this->confTab[$key] = $value;
        unset($_SESSION[self::SESSION]);
    }

    public function put($key,$value){
        if (isset($this->confTab[$key])){
            $this->change(array('value'=>$value),array('userid' => $this->getUserid(), 'key'=>$key));
        } else {
            $this->add($key,$value);
        }
        $this->confTab[$key] = $value;
        unset($_SESSION[self::SESSION]);
    }

    public function loadKey($key, $selectedUserId=false) {
        $userId = $selectedUserId ? $selectedUserId : $this->getUserId();
        $results = $this->loadAll(array('userid' => $userId , 'key' => $key));
        return count($results) === 1 ? $results[0]->value : false;
    }

    public function getAll(){

        if(!isset($_SESSION[self::SESSION])){

            $configs = $this->loadAll(array('userid' => $this->getUserid()));
            $confTab = array();

            foreach($configs as $config){
                $this->confTab[$config->getKey()] = $config->getValue();
            }

            $_SESSION[self::SESSION] = serialize($this->confTab);

        }else{
            $this->confTab = unserialize($_SESSION[self::SESSION]);
        }
    }

    public function isOtpEnabledForOneUser() {
        $usersWithOtpEnabled = $this->loadAll(array('key' => "otpenabled", "value" => 1));
        return count($usersWithOtpEnabled) > 0;
    }

    public function setDefaults() {
        foreach($this->options as $option => $defaultValue) {
            switch($option) {
                case 'language':
                    $value = isset($_POST['install_changeLngLeed']) ? $_POST['install_changeLngLeed'] : $defaultValue;
                    break;
                case 'theme':
                    $value = isset($_POST['template']) ? $_POST['template'] : $defaultValue;
                    break;
                default:
                    $value = $defaultValue;
                    break;
            }
            $this->add($option, $value);
        }
    }

    public function getUserid(){
        return $this->userid;
    }

    public function setUserid($userid) {
        $this->userid = $userid;
    }
}
?>
