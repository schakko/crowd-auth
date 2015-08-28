<?php
namespace GLOKON\CrowdAuth\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;

/*
 * Model for persisting the cached users of the connected Crowd instance.
 * To define your own user model, extend this class and let point the crowd_user_model setting to your own class.
 */
class CrowdUser extends Model
{
	protected $fillable = ['crowd_key', 'username', 'email', 'display_name', 'first_name', 'last_name'];
	
	protected $table = 'crowd_users';
	
	public function groups() {
		return $this->belongsToMany('GLOKON\CrowdAuth\Model\CrowdGroup', 'crowdgroup_crowduser');
	}
	
	public function userHasGroup($group_id) {
		return $this->groups->contains($group_id);
	}
}
