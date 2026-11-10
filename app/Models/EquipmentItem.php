<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EquipmentItem extends Model { protected $table='equipment_request_items'; protected $guarded=[]; protected $casts=["borrow_date"=>"date","expected_return"=>"date"]; public function equipment(){ return $this->belongsTo(Equipment::class); } }
