<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; 
class User extends Authenticatable {
  use HasApiTokens;
  protected $guarded = []; protected $hidden = ['password','remember_token'];
  public function isAdminSide(): bool { return in_array($this->role, ['staff','admin','superadmin']); }
  public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
  public function requests() { return $this->hasMany(FermsRequest::class); }
  public function notices() { return $this->hasMany(Notice::class); }
}
