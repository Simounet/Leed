<?php

/*
 @nom: User
 @auteur: Idleman (http://blog.idleman.fr)
 @description:  Classe de gestion des utilisateurs
 */

class User extends MysqlEntity{

    const OTP_INTERVAL = 30;
    const OTP_DIGITS   = 8;
    const OTP_DIGEST   = 'sha1';

    protected $id,$login,$password,$cryptographicSalt,$otpSecret;
    protected $TABLE_NAME = 'user';
    protected $object_fields =
    array(
        'id'=>'key',
        'login'=>'string',
        'password'=>'string',
        'cryptographicSalt' => 'string',
        'otpSecret'=>'string',
    );

    protected $object_fields_uniques =
    array(
        'login'
    );

    function __construct(){
        parent::__construct();
    }

    function setId($id){
        $this->id = $id;
    }

    function isOtpSecretValid($otpSecret) {
        // Teste si la longueur est d'au moins 8 caractères
        // et en Base32: [A-Z] + [2-7]
        return is_string($otpSecret) && preg_match('/^[a-zA-Z2-7]{8,}$/', $otpSecret);
    }

    protected function getOtpControler() {
        return new \OTPHP\TOTP($this->otpSecret, array('interval'=>self::OTP_INTERVAL, 'digits'=>self::OTP_DIGITS, 'digest'=>self::OTP_DIGEST));
    }

    function getOtpKey() {
        $otp = $this->getOtpControler();
        return str_pad($otp->now(), $otp->digits, '0', STR_PAD_LEFT);
    }

    function exist($login,$password,$otpEntered=Null){
        $userManager = new User();
        // @TODO à gérer dans MysqlEntity
        $query = 'SELECT * FROM `'.MYSQL_PREFIX.$this->TABLE_NAME.'` WHERE password=SHA1(CONCAT("' . $password . '", cryptographicSalt))';
        $result = $this->customQuery($query);
        $users = $this->getObjectsFromQuery($result);
        $user = count($users) > 0 ? $users[0] : false;

        if (false!=$user) {
            $otpSecret = $user->otpSecret;

            global $configurationManager;
            switch (True) {
                case !$configurationManager->get('otpEnabled'):
                case empty($otpSecret) && empty($otpEntered):
                    // Pas d'OTP s'il est désactivé dans la configuration où s'il n'est pas demandé et fourni.
                    return $user;
            }
            $otp = $user->getOtpControler();
            if ($otp->verify($otpEntered) || $otp->verify($otpEntered, time()-10)) {
                return $user;
            }
        }

        return false;
    }

    static function get($login){
        $userManager = new User();
        return $userManager->load(array('login'=>$login,));
    }

    function getToken() {
        assert('!empty($this->password)');
        assert('!empty($this->login)');
        return sha1($this->password.$this->login);
    }

    public function add($login = false, $password = false, $logger = false) {
        if(!$logger) {
            require_once('Logger.class.php');
            $logger = new Logger('settings');
        }
        if(empty($login)) {
            $logger->appendLogs(_t("USER_ADD_MISSING_LOGIN"));
        }
        $existingUser = $this->load(array('login' => $login));
        if($existingUser instanceof User) {
            $logger->appendLogs(_t("USER_ADD_DUPLICATE"));
            $logger->save();
            return false;
        }
        if(empty($password)) {
            $logger->appendLogs(_t("USER_ADD_MISSING_PASSWORD"));
        }
        if($logger->hasLogs()) {
            $logger->save();
            return false;
        }
        $this->setLogin($login);
        $this->setCryptographicSalt($this->generateSalt());
        $this->setPassword($password, $this->getCryptographicSalt());
        $this->save();
        $this->createDefaultFolder();
        $this->createDefaultUserConfiguration();
        $logger->appendLogs(_t("USER_ADD_OK"). ' '.$login);
        $logger->save();
        return true;
    }

    public function getUserList() {
        return $this->loadAllOnlyColumn(
            '`id`, `login`',
            array('id' => 1),
            '`id` ASC',
            null,
            '>='
        );
    }

