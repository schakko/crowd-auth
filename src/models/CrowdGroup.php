<?php

/*
 * This file is part of CrowdAuth
 *
 * (c) Daniel McAssey <hello@glokon.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GLOKON\CrowdAuth\Models;

class CrowdGroup extends \Eloquent {

    /**
     * Whitelist
     *
     * Allow for mass Assignment
     *
     * @var array
     */
    protected $fillable = array('group_name');

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'crowd_groups';

    /**
     * Get all users that use this group
     *
     * @return CrowdUser
     */
    public function users() {
        return $this->belongsToMany('GLOKON\CrowdAuth\Models\CrowdUser', 'crowdgroup_crowduser', 'crowd_group_id', 'crowd_user_id');
    }
}