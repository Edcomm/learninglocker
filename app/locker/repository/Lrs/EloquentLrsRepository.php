<?php namespace Locker\Repository\Lrs;

use Lrs;
//use Illuminate\Database\Eloquent\Model;

class EloquentLrsRepository implements LrsRepository {

  /**
  * @var $lrs
  */
  protected $lrs;

  /**
   * Construct
   *
   * @param $lrs
   */
  public function __construct(Lrs $lrs){
    $this->lrs = $lrs;
  }

  public function all(){
    if( \Auth::user()->role == 'super' ){
      return $this->lrs->all();
    }else{
      return $this->lrs->where('users._id', \Auth::user()->_id)->remember(10)->get();
    }
  }

  public function find($id){
    return $this->lrs->find($id);
  }

  public function validate($data){
    $lrs = new Lrs;
    return $lrs->validate( $data );
  }

  public function create( $input ){

    $user             = \Auth::user();
    $lrs              = new Lrs;
    $lrs->title       = $input['title'];
    $lrs->description = $input['description'];
    $lrs->api         = array('basic_key'    => \Locker\Helpers\Helpers::getRandomValue(),
                              'basic_secret' => \Locker\Helpers\Helpers::getRandomValue());
    $lrs->owner       = array( '_id' => \Auth::user()->_id );
    $lrs->users       = array( array('_id'   => $user->_id,
                                     'email' => $user->email,
                                     'name'  => $user->name, 
                                     'role'  => 'admin' ) );

    $lrs->save() ? $result = true : $return = false;

    //fire a create lrs event if it worked and saced
    if( $result )
      \Event::fire('user.create_lrs', array('user' => $user));

    return $result;
    
  }

  public function update($id, $input){

    $lrs = $this->find($id);

    $lrs->title       = $input['title'];
    $lrs->description = $input['description'];
   
    $lrs->save();
      
    return $lrs;

  }

  public function delete($id){
    
    $lrs = $this->find($id);

    //first delete all statements
    \Statement::where('lrs._id', $id)->delete();

    //now delete the lrs
    return $lrs->delete();
  }

  public function removeUser( $id, $user ){
    return $this->lrs->where('_id', $id)->pull('users', array('_id' => $user));
  }

  public function getLrsOwned( $user ){
    return $this->lrs->where('owner._id', $user)->select('title')->get()->toArray();
  }

  public function getLrsMember( $user ){
    return $this->lrs->where('users.user', $user)->select('title')->get()->toArray();
  }

  public function changeRole( $id, $user, $role ){
  
    $lrs = $this->find($id);
    $users = $lrs->users;
    foreach($users as &$u){
      if( $u['_id'] == $user ){
        $u['role'] = $role;
      }
    }
    $lrs->users = $users;
    return $lrs->save();
  }

  /**
   * Checks that the secret matches.
   * Also used to authenticate client users.
   * @param Illuminate\Database\Eloquent\Model $client
   * @param string $secret
   * @return Illuminate\Database\Eloquent\Model
   */
  public static function checkSecret($client, $secret) {
    if ($client !== null && $client->api['basic_secret'] === $secret) {
      return $client;
    } else {
      return null;
    }
  }

}