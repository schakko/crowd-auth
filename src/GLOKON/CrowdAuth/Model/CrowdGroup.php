<?php
namespace GLOKON\CrowdAuth\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;

/**
 * Model for persisting the cached groups of the connected Crowd instance.
 * To define your own group model, extend this class and let point the crowd_group_model setting to your own class.
 */
class CrowdGroup extends Model
{
	protected $fillable = ['group_name'];
	
	protected $table = 'crowd_groups';
	
	public function groups() {
		return $this->belongsToMany('GLOKON\CrowdAuth\Model\CrowdUser', 'crowdgroup_crowduser');
	}
}
