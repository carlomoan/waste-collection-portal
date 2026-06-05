<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Budget extends Model {
    use SoftDeletes;
    protected $fillable = ['name','total_budget','spent','year','month','status','created_by'];
    protected $casts = ['total_budget'=>'decimal:2','spent'=>'decimal:2','year'=>'integer','month'=>'integer'];
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
