<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Reservation extends Model {
  protected $guarded=[]; protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime'];
  public function facility(){ return $this->belongsTo(Facility::class); }
  public function request(){ return $this->belongsTo(FermsRequest::class); }
  public function scopeConflicting($q,$fid,$s,$e){ return $q->where('facility_id',$fid)->where('status','approved')->where('starts_at','<',$e)->where('ends_at','>',$s); }
}