    public function remove($userId) {
        if((int) $userId === 1) {
            return false;
        }
        require_once('Logger.class.php');
        $logger = new Logger('settings');
        if(empty($userId)) {
            $logger->appendLogs(_t("USER_DEL_MISSING_ID"));
            $logger->save();
            return false;
        }
        $user = $this->load(array('id' => $userId));
        if(!$user) {
            $logger->appendLogs(_t("USER_DEL_UNKNOWN_ID").' '.$userId);
            $logger->save();
            return false;
        }
        $this->setLogin($user->getLogin());
        $this->cleanSideTables($userId);
        $this->delete(array('id' => $userId));
        $logger->appendLogs(_t("USER_DEL_OK").$user->getLogin());
        $logger->save();
        return true;
    }

    protected function cleanSideTables($userId) {
        $this->cleanFolder($userId);
        $this->cleanFeed($userId);
        $this->cleanUserConfiguration($userId);
    }

    protected function cleanFolder($userId) {
        $folderManager = new Folder();
        $folderManager->delete(array('userid' => $userId));
    }

    protected function cleanFeed($userId) {
        $feedManager = new Feed();
        $feedManager->delete(array('userid' => $userId));
    }

    protected function cleanUserConfiguration($userId) {
        $userConfigurationManager = new UserConfiguration();
        $userConfigurationManager->delete(array('userid' => $userId));
    }

    public function createDefaultFolder() {
        $folderManager = new Folder();
        if(!$folderManager->tableExists()) {
            return false;
        }
        $folderManager->setName(_t('GENERAL_FOLDER'));
        $folderManager->setUserid($this->getId());
        $folderManager->setParent(-1);
        $folderManager->setIsopen(1);
        $folderManager->save();
    }

    protected function createDefaultUserConfiguration() {
        $userConfiguration = new UserConfiguration($this->getId());
        $userConfiguration->setDefaults();
    }

    static function existAuthToken($auth=null){
        $result = false;
        $userManager = new User();
        $users = $userManager->populate('id');
        $phpAuth = strtolower(@$_SERVER['PHP_AUTH_USER']);
        if (empty($auth)) $auth = @$_COOKIE['leedStaySignedIn'];
        foreach($users as $user){
            if ($user->getToken()==$auth || strtolower($user->login)===$phpAuth){
                $result = $user;
                break;
            }
        }
        return $result;
    }

    public function changePassword($userId, $password) {
        if(trim($password) === '') {
            return false;
        }
        $query = 'UPDATE `'.MYSQL_PREFIX.$this->TABLE_NAME.'` SET `password`=SHA1(CONCAT("' . $password . '", cryptographicSalt )) WHERE id=' . $userId;
        return $this->customQuery($query);
    }

    protected function generateSalt() {
        return ''.mt_rand().mt_rand();
    }

    protected function encrypt($password, $salt=''){
        return sha1($password.$salt);
    }

    function setStayConnected() {
        ///@TODO: set the current web directory, here and on del
        setcookie('leedStaySignedIn', $this->getToken(), time()+31536000);
    }

    static function delStayConnected() {
        setcookie('leedStaySignedIn', '', -1);
    }

    function getId(){
        return $this->id;
    }

    function getLogin(){
        return $this->login;
    }

    function setLogin($login){
        $this->login = $login;
    }

    function getPassword(){
        return $this->password;
    }

    function setPassword($password,$salt=''){
        $this->password = $this->encrypt($password,$salt);
    }

    public function getCryptographicSalt() {
        return $this->cryptographicSalt;
    }

    public function setCryptographicSalt($salt) {
        $this->cryptographicSalt = $salt;
    }

    function getOtpSecret(){
        return $this->otpSecret;
    }

    function setOtpSecret($otpSecret){
        $this->otpSecret = $otpSecret;
    }

    function resetPassword($resetPassword, $salt=''){
        $this->setPassword($resetPassword, $salt);
        $this->otpSecret = '';
        $this->save();
    }

}

?>
