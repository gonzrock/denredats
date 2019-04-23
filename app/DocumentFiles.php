<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentFiles extends Model
{
   	protected $guarded = [];
   	
 
    public function document()
    {
        return $this->belongsTo('App\Document');
    }
}
