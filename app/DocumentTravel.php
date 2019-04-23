<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentTravel extends Model
{
    protected $guarded = [];
 
    public function document()
    {
        return $this->belongsTo('App\Document');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
