<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['user', 'message_id', 'message', 'net_values'];

    public function user(){
        return $this->hasOne(TeamsUser::class);
    }
}
