# CICacheService
CICacheService with memcache

support with ci 3.x

first you should add a model File

For example:

<pre/>
class Users_model extends CI_Model 
{

	public $primary_key = "id";  //important

    public function __construct()
    {
        parent::__construct();
    }

}

Use it

Del cache
$this->CS->delCacheById("Users", 4, true);

Get cache
$user = $this->CS->getCacheById("Users", 5);

print_r($user);

Get list by Cache with id list
$userlist = $this->CS->getCacheByList("Users", array(5,6,8));

print_r($userlist);

print_r($this->CS->saveWithCache("Users", array('id'=>5), array('username'=> "shuxin")));
exit;
