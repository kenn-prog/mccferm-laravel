<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Transaction extends Model {
  protected $table='equipment_transactions'; protected $guarded=[]; protected $casts=['borrow_date'=>'date','expected_return'=>'date','actual_return'=>'datetime'];
  public function equipment(){ return $this->belongsTo(Equipment::class); } public function user(){ return $this->belongsTo(User::class); }
  public function getIsOverdueAttribute(): bool { return $this->status==='borrowed' && $this->expected_return->isPast() && !$this->expected_return->isToday(); }
}
