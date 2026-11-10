<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FermsRequest extends Model {
  protected $table='requests'; protected $guarded=[];
  public function user(){ return $this->belongsTo(User::class); }
  public function reservation(){ return $this->hasOne(Reservation::class,'request_id'); }
  public function item(){ return $this->hasOne(EquipmentItem::class,'request_id'); }
}
