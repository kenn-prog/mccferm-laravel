<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable {
  protected $guarded = []; protected $hidden = ['password','remember_token'];
  public function isAdminSide(): bool { return in_array($this->role, ['staff','admin']); }
  public function requests() { return $this->hasMany(FermsRequest::class); }
  public function notices() { return $this->hasMany(Notice::class); }
}
